<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ProductModel as Product;

class CategoryModel extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = "category";
    protected $primaryKey = "cate_id";
    public $timestamps = true;
    protected $fillable = [
        'cate_id',
        'cate_name',
        'cate_slug',
        'cate_sort',
        'cate_img',
        'cate_hidden',
        'cate_meta_keywords',
        'cate_parent_id',
    ];
    protected $attributes = [
        'cate_hidden' => 1,
    ];

    public function getChildCate() {
        return $this->hasMany(CategoryModel::class, 'cate_parent_id', 'cate_id');
    }

    public function getProductsInCate() {
        return $this->hasMany(Product::class, 'cate_id', 'cate_id');
    }

    public function getParentCate(){
        return $this->belongsTo(CategoryModel::class, 'cate_parent_id', 'cate_id');
    }
}
