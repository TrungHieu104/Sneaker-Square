<?php

namespace App\Console\Commands;

use App\Services\Assistant\SupportAssistant;
use App\Services\ShopSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Asks the real Claude model a set of sample questions and checks each answer
 * against what is expected, so a model is tried before it is chosen in the
 * settings. Every case is a paid API call; nothing is saved.
 */
class EvaluateAssistant extends Command
{
    protected $signature = 'assistant:eval
        {--model= : Model cần thử, mặc định là model đang chọn ở trang Cấu hình}
        {--cases=tests/Evals/assistant.php : File bộ câu hỏi mẫu}';

    protected $description = 'Chạy bộ câu hỏi mẫu với Claude thật (tốn phí API) và báo câu nào trả lời sai';

    private const PASS = 'Đạt';

    private const FAIL = 'Không đạt';

    public function handle(SupportAssistant $assistant, ShopSettings $settings): int
    {
        $model = (string) ($this->option('model') ?: $settings->assistantModel());

        if (! array_key_exists($model, ShopSettings::ASSISTANT_MODELS)) {
            $this->error('Model không có trong danh sách: '.implode(', ', array_keys(ShopSettings::ASSISTANT_MODELS)).'.');

            return self::FAILURE;
        }

        if ((string) config('services.anthropic.key') === '') {
            $this->error('Chưa có ANTHROPIC_API_KEY trong .env.');

            return self::FAILURE;
        }

        $path = (string) $this->option('cases');
        $path = Str::startsWith($path, '/') ? $path : base_path($path);

        if (! is_file($path)) {
            $this->error('Không thấy file bộ câu hỏi: '.$path);

            return self::FAILURE;
        }

        /** @var array<int, array<string, mixed>> $cases */
        $cases = require $path;
        $this->line('Chạy '.count($cases).' câu hỏi với '.$model.'.');

        return $this->evaluate($assistant, $settings, $model, $cases);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cases
     */
    private function evaluate(SupportAssistant $assistant, ShopSettings $settings, string $model, array $cases): int
    {
        $fill = [
            '{return_days}' => (string) $settings->returnDays(),
            '{hotline}' => (string) $settings->shopInfoForDisplay()['phone_display'],
        ];
        $rows = [];
        $passed = 0;

        foreach ($cases as $i => $case) {
            $result = $assistant->trial((string) $case['question'], $model);
            $problems = $this->problems($case, $result, $fill);
            $passed += $problems === [] ? 1 : 0;
            $rows[] = [
                $i + 1,
                Str::limit((string) $case['question'], 50),
                $problems === [] ? self::PASS : self::FAIL,
                implode('; ', $problems),
                Str::limit((string) ($result['answer']['reply'] ?? $result['fixed'] ?? ''), 80),
                isset($result['trace']['ms']) ? $result['trace']['ms'].' ms' : '',
            ];
        }

        $this->table(['#', 'Câu hỏi', 'Kết quả', 'Lý do', 'Câu trả lời', 'Thời gian'], $rows);
        $this->line($passed.'/'.count($cases).' câu đạt với '.$model.'.');

        return $passed === count($cases) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * What the answer got wrong; none means it passes.
     *
     * @param  array<string, mixed>  $case
     * @param  array{intent: mixed, fixed: ?string, answer: ?array<string, mixed>, trace: ?array<string, mixed>}  $result
     * @param  array<string, string>  $fill
     * @return array<int, string>
     */
    private function problems(array $case, array $result, array $fill): array
    {
        if (isset($result['trace']['error'])) {
            return ['Claude không trả lời: '.$result['trace']['error']];
        }

        $answer = $result['answer'];
        $reply = (string) ($answer['reply'] ?? $result['fixed'] ?? '');
        $decided = [
            'answered' => $answer === null ? $result['fixed'] !== null : ($answer['answered'] ?? false) === true,
            'handoff' => $result['intent'] === null || ($answer['handoff'] ?? false) === true,
        ];
        $problems = [];

        foreach ($decided as $key => $value) {
            if (array_key_exists($key, $case) && $case[$key] !== $value) {
                $problems[] = $key.' là '.($value ? 'true' : 'false').', cần '.($case[$key] ? 'true' : 'false');
            }
        }

        return array_merge($problems, $this->wording($case, $reply, $fill));
    }

    /**
     * @param  array<string, mixed>  $case
     * @param  array<string, string>  $fill
     * @return array<int, string>
     */
    private function wording(array $case, string $reply, array $fill): array
    {
        $problems = [];

        foreach ((array) ($case['contains'] ?? []) as $text) {
            $text = strtr((string) $text, $fill);

            if (! Str::contains($reply, $text, ignoreCase: true)) {
                $problems[] = 'thiếu “'.$text.'”';
            }
        }

        foreach ((array) ($case['forbid'] ?? []) as $text) {
            if (Str::contains($reply, (string) $text, ignoreCase: true)) {
                $problems[] = 'có “'.$text.'”';
            }
        }

        if (isset($case['forbid_pattern']) && preg_match((string) $case['forbid_pattern'], $reply, $found)) {
            $problems[] = 'có “'.$found[0].'”';
        }

        return $problems;
    }
}
