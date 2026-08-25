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
 * Guards against N+1 by measuring, not by reading the code.
 *
 * Each test renders the same page twice with different amounts of data and
 * asserts the query count did not move. A relation that gets lazy-loaded inside
 * a loop makes the second render cost more, and the test says so.
 */
class PageQueryCountTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        // The response cache would serve the second render from memory and hide
        // exactly what these tests are looking for.
        config(['responsecache.enabled' => false]);
    }

    private function queriesFor(string $url): int
    {
        // One untimed render first. The very first request of a process pays
        // for things that are then cached — the permission table, for one — and
        // counting that would make the two measurements incomparable.
        $this->get($url);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get($url)->assertOk();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function makeProducts(int $count, string $prefix): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->makeProduct(slug: $prefix . '-' . $i);
        }
    }

    private function addReviews(ProductModel $product, int $count): void
    {
        $reviewer = UserModel::first() ?? $this->makeUser();

        for ($i = 1; $i <= $count; $i++) {
            CommentModel::create([
                'comment_content' => 'Một nhận xét đủ dài để qua vòng kiểm tra.',
                'comment_date' => now(),
                'pro_id' => $product->pro_id,
                'user_id' => $reviewer->user_id,
                'comment_name' => 'Người mua ' . $i,
                'comment_email' => 'nguoimua@example.test',
                'rating' => 4,
                'comment_hidden' => 1,
            ]);
        }
    }

    public function test_trang_danh_sach_san_pham_khong_ton_them_truy_van_khi_them_san_pham(): void
    {
        $this->makeProducts(2, 'giay');
        $withTwo = $this->queriesFor('/san-pham');

        $this->makeProducts(6, 'giay-them');
        $withEight = $this->queriesFor('/san-pham');

        $this->assertSame(
            $withTwo,
            $withEight,
            "Số truy vấn phải không đổi khi số sản phẩm tăng (2 sản phẩm: $withTwo, 8 sản phẩm: $withEight)"
        );
    }

    public function test_trang_chi_tiet_khong_ton_them_truy_van_khi_them_danh_gia(): void
    {
        $this->makeProducts(3, 'giay');
        $product = ProductModel::where('pro_slug', 'giay-1')->firstOrFail();
        $this->makeUser();

        $this->addReviews($product, 1);
        $withOne = $this->queriesFor('/san-pham/giay-1');

        $this->addReviews($product, 7);
        $withEight = $this->queriesFor('/san-pham/giay-1');

        $this->assertSame(
            $withOne,
            $withEight,
            "Số truy vấn phải không đổi khi số đánh giá tăng (1 đánh giá: $withOne, 8 đánh giá: $withEight)"
        );
    }

    /**
     * A ceiling as well as a slope.
     *
     * The tests above catch a cost that grows with the data. They cannot catch
     * a fixed cost paid once per view rendered — which is what the view
     * composer registered on '*' was, four queries every time any template was
     * drawn. This one would.
     */
    public function test_trang_danh_sach_nam_trong_nguong_truy_van(): void
    {
        $this->makeProducts(8, 'giay');

        $queries = $this->queriesFor('/san-pham');

        $this->assertLessThanOrEqual(
            15,
            $queries,
            "Trang danh sách sản phẩm dùng $queries truy vấn, vượt ngưỡng 15"
        );
    }

    public function test_them_danh_muc_khong_lam_tang_truy_van_o_thanh_ben(): void
    {
        $this->makeProducts(3, 'giay');
        $baseline = $this->queriesFor('/san-pham');

        for ($i = 2; $i <= 6; $i++) {
            DB::table('category')->insert([
                'cate_id' => $i,
                'cate_name' => 'Danh mục ' . $i,
                'cate_slug' => 'danh-muc-' . $i,
                'cate_sort' => $i,
                'cate_hidden' => 1,
                'cate_parent_id' => 1,
            ]);
        }

        $this->assertSame(
            $baseline,
            $this->queriesFor('/san-pham'),
            'Thanh bên đếm số sản phẩm mỗi danh mục bằng SQL, không nạp từng sản phẩm'
        );
    }
}
