<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\NewsModel as News;
use Illuminate\Database\Eloquent\SoftDeletes;


class CateNewsModel extends Model
{
    use HasFactory , SoftDeletes;
    protected $table = "cate_news";
    protected $primaryKey = "cate_news_id";
    public $timestamp = true;
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'cate_news_id',
        'cate_news_name',
        'cate_news_slug',
        'cate_news_sort',
        'cate_new_img',
        'cate_news_hidden',
    ];

    protected $atttributes = [
        'cate_news_hidden' =>1,
    ];

    public function getNewsInCate(){
        return $this->hasMany(News::class, 'cate_news_id', 'cate_news_id');
    }
}
