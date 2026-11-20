<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One chat between a shopper and the support assistant. A guest's chat is
 * found by the visitor cookie (session_id) and moves to their account when
 * they log in; a closed chat is never reopened, the next question starts a
 * new one.
 */
class AssistantConversationModel extends Model
{
    public const BOT = 'bot';

    public const WAITING_STAFF = 'waiting_staff';

    public const ANSWERED = 'answered';

    public const CLOSED = 'closed';

    protected $table = 'assistant_conversations';

    protected $fillable = [
        'user_id', 'session_id', 'status', 'pro_id', 'handed_off_at', 'customer_seen_at', 'last_message_at',
    ];

    protected $casts = [
        'handed_off_at' => 'datetime',
        'customer_seen_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    /** @return BelongsTo<UserModel, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id', 'user_id');
    }

    /** @return BelongsTo<ProductModel, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'pro_id', 'pro_id');
    }

    /** @return HasMany<AssistantMessageModel, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessageModel::class, 'conversation_id')->orderBy('id');
    }

    public function isWithStaff(): bool
    {
        return in_array($this->status, [self::WAITING_STAFF, self::ANSWERED], true);
    }
}
