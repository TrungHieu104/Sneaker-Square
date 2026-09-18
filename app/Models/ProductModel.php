<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CategoryModel as Category;
use App\Models\ImageModel as Image;
use App\Models\CommentModel as Comment;
use App\Models\ColorModel as Color;
use App\Models\SizeModel as Size;
use App\Models\OrderDetailModel as OrderDetail;
use App\Models\ProductQuantityModel as Quantity;
use Illuminate\Database\Query\Expression;

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
        'pro_weight',
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

    public function getQuantities() {
        return $this->hasMany(Quantity::class, 'pro_id', 'pro_id');
    }

    public function variantFor($colorId, $sizeId): ?Quantity
    {
        return $this->getQuantities()
            ->where('color_id', $colorId)
            ->where('size_id', $sizeId)
            ->first();
    }

    public function sellingPrice(): int
    {
        return (int) ($this->pro_price_sale != 0 ? $this->pro_price_sale : $this->pro_price);
    }
    
    public function soldProduct() {
        return $this->belongsTo(OrderDetail::class, 'pro_id', 'pro_id');
    }

    /**
     * Loads the rating figures alongside the products in one query.
     *
     * Without it every product card runs its own AVG and COUNT.
     */
    public function scopeWithRatingSummary(Builder $query): Builder
    {
        return $query
            ->withAvg(
                ['getComments as average_rating' => fn (Builder $comments) => $comments
                    ->where('comment_hidden', 1)
                    ->whereNotNull('rating')],
                'rating'
            )
            ->withCount(
                ['getComments as review_count' => fn (Builder $comments) => $comments
                    ->where('comment_hidden', 1)]
            );
    }

    /**
     * Average star rating across visible reviews, rounded to one decimal.
     *
     * Reviews written before the `rating` column existed have no score and stay
     * out of the average. Falls back to its own query only when the product was
     * fetched without withRatingSummary().
     */
    public function getAverageRating(): float
    {
        $average = array_key_exists('average_rating', $this->getAttributes())
            ? $this->getAttributes()['average_rating']
            : $this->getComments()
                ->where('comment_hidden', 1)
                ->whereNotNull('rating')
                ->avg('rating');

        return round((float) $average, 1);
    }

    public function getReviewCount(): int
    {
        if (array_key_exists('review_count', $this->getAttributes())) {
            return (int) $this->getAttributes()['review_count'];
        }

        return $this->getComments()->where('comment_hidden', 1)->count();
    }

    /**
     * Loads the variants' price range alongside the products in one query.
     *
     * A listing card prints a range, and asking each card's variants for it
     * costs the home page dozens of round trips.
     */
    public function scopeWithPriceRange(Builder $query): Builder
    {
        return $query
            ->withMin(['getQuantities as min_variant_price'], new Expression(self::sellingPriceSql()))
            ->withMax(['getQuantities as max_variant_price'], new Expression(self::sellingPriceSql()))
            ->withMin(['getQuantities as min_variant_list_price'], new Expression(self::listPriceSql()))
            ->withMax(['getQuantities as max_variant_list_price'], new Expression(self::listPriceSql()));
    }

    /**
     * Only the products that are actually discounted somewhere.
     *
     * `pro_price_sale != 0` alone does not answer this: a variant can be
     * discounted while the product row is not, and a variant that priced itself
     * takes no part in the product's discount.
     */
    public function scopeOnSale(Builder $query): Builder
    {
        return $query->where(fn (Builder $products) => $products
            // A variant discounted on its own terms.
            ->whereHas('getQuantities', fn (Builder $variants) => $variants
                ->whereNotNull('pro_price_sale')
                ->where('pro_price_sale', '!=', 0))
            // Or the product's own discount, as long as something still follows it.
            ->orWhere(fn (Builder $product) => $product
                ->where('pro_price_sale', '!=', 0)
                ->where(fn (Builder $reaches) => $reaches
                    ->whereHas('getQuantities', fn (Builder $variants) => $variants
                        ->whereNull('pro_price')
                        ->whereNull('pro_price_sale'))
                    ->orWhereDoesntHave('getQuantities'))));
    }

    /**
     * Orders by what the product actually sells for, cheapest variant first.
     *
     * Ordering by `products.pro_price` files a product listed at 100,000 whose
     * every variant sells at 120,000 among the 100,000 ones, contradicting the
     * range printed on its own card.
     */
    public function scopeOrderBySellingPrice(Builder $query, string $direction = 'asc'): Builder
    {
        $ascending = strtolower($direction) !== 'desc';
        $aggregate = $ascending ? 'MIN' : 'MAX';
        $product = self::productSellingPriceSql();

        // The outer COALESCE covers a product that has never been stocked, whose
        // subquery returns nothing at all.
        return $query->orderByRaw(
            "COALESCE((SELECT {$aggregate}(" . self::sellingPriceSql() . ')'
            . ' FROM products_quantity WHERE products_quantity.pro_id = products.pro_id)'
            . ", {$product}) " . ($ascending ? 'asc' : 'desc')
        );
    }

    /**
     * ProductQuantityModel::sellingPrice() written in SQL, correlated against the
     * `products` row so an inherited price resolves inside the query itself.
     */
    private static function sellingPriceSql(): string
    {
        return 'CASE'
            . ' WHEN products_quantity.pro_price_sale IS NULL AND products_quantity.pro_price IS NULL'
            . ' THEN ' . self::productSellingPriceSql()
            . ' WHEN products_quantity.pro_price_sale <> 0 THEN products_quantity.pro_price_sale'
            . ' ELSE COALESCE(products_quantity.pro_price, products.pro_price)'
            . ' END';
    }

    private static function listPriceSql(): string
    {
        return 'COALESCE(products_quantity.pro_price, products.pro_price)';
    }

    private static function productSellingPriceSql(): string
    {
        return 'CASE WHEN products.pro_price_sale <> 0 THEN products.pro_price_sale ELSE products.pro_price END';
    }

    /**
     * @return array{min: int, max: int}
     */
    public function priceRange(): array
    {
        return $this->rangeOf(
            'min_variant_price',
            'max_variant_price',
            fn (Quantity $variant) => $variant->sellingPrice($this),
            $this->sellingPrice(),
        );
    }

    /**
     * The range behind the struck-through figure shown during a sale.
     *
     * @return array{min: int, max: int}
     */
    public function listPriceRange(): array
    {
        return $this->rangeOf(
            'min_variant_list_price',
            'max_variant_list_price',
            fn (Quantity $variant) => $variant->listPrice($this),
            (int) $this->pro_price,
        );
    }

    public function isOnSale(): bool
    {
        $selling = $this->priceRange();
        $list = $this->listPriceRange();

        return $selling['min'] < $list['min'] || $selling['max'] < $list['max'];
    }

    public function displaySellingPrice(): string
    {
        return $this->formatRange($this->priceRange());
    }

    public function displayListPrice(): string
    {
        return $this->formatRange($this->listPriceRange());
    }

    /**
     * Collects the prices actually on offer and takes the two ends.
     *
     * Queries the variants itself only for a product fetched without
     * scopeWithPriceRange().
     *
     * @param  callable(Quantity): int  $priceOf
     * @return array{min: int, max: int}
     */
    private function rangeOf(string $minKey, string $maxKey, callable $priceOf, int $ownPrice): array
    {
        $attributes = $this->getAttributes();

        if (array_key_exists($minKey, $attributes)) {
            $prices = $attributes[$minKey] === null
                ? []
                : [(int) $attributes[$minKey], (int) $attributes[$maxKey]];
        } else {
            $prices = $this->getQuantities->map($priceOf)->all();
        }

        // Never stocked: only the product's own price is left.
        if ($prices === []) {
            $prices = [$ownPrice];
        }

        return ['min' => min($prices), 'max' => max($prices)];
    }

    /**
     * @param  array{min: int, max: int}  $range
     */
    private function formatRange(array $range): string
    {
        $min = number_format($range['min'], 0, ',', '.');

        return $range['min'] === $range['max']
            ? $min
            : $min . ' - ' . number_format($range['max'], 0, ',', '.');
    }
}
