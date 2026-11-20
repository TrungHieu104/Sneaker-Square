<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A question the support assistant answered badly: it had no data, or the
 * customer asked for a person straight after its answer. Questions asking
 * the same thing share a group_id, the id of the first question of the
 * group, so staff answer the group once.
 */
class AssistantUnansweredModel extends Model
{
    public const ANSWERED = 'answered';

    public const DISMISSED = 'dismissed';

    public const NO_DATA = 'no_data';

    public const UNHAPPY = 'unhappy';

    public const UPDATED_AT = null;

    protected $table = 'assistant_unanswered';

    protected $fillable = [
        'conversation_id', 'message_id', 'question', 'intent', 'reason', 'topic', 'pro_id', 'group_id',
        'resolution', 'resolved_at', 'knowledge_id', 'created_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * The question a staff reply in a chat answers: the latest one logged in
     * that chat after staff last replied and before this reply. Whether it is
     * resolved does not matter; an older question, answered or not, belongs
     * to an earlier reply.
     */
    public static function answeredBy(AssistantMessageModel $reply): ?self
    {
        $previous = AssistantMessageModel::where('conversation_id', $reply->conversation_id)
            ->where('sender', AssistantMessageModel::STAFF)
            ->where('id', '<', $reply->id)
            ->max('id');

        return self::where('conversation_id', $reply->conversation_id)
            ->where('message_id', '<', $reply->id)
            ->where('message_id', '>', (int) $previous)
            ->latest('message_id')
            ->first();
    }

    /** @return BelongsTo<ProductModel, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'pro_id', 'pro_id');
    }
}
