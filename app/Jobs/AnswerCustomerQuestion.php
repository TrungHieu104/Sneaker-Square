<?php

namespace App\Jobs;

use App\Models\AssistantMessageModel as Message;
use App\Models\ProductModel;
use App\Services\Assistant\ReplyDraft;
use App\Services\Assistant\SupportAssistant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Answers one customer message off the web request. The widget polls until
 * the reply is written, so a failure must still leave a reply behind.
 */
class AnswerCustomerQuestion implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    // A retry would answer a question the customer may already have asked again.
    public int $tries = 1;

    // Below the redis retry_after (90s), so a slow job is never handed to a second worker.
    public int $timeout = 75;

    // Fail at once, so failed() leaves the busy notice while the widget still waits.
    public bool $failOnTimeout = true;

    /**
     * @param  int|null  $measuredSize  the size the customer measured, read in their request: the worker has no session
     */
    public function __construct(
        public readonly int $messageId,
        public readonly ?int $productId,
        public readonly ?int $measuredSize = null,
    ) {}

    public function handle(SupportAssistant $assistant): void
    {
        $asked = Message::find($this->messageId);

        if ($asked === null) {
            return;
        }

        $page = $this->productId === null ? null : ProductModel::where('pro_hidden', 1)->find($this->productId);

        $assistant->reply($asked, $page, $this->measuredSize);
    }

    public function failed(Throwable $e): void
    {
        $asked = Message::with('conversation')->find($this->messageId);

        // Staff took the chat, or the bot already answered before the failure.
        if ($asked === null
            || $asked->conversation?->isWithStaff()
            || Message::where('conversation_id', $asked->conversation_id)->where('id', '>', $asked->id)->where('sender', Message::BOT)->exists()) {
            return;
        }

        Message::create([
            'conversation_id' => $asked->conversation_id,
            'sender' => Message::BOT,
            'body' => SupportAssistant::BUSY,
            'meta' => ['actions' => [ReplyDraft::handoff()]],
            'created_at' => now(),
        ]);
    }
}
