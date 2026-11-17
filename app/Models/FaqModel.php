<?php

namespace App\Models;

use App\Models\Concerns\ClearsResponseCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One question and its answer on the storefront FAQ page.
 */
class FaqModel extends Model
{
    use ClearsResponseCache;

    protected $table = 'faqs';

    public $primaryKey = 'faq_id';

    protected $fillable = ['group_id', 'faq_question', 'faq_answer', 'faq_position', 'faq_hidden', 'faq_created_by', 'faq_updated_by'];

    /** @return BelongsTo<FaqGroupModel, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(FaqGroupModel::class, 'group_id', 'group_id');
    }

    /**
     * Where a question added without a position goes: last in its group.
     */
    public static function nextPosition(int $groupId): int
    {
        return (int) self::query()->where('group_id', $groupId)->max('faq_position') + 1;
    }
}
