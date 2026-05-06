<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class CateSlideModel extends Model
{
    use HasFactory, SoftDeletes;
    protected $table ="cate_slide"; 
    public $primaryKey = "cate_slide_id";
    protected $dates = ['deleted_at'];
    public $timestamps = true;  
    protected $fillable = ['cate_slide_name', 'cate_slide_slug','cate_slide_hidden'];
}
