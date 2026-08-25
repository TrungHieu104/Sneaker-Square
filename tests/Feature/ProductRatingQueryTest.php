<?php

namespace Tests\Feature;

use App\Models\CommentModel;
use App\Models\ProductModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Covers the N+1 in the star ratings.
 *
 * getAverageRating() and getReviewCount() each ran their own query, and the
 * listing templates call both for every product card. The home page alone
 * lists products in four blocks, so drawing stars cost dozens of round trips.
 */
class ProductRatingQueryTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->userId = $this->makeUser()->user_id;
    }

    private function makeRatedProducts(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $product = $this->makeProduct(slug: 'giay-' . $i);

            foreach ([5, 4, 3] as $rating) {
                CommentModel::create([
                    'comment_content' => 'Một nhận xét đủ dài để qua vòng kiểm tra.',
                    'comment_date' => now(),
                    'pro_id' => $product->pro_id,
                    'user_id' => $this->userId,
                    'comment_name' => 'Người mua',
                    'comment_email' => 'nguoimua@example.test',
                    'rating' => $rating,
                    'comment_hidden' => 1,
                ]);
            }
        }
    }

    /**
     * @return array{queries: int, ratings: array<int, float>, counts: array<int, int>}
     */
    private function drawProductCards(bool $withScope): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $products = $withScope
            ? ProductModel::withRatingSummary()->get()
            : ProductModel::query()->get();

        $ratings = [];
        $counts = [];

        foreach ($products as $product) {
            $ratings[] = $product->getAverageRating();
            $counts[] = $product->getReviewCount();
        }

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return ['queries' => $queries, 'ratings' => $ratings, 'counts' => $counts];
    }

    public function test_danh_sach_san_pham_chi_ton_mot_truy_van_khi_dung_scope(): void
    {
        $this->makeRatedProducts(5);

        $result = $this->drawProductCards(withScope: true);

        $this->assertSame(1, $result['queries'], 'Toàn bộ điểm đánh giá phải nằm trong chính câu truy vấn danh sách');
    }

    public function test_khong_dung_scope_thi_moi_san_pham_ton_them_hai_truy_van(): void
    {
        $this->makeRatedProducts(5);

        $result = $this->drawProductCards(withScope: false);

        // 1 for the listing + 2 per product. This is the behaviour the scope
        // exists to avoid; the test states the cost so a regression is visible.
        $this->assertSame(11, $result['queries']);
    }

    public function test_scope_tra_ve_dung_diem_va_dung_so_luot_danh_gia(): void
    {
        $this->makeRatedProducts(2);

        $withScope = $this->drawProductCards(withScope: true);
        $withoutScope = $this->drawProductCards(withScope: false);

        $this->assertSame([4.0, 4.0], $withScope['ratings']);
        $this->assertSame([3, 3], $withScope['counts']);
        $this->assertSame($withoutScope['ratings'], $withScope['ratings'], 'Hai đường tính phải cho cùng kết quả');
        $this->assertSame($withoutScope['counts'], $withScope['counts']);
    }

    public function test_san_pham_chua_ai_danh_gia_van_tra_ve_khong(): void
    {
        $this->makeProduct(slug: 'giay-chua-danh-gia');

        $product = ProductModel::withRatingSummary()->first();

        $this->assertSame(0.0, $product->getAverageRating());
        $this->assertSame(0, $product->getReviewCount());
    }
}
