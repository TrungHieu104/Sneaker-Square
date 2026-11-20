<?php

namespace App\Services\Assistant;

use App\Enums\AssistantIntent;

/**
 * What Laravel found for one question, before the model phrases it.
 *
 * facts are the only thing the model is shown. fallback is a sentence built
 * from the same facts, used when the model is not asked or not reachable.
 * actions and card are drawn by the widget as they are; the model never
 * writes them. Facts cite no source: only a page the storefront publishes is
 * shown as one.
 */
final class ReplyDraft
{
    /**
     * @param  array<int, string>  $facts
     * @param  array<int, array<string, mixed>>  $actions
     * @param  array<string, mixed>|null  $card
     */
    public function __construct(
        public readonly AssistantIntent $intent,
        public readonly string $fallback,
        public readonly array $facts = [],
        public readonly array $actions = [],
        public readonly ?array $card = null,
        public readonly bool $askModel = true,
    ) {}

    /**
     * An answer that needs no model: a refusal, a missing order, a question
     * without the page it depends on.
     *
     * @param  array<int, array<string, mixed>>  $actions
     */
    public static function fixed(AssistantIntent $intent, string $text, array $actions = []): self
    {
        return new self($intent, $text, actions: $actions, askModel: false);
    }

    /**
     * @param  array<int, string>  $facts
     */
    public function withMore(array $facts): self
    {
        return new self(
            $this->intent,
            $this->fallback,
            array_merge($this->facts, $facts),
            $this->actions,
            $this->card,
            $this->askModel,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function link(string $label, string $url, bool $primary = false): array
    {
        return ['label' => $label, 'kind' => 'link', 'value' => $url, 'primary' => $primary];
    }

    /**
     * @return array<string, mixed>
     */
    public static function handoff(bool $primary = false): array
    {
        return ['label' => $primary ? 'Chuyển cho nhân viên' : 'Gặp nhân viên', 'kind' => 'handoff', 'value' => null, 'primary' => $primary];
    }
}
