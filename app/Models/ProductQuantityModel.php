<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductModel as Product;
use App\Models\SizeModel as Size;
use App\Models\ColorModel as Color;

/**
 * A row here is one variant: the triple (product, size, colour).
 *
 * Its three price columns are nullable, and NULL means "fall back to the
 * product's price" — so a single-price product leaves all of them empty and only
 * the variants that sell for something else carry a figure.
 */
class ProductQuantityModel extends Model
{
    use HasFactory;
    protected $table = "products_quantity";
    protected $primaryKey = 'quantity_id';
    public $timestamps = true;
    protected $fillable = [
        'quantity',
        'quantity_date',
        'pro_id',
        'size_id',
        'color_id',
        'pro_price',
        'pro_price_sale',
        'capital_price',
    ];

    protected $casts = [
        'pro_price' => 'integer',
        'pro_price_sale' => 'integer',
        'capital_price' => 'integer',
    ];

    public function getProducts() {
        return $this->belongsTo(Product::class, 'pro_id', 'pro_id');
    }

    public function getSize() {
        return $this->belongsTo(Size::class, 'size_id', 'size_id');
    }

    public function getColor() {
        return $this->belongsTo(Color::class, 'color_id', 'color_id');
    }

    /**
     * Pass `$product` when you already hold it, or the relation loads it again —
     * on the cart page that is one query per line.
     */
    public function listPrice(?Product $product = null): int
    {
        if ($this->pro_price !== null) {
            return (int) $this->pro_price;
        }

        return (int) ($this->resolveProduct($product)?->pro_price ?? 0);
    }

    /**
     * The variant's sale price; 0 means it is not on sale.
     *
     * The sale price travels with the list price rather than inheriting on its
     * own. Inherited separately, a product discounted 100,000 → 90,000 would drag
     * a variant selling at 120,000 down to 90,000 too — below its own list price.
     */
    public function salePrice(?Product $product = null): int
    {
        if ($this->pro_price_sale !== null) {
            return (int) $this->pro_price_sale;
        }

        if ($this->pro_price !== null) {
            return 0;
        }

        return (int) ($this->resolveProduct($product)?->pro_price_sale ?? 0);
    }

    public function capitalPrice(?Product $product = null): int
    {
        return (int) ($this->capital_price ?? $this->resolveProduct($product)?->capital_price ?? 0);
    }

    public function sellingPrice(?Product $product = null): int
    {
        $product = $this->resolveProduct($product);
        $sale = $this->salePrice($product);

        return $sale != 0 ? $sale : $this->listPrice($product);
    }

    private function resolveProduct(?Product $product): ?Product
    {
        return $product ?? $this->getProducts;
    }
}
