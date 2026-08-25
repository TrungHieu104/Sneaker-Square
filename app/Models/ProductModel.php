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

    /**
     * Loads the rating figures alongside the products in one query.
     *
     * Without this, every product card on a listing page runs its own AVG and
     * its own COUNT — the home page alone lists products in four separate
     * blocks, so it was issuing dozens of round trips just to draw stars.
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
     * Reviews written before the `rating` column existed have no score and are
     * excluded from the average. Returns 0.0 when nothing has been rated yet.
     *
     * Uses the figure withRatingSummary() already loaded when there is one, and
     * only falls back to its own query for a product fetched without the scope.
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
}
