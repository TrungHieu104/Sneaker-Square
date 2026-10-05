<?php

namespace App\Models;

use App\Models\ProductModel as Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'category';

    protected $primaryKey = 'cate_id';

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

    /** @return HasMany<CategoryModel, $this> */
    public function getChildCate(): HasMany
    {
        return $this->hasMany(CategoryModel::class, 'cate_parent_id', 'cate_id');
    }

    /** @return HasMany<Product, $this> */
    public function getProductsInCate(): HasMany
    {
        return $this->hasMany(Product::class, 'cate_id', 'cate_id');
    }

    /** @return BelongsTo<CategoryModel, $this> */
    public function getParentCate(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'cate_parent_id', 'cate_id');
    }
}
