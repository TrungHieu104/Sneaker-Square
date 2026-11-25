<?php

namespace App\Services\Assistant;

use Anthropic\Client;
use Anthropic\Core\Exceptions\AnthropicException;
use Anthropic\Messages\TextBlock;
use App\Services\ShopSettings;
use GuzzleHttp\Client as Guzzle;
use Psr\Http\Client\ClientInterface;

/**
 * The Claude API, asked one question at a time with the data Laravel already
 * looked up. It only reads what it is handed and answers in the JSON shape
 * it is given; it cannot query the shop or act on an order.
 */
class ClaudeClient
{
    // The facts are already looked up, so the model needs little reasoning, and the customer is waiting.
    private const EFFORT = 'low';

    private const MAX_TOKENS = 2048;

    // The SDK does not enforce a timeout itself; the HTTP client must. With one
    // retry an answer takes at most about 45s (unless the API asks to wait),
    // inside the widget's 60s wait and the job's 75s limit.
    private const TIMEOUT_SECONDS = 20;

    private const CONNECT_TIMEOUT_SECONDS = 5;

    private const MAX_RETRY_DELAY_SECONDS = 2.0;

    /**
     * @param  ClientInterface|null  $transporter  the HTTP client to send through; tests pass a fake
     */
    public function __construct(
        private readonly ShopSettings $settings,
        private readonly ?ClientInterface $transporter = null,
    ) {}

    /**
     * The system text is cached between calls, so the part every question
     * shares (rules, policies, FAQ) belongs there and the per-question data in $user.
     *
     * @param  array<string, mixed>  $schema  JSON schema the answer must follow
     * @param  int  $retries  how often an overloaded or failed call is tried again; 0 when nobody waits on it
     * @param  string|null  $model  a model to try instead of the one chosen in the settings
     *
     * @throws AssistantUnavailable
     */
    public function ask(string $system, string $user, array $schema, int $retries = 1, ?string $model = null): ClaudeAnswer
    {
        $key = (string) config('services.anthropic.key');

        if ($key === '') {
            throw new AssistantUnavailable('ANTHROPIC_API_KEY is not set.');
        }

        try {
            $message = $this->client($key, $retries)->messages->create(
                maxTokens: self::MAX_TOKENS,
                messages: [['role' => 'user', 'content' => $user]],
                model: $model ?? $this->settings->assistantModel(),
                outputConfig: ['effort' => self::EFFORT, 'format' => ['type' => 'json_schema', 'schema' => $schema]],
                system: [['type' => 'text', 'text' => $system, 'cacheControl' => ['type' => 'ephemeral']]],
            );
        } catch (AnthropicException $e) {
            throw new AssistantUnavailable('Claude did not answer: '.$e->getMessage(), 0, $e);
        }

        if ($message->stopReason !== 'end_turn') {
            throw new AssistantUnavailable('Claude stopped early: '.$message->stopReason.'.');
        }

        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                $answer = json_decode($block->text, true);

                if (is_array($answer)) {
                    return new ClaudeAnswer(
                        $answer,
                        $message->model,
                        $message->usage->inputTokens,
                        $message->usage->outputTokens,
                        (int) $message->usage->cacheReadInputTokens,
                        (int) $message->usage->cacheCreationInputTokens,
                    );
                }
            }
        }

        throw new AssistantUnavailable('Claude returned no JSON answer.');
    }

    private function client(string $key, int $retries): Client
    {
        return new Client(
            apiKey: $key,
            requestOptions: [
                'maxRetries' => $retries,
                'maxRetryDelay' => self::MAX_RETRY_DELAY_SECONDS,
                'transporter' => $this->transporter ?? new Guzzle([
                    'timeout' => self::TIMEOUT_SECONDS,
                    'connect_timeout' => self::CONNECT_TIMEOUT_SECONDS,
                ]),
            ],
        );
    }
}
