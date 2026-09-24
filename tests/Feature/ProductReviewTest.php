<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\CommentModel;
use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the star-rating fix.
 *
 * The feature was fully built — star picker in the form, validation rule, model
 * assignment — and create_comments_table declares the column, but no migration
 * ever added it to databases created before that edit. On those, every review
 * submission failed with "Unknown column 'rating' in 'field list'".
 */
class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userId = UserModel::create([
            'name' => 'Reviewer',
            'email' => 'reviewer@gmail.com',
            'password' => bcrypt('secret-password'),
            'user_status' => 1,
            'user_role' => 0,
        ])->user_id;
    }

    private function makeProduct(string $slug = 'nike-air'): ProductModel
    {
        DB::table('category')->insertOrIgnore([
            'cate_id' => 1, 'cate_name' => 'Giày thể thao', 'cate_slug' => 'giay-the-thao',
            'cate_sort' => 1, 'cate_hidden' => 1,
        ]);

        return ProductModel::create([
            'pro_name' => 'Product ' . $slug,
            'pro_slug' => $slug,
            'pro_code' => 'SKU-' . $slug,
            'pro_price' => 1_000_000,
            'capital_price' => 600_000,
            'pro_img' => $slug . '.jpg',
            'pro_date' => now()->toDateString(),
            'pro_hidden' => 1,
            'cate_id' => 1,
        ]);
    }

    private function addReview(ProductModel $product, ?int $rating, int $hidden = 1): CommentModel
    {
        return CommentModel::create([
            'comment_content' => 'A review body long enough to pass validation.',
            'comment_date' => now(),
            'pro_id' => $product->pro_id,
            'user_id' => $this->userId,
            'comment_name' => 'Reviewer',
            'comment_email' => 'reviewer@gmail.com',
            'rating' => $rating,
            'comment_hidden' => $hidden,
        ]);
    }

    public function test_a_review_can_store_a_star_rating(): void
    {
        $product = $this->makeProduct();

        $review = $this->addReview($product, 5);

        $this->assertSame(5, (int) $review->fresh()->rating);
    }

    public function test_average_rating_is_zero_when_nothing_has_been_rated(): void
    {
        $product = $this->makeProduct();

        $this->assertSame(0.0, $product->getAverageRating());
    }

    public function test_average_rating_ignores_reviews_written_before_the_column_existed(): void
    {
        $product = $this->makeProduct();

        // Legacy rows carry no score and must not drag the average down.
        $this->addReview($product, null);
        $this->addReview($product, null);
        $this->addReview($product, 5);
        $this->addReview($product, 4);
        $this->addReview($product, 4);

        $this->assertSame(4.3, $product->getAverageRating());
        $this->assertSame(5, $product->getReviewCount(), 'The count covers every visible review');
    }

    /**
     * Records a completed order for this product, which is what earns the right
     * to review it.
     */
    private function recordCompletedPurchase(ProductModel $product, int $userId): void
    {
        $order = OrderModel::create([
            'order_code' => 'DH' . random_int(100000, 999999),
            'order_name' => 'Reviewer',
            'order_email' => 'reviewer@gmail.com',
            'order_address' => '1 Võ Văn Ngân',
            'order_local' => 'Thủ Đức, TP.HCM',
            'order_phone' => '0912345678',
            'order_delivery_fee' => 30000,
            'order_coupon_value' => 0,
            'order_total' => 1_030_000,
            'order_payment' => 'cod',
            'order_payment_status' => 1,
            'order_date' => now()->toDateString(),
            'order_status' => OrderStatus::Completed,
            'user_id' => $userId,
        ]);

        OrderDetailModel::create([
            'order_id' => $order->order_id,
            'pro_id' => $product->pro_id,
            'pro_name' => $product->pro_name,
            'price' => 1_000_000,
            'quantity' => 1,
        ]);
    }

    public function test_a_customer_who_received_the_product_can_review_it(): void
    {
        $product = $this->makeProduct('nike-da-mua');
        $this->recordCompletedPurchase($product, $this->userId);

        $this->actingAs(UserModel::find($this->userId))
            ->post(route('comments.store', $product->pro_id), [
                'comment_content' => 'Giày đi rất êm, đúng mô tả.',
                'rating' => 4,
            ])
            ->assertSessionHasNoErrors();

        $review = CommentModel::where('pro_id', $product->pro_id)->firstOrFail();

        $this->assertSame(4, (int) $review->rating);
        $this->assertSame($this->userId, (int) $review->user_id);
    }

    public function test_a_customer_who_never_bought_it_cannot_review_it(): void
    {
        $product = $this->makeProduct('nike-chua-mua');

        $this->actingAs(UserModel::find($this->userId))
            ->post(route('comments.store', $product->pro_id), [
                'comment_content' => 'Giày đi rất êm, đúng mô tả.',
                'rating' => 5,
            ]);

        $this->assertSame(0, CommentModel::where('pro_id', $product->pro_id)->count());
    }

    public function test_a_guest_cannot_review(): void
    {
        $product = $this->makeProduct('nike-khach-vang-lai');

        $this->post(route('comments.store', $product->pro_id), [
            'comment_content' => 'Giày đi rất êm, đúng mô tả.',
            'rating' => 5,
        ]);

        $this->assertSame(0, CommentModel::where('pro_id', $product->pro_id)->count());
    }

    public function test_a_rating_outside_one_to_five_is_rejected(): void
    {
        $product = $this->makeProduct('nike-sao-la');
        $this->recordCompletedPurchase($product, $this->userId);

        $this->actingAs(UserModel::find($this->userId))
            ->post(route('comments.store', $product->pro_id), [
                'comment_content' => 'Giày đi rất êm, đúng mô tả.',
                'rating' => 9,
            ])
            ->assertSessionHasErrors('rating');

        $this->assertSame(0, CommentModel::where('pro_id', $product->pro_id)->count());
    }

    public function test_hidden_reviews_are_excluded_from_the_average(): void
    {
        $product = $this->makeProduct();

        $this->addReview($product, 5);
        $this->addReview($product, 1, hidden: 0);

        $this->assertSame(5.0, $product->getAverageRating());
        $this->assertSame(1, $product->getReviewCount());
    }
}
