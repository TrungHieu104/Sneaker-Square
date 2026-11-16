<?php

namespace App\Models;

use App\Enums\PageType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A content page written in the admin editor: a policy or the about page.
 */
class PageModel extends Model
{
    use SoftDeletes;

    protected $table = 'pages';

    public $primaryKey = 'page_id';

    protected $fillable = ['page_type', 'page_name', 'page_description', 'page_content', 'page_hidden', 'page_slug', 'page_meta_keywords', 'page_meta_description', 'page_created_by', 'page_updated_by'];

    protected $casts = ['page_type' => PageType::class];

    /**
     * @param  Builder<PageModel>  $query
     * @return Builder<PageModel>
     */
    public function scopePolicies(Builder $query): Builder
    {
        return $query->where('page_type', PageType::Policy->value);
    }

    /**
     * @param  Builder<PageModel>  $query
     * @return Builder<PageModel>
     */
    public function scopeAbout(Builder $query): Builder
    {
        return $query->where('page_type', PageType::About->value);
    }

    /**
     * @param  Builder<PageModel>  $query
     * @return Builder<PageModel>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('page_hidden', 1);
    }
}
