<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An answer staff wrote for a question the support assistant could not
 * answer. Policies and the public FAQ are read from their own tables; these
 * are the only answers that live here, and customers never see them as a page.
 *
 * scope narrows an answer to one product or one category. message_id is the
 * staff reply in a chat it was saved from, so that reply is saved once.
 */
class AssistantKnowledgeModel extends Model
{
    public const SCOPE_ALL = 'all';

    public const SCOPE_CATEGORY = 'category';

    public const SCOPE_PRODUCT = 'product';

    protected $table = 'assistant_knowledge';

    protected $fillable = ['scope_type', 'scope_id', 'title', 'content', 'message_id', 'created_by'];

    protected $casts = [
        'scope_id' => 'integer',
        'message_id' => 'integer',
    ];
}
