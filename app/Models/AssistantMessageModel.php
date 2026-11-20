<?php

namespace App\Models;

use App\Services\Assistant\ChatSignal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a support chat. meta carries what the widget draws around a
 * bot answer: the sources it was built from, the buttons under it and the
 * order card, all produced by Laravel rather than by the model.
 */
class AssistantMessageModel extends Model
{
    public const CUSTOMER = 'customer';

    public const BOT = 'bot';

    public const STAFF = 'staff';

    public const SYSTEM = 'system';

    public const UPDATED_AT = null;

    protected $table = 'assistant_messages';

    protected $fillable = ['conversation_id', 'sender', 'body', 'intent', 'meta', 'staff_id', 'created_at'];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(fn (self $message) => app(ChatSignal::class)->changed((int) $message->conversation_id));
    }

    /** @return BelongsTo<AssistantConversationModel, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AssistantConversationModel::class, 'conversation_id');
    }

    /** @return BelongsTo<UserModel, $this> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'staff_id', 'user_id');
    }

    /**
     * The shape the storefront widget reads. Sources are looked up in $pages
     * now rather than saved with the message, so a renamed policy slug still links.
     *
     * @param  array<string, string>  $pages  page url by source title
     * @return array<string, mixed>
     */
    public function toWidget(array $pages = []): array
    {
        return [
            'id' => $this->id,
            'sender' => $this->sender,
            'body' => $this->body,
            // A source is a page the storefront publishes; a staff answer or
            // a hidden page has none to open, so it is not shown.
            'sources' => array_values(array_map(
                fn (string $title) => ['title' => $title, 'url' => $pages[$title]],
                array_filter($this->meta['sources'] ?? [], fn (string $title) => isset($pages[$title])),
            )),
            'actions' => $this->meta['actions'] ?? [],
            'card' => $this->meta['card'] ?? null,
            'at' => $this->created_at?->format('H:i'),
        ];
    }
}
