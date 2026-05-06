<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\NewsModel as News;
use Illuminate\Database\Eloquent\SoftDeletes;


class TagsModel extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = "tags";
    protected $primaryKey = "tag_id";
    public $timestamp = true;
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'tag_id',
        'tag_content',
        'tag_slug',
        'tag_hidden',
    ];

    protected $attributes = [
        'tag_hidden'=> 1,
    ];
    public function getNews(){
        return $this->belongsToMany(News::class, 'news_by_tags', 'tag_id','news_id');
    }
    
}
