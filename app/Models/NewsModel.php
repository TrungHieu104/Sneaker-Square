<?php

namespace App\Models;

use App\Models\CateNewsModel as CateNews;
use App\Models\TagsModel as Tags;
use App\Models\UserModel as User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'news';

    protected $primaryKey = 'news_id';

    public $timestamp = true;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'news_id',
        'news_title',
        'news_slug',
        'news_summarize',
        'news_content',
        'news_hidden',
        'news_img',
        'news_SEO_title',
        'news_meta_keywords',
        'news_meta_description',
        'views',
        'news_hot',
        'post_date',
        'news_created_by',
        'cate_news_id',
        'user_id',
    ];

    protected $attributes = [
        'news_hidden' => 1,
        'views' => 0,
        'news_hot' => 0,
    ];

    /** @return BelongsTo<CateNews, $this> */
    public function getCateNews(): BelongsTo
    {
        return $this->belongsTo(CateNews::class, 'cate_news_id', 'cate_news_id');
    }

    /** @return BelongsToMany<Tags, $this> */
    public function getTags(): BelongsToMany
    {
        return $this->belongsToMany(Tags::class, 'news_by_tags', 'news_id', 'tag_id');
    }

    /** @return BelongsTo<User, $this> */
    public function getUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
