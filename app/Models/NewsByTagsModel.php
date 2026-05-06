<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsByTagsModel extends Model
{
    use HasFactory;
    protected $table = "news_by_tags";
    public $timestamp = true;
    protected $fillable = [
        'news_id',
        'tag_id',
    ];

}
