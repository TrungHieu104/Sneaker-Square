<?php

namespace App\Models;

use App\Models\ProductModel as Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImageModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'image';

    protected $primaryKey = 'img_id';

    public $timestamps = true;

    protected $fillable = [
        'img_id',
        'img_name',
        'img_hidden',
        'pro_id',
    ];

    protected $attributes = [
        'img_hidden' => 1,
    ];

    /** @return BelongsTo<Product, $this> */
    public function getProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'pro_id', 'pro_id');
    }
}
