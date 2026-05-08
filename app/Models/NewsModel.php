<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CateNewsModel as CateNews;
use App\Models\TagsModel as Tags;
use App\Models\NewsByTagsModel as NewsByTags;
use App\Models\UserModel as User;
use Illuminate\Database\Eloquent\SoftDeletes;


class NewsModel extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = "news";
    protected $primaryKey = "news_id";
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
        'news_hidden'=>1,
        'views'=>0,
        'news_hot'=>0,
    ];

    public function getCateNews(){
        return $this->belongsTo(CateNews::class, 'cate_news_id', 'cate_news_id');
    }

    public function getTags(){
        return $this->belongsToMany(Tags::class, 'news_by_tags', 'news_id','tag_id');
    }

    public function getUser(){
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
