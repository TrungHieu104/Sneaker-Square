<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PromotionModel extends Model
{
    use HasFactory, SoftDeletes;
    protected $table ="promotion"; 
    public $primaryKey = "promotion_id";
    protected $dates = ['deleted_at'];
    public $timestamps = true;  
    protected $fillable = ['type', 'promotion_name','promotion_img','promotion_link','promotion_hidden','promotion_sort','promotion_content','promotion_date',
                            'promotion_start','promotion_end','promotion_note','cate_slide_id'];
}

