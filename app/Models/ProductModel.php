<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CategoryModel as Category;
use App\Models\ImageModel as Image;
use App\Models\CommentModel as Comment;
use App\Models\ColorModel as Color;
use App\Models\SizeModel as Size;
use App\Models\OrderDetailModel as OrderDetail;

class ProductModel extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = "products";
    protected $primaryKey = "pro_id";
    public $timestamps = true;
    protected $fillable = [
        'pro_id',
        'pro_name',
        'pro_slug',
        'pro_code',
        'pro_price',
        'pro_price_sale',
        'capital_price',
        'pro_img',
        'pro_description',
        'pro_SEO_title',
        'pro_views',
        'pro_hot',
        'pro_hidden',
        'pro_meta_keywords',
        'pro_meta_description',
        'pro_date',
        'cate_id',
    ];
    protected $attributes = [
        'pro_price_sale' => 0,
        'pro_views' => 0,
        'pro_hot' => 0,
        'pro_hidden' => 1,
    ];

    public function getCate() {
        return $this->belongsTo(Category::class, 'cate_id', 'cate_id');
    }

    public function getImages(){
        return $this->hasMany(Image::class, 'pro_id', 'pro_id');
    }

    public function getComments() {
        return $this->hasMany(Comment::class, 'pro_id', 'pro_id');
    }

    public function getColor() {
        return $this->belongsToMany(Color::class, 'products_quantity', 'pro_id', 'color_id');
    }

    public function getSize() {
        return $this->belongsToMany(Size::class, 'products_quantity', 'pro_id', 'size_id');
    }
    
    public function soldProduct() {
        return $this->belongsTo(OrderDetail::class, 'pro_id', 'pro_id');
    }
}
