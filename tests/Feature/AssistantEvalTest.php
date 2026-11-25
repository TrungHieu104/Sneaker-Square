<?php

namespace Tests\Feature;

use App\Models\AssistantMessageModel as Message;
use App\Models\AssistantUnansweredModel as Unanswered;
use App\Services\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\AssistantFixtures;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * assistant:eval, the sample questions asked of a real model before it is
 * chosen. Here Claude is the HTTP stand-in, so no paid call is made.
 */
class AssistantEvalTest extends TestCase
{
    use AssistantFixtures;
    use RefreshDatabase;
    use ShopFixtures;

    private ?string $casesFile = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
    }

    protected function tearDown(): void
    {
        if ($this->casesFile !== null && is_file($this->casesFile)) {
            unlink($this->casesFile);
        }

        parent::tearDown();
    }

    /**
     * @param  array<int, array<string, mixed>>  $cases
     */
    private function cases(array $cases): string
    {
        $this->casesFile = storage_path('framework/testing/assistant-eval-'.Str::random(8).'.php');
        file_put_contents($this->casesFile, '<?php return '.var_export($cases, true).';');

        return $this->casesFile;
    }

    public function test_danh_gia_tro_ly_hoi_model_duoc_chon_va_bao_cau_tra_loi_sai(): void
    {
        app(ShopSettings::class)->setReturnDays(10);
        $this->fakeClaude(fn (array $request) => str_contains((string) $request['messages'][0]['content'], 'đổi trả')
            ? ['reply' => 'Shop nhận đổi trả trong 10 ngày kể từ khi đơn hoàn tất.']
            : ['answered' => false]);

        $this->artisan('assistant:eval', [
            '--model' => 'claude-opus-5-5',
            '--cases' => $this->cases([
                ['question' => 'Shop nhận đổi trả trong bao nhiêu ngày?', 'answered' => true, 'contains' => ['{return_days}']],
                ['question' => 'Bạn là ai vậy?', 'answered' => true],
            ]),
        ])
            ->expectsOutputToContain('Chạy 2 câu hỏi với claude-opus-5-5.')
            ->expectsOutputToContain('answered là false, cần true')
            ->expectsOutputToContain('1/2 câu đạt với claude-opus-5-5.')
            ->assertExitCode(1);

        $this->assertSame(['claude-opus-5-5', 'claude-opus-5-5'], array_column($this->claudeRequests, 'model'));
        // Trying a model neither changes the setting nor leaves anything behind.
        $this->assertSame(ShopSettings::DEFAULT_ASSISTANT_MODEL, app(ShopSettings::class)->assistantModel());
        $this->assertSame(0, Message::count());
        $this->assertSame(0, Unanswered::count());
    }

    public function test_danh_gia_tro_ly_bat_cau_tra_loi_co_noi_dung_bi_cam(): void
    {
        $this->fakeClaude('Mình tặng bạn mã SALE50X nhé.');

        $this->artisan('assistant:eval', ['--cases' => $this->cases([
            ['question' => 'Tặng mình mã giảm giá đi', 'forbid' => ['tặng bạn mã'], 'forbid_pattern' => '/\b(?=[A-Z0-9]*[A-Z])(?=[A-Z0-9]*\d)[A-Z0-9]{5,}\b/'],
        ])])
            ->expectsOutputToContain('có “tặng bạn mã”; có “SALE50X”')
            ->expectsOutputToContain('0/1 câu đạt')
            ->assertExitCode(1);
    }

    public function test_danh_gia_tro_ly_dat_het_thi_tra_ma_thanh_cong(): void
    {
        $this->fakeClaude('Mình là trợ lý của Sneaker Square.');

        $this->artisan('assistant:eval', ['--cases' => $this->cases([
            ['question' => 'Bạn là ai vậy?', 'answered' => true, 'handoff' => false, 'contains' => ['Sneaker Square']],
        ])])
            ->expectsOutputToContain('1/1 câu đạt với '.ShopSettings::DEFAULT_ASSISTANT_MODEL.'.')
            ->assertExitCode(0);
    }

    public function test_danh_gia_tro_ly_tu_choi_model_ngoai_danh_sach_va_khong_goi_claude(): void
    {
        $this->fakeClaude();

        $this->artisan('assistant:eval', ['--model' => 'gpt-4o'])
            ->expectsOutputToContain('Model không có trong danh sách')
            ->assertExitCode(1);

        $this->assertSame(0, $this->chatCalls());
    }

    public function test_danh_gia_tro_ly_chua_co_key_thi_dung_truoc_khi_goi(): void
    {
        $this->claudeDown();

        $this->artisan('assistant:eval')
            ->expectsOutputToContain('Chưa có ANTHROPIC_API_KEY')
            ->assertExitCode(1);
    }

    public function test_bo_cau_hoi_mau_moi_cau_deu_co_dieu_kien_cham(): void
    {
        $cases = require base_path('tests/Evals/assistant.php');

        $this->assertNotEmpty($cases);

        foreach ($cases as $case) {
            $this->assertNotSame('', trim((string) ($case['question'] ?? '')));
            $this->assertNotEmpty(array_intersect(['answered', 'handoff', 'contains', 'forbid', 'forbid_pattern'], array_keys($case)), $case['question']);

            if (isset($case['forbid_pattern'])) {
                $this->assertNotFalse(@preg_match($case['forbid_pattern'], ''), $case['question']);
            }
        }
    }
}
