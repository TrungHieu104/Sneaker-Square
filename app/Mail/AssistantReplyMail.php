<?php

namespace App\Mail;

use App\Models\AssistantMessageModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A staff answer in a support chat, for a customer who left before it came.
 */
class AssistantReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public AssistantMessageModel $reply) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Sneaker Square đã trả lời câu hỏi của bạn');
    }

    public function content(): Content
    {
        $conversation = $this->reply->conversation;
        $question = $conversation->messages()->where('sender', AssistantMessageModel::CUSTOMER)
            ->where('id', '<', $this->reply->id)
            ->latest('id')
            ->value('body');

        return new Content(
            view: 'mail.assistantReply',
            with: [
                'name' => $conversation->user?->name,
                'question' => $question,
                'answer' => $this->reply->body,
                'shopUrl' => route('home.page'),
            ],
        );
    }
}
