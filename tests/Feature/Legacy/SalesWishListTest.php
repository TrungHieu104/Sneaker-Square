<?php

namespace Tests\Feature\Legacy;

use App\Models\ProductModel;
use App\Models\UserModel;
use App\Models\WishListModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterisation of the storefront wishlist, pinned before a refactor.
 */
class SalesWishListTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const EMPTY_LIST = 'Chưa có sản phẩm...';

    private UserModel $customer;

    private UserModel $otherCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->customer = $this->makeUser();
        $this->otherCustomer = $this->makeUser(email: 'khac@example.test', username: 'khachkhac');
    }

    // --------------------------------------------------------------- page

    public function test_khach_chua_dang_nhap_thay_danh_sach_trong(): void
    {
        $this->like($this->otherCustomer, $this->makeProduct(slug: 'giay-cua-nguoi-khac'));

        $this->get(route('product.wishlist'))
            ->assertOk()
            ->assertSee(self::EMPTY_LIST)
            ->assertDontSee('Sản Phẩm Giay-cua-nguoi-khac');
    }

    public function test_trang_yeu_thich_chi_hien_san_pham_dang_ban_cua_chinh_khach(): void
    {
        $mine = $this->makeProduct(slug: 'giay-cua-toi');
        $hidden = $this->makeProduct(slug: 'giay-da-an');
        $hidden->pro_hidden = 0;
        $hidden->save();
        $theirs = $this->makeProduct(slug: 'giay-cua-ban');
        $this->like($this->customer, $mine);
        $this->like($this->customer, $hidden);
        $this->like($this->otherCustomer, $theirs);

        $this->actingAs($this->customer)->get(route('product.wishlist'))
            ->assertOk()
            ->assertSee('Sản phẩm yêu thích')
            ->assertSee('Sản Phẩm Giay-cua-toi')
            ->assertDontSee('Sản Phẩm Giay-da-an')
            ->assertDontSee('Sản Phẩm Giay-cua-ban');
    }

    public function test_khach_chua_co_san_pham_yeu_thich_thay_thong_bao_trong(): void
    {
        $this->actingAs($this->customer)->get(route('product.wishlist'))
            ->assertOk()
            ->assertSee(self::EMPTY_LIST);
    }

    // ------------------------------------------------------------- toggle

    public function test_bam_tim_lan_dau_them_lan_hai_bo_khoi_yeu_thich(): void
    {
        $product = $this->makeProduct(slug: 'giay-tim');

        $this->actingAs($this->customer)->post(route('product.wishlist_store'), ['product-id' => $product->pro_id])
            ->assertOk()
            ->assertExactJson(['status' => 'Thêm mục yêu thích thành công', 'icon' => 'success']);
        $this->assertDatabaseHas('like', ['user_id' => $this->customer->user_id, 'pro_id' => $product->pro_id]);

        $this->actingAs($this->customer)->post(route('product.wishlist_store'), ['product-id' => $product->pro_id])
            ->assertOk()
            ->assertExactJson(['status' => 'Đã xóa khỏi mục yêu thích', 'icon' => 'success']);
        $this->assertDatabaseMissing('like', ['user_id' => $this->customer->user_id, 'pro_id' => $product->pro_id]);
    }

    public function test_bo_yeu_thich_khong_dung_den_yeu_thich_cua_khach_khac(): void
    {
        $product = $this->makeProduct(slug: 'giay-chung');
        $this->like($this->customer, $product);
        $this->like($this->otherCustomer, $product);

        $this->actingAs($this->customer)->post(route('product.wishlist_store'), ['product-id' => $product->pro_id])
            ->assertJson(['status' => 'Đã xóa khỏi mục yêu thích']);

        $this->assertDatabaseMissing('like', ['user_id' => $this->customer->user_id, 'pro_id' => $product->pro_id]);
        $this->assertDatabaseHas('like', ['user_id' => $this->otherCustomer->user_id, 'pro_id' => $product->pro_id]);
    }

    public function test_khach_chua_dang_nhap_bam_tim_bi_nhac_dang_nhap(): void
    {
        $product = $this->makeProduct(slug: 'giay-khach-vang-lai');

        $this->post(route('product.wishlist_store'), ['product-id' => $product->pro_id])
            ->assertOk()
            ->assertExactJson(['status' => 'Vui lòng đăng nhập', 'icon' => 'error']);

        $this->assertSame(0, WishListModel::count());
    }

    public function test_bam_tim_san_pham_khong_ton_tai_bao_loi(): void
    {
        $this->actingAs($this->customer)->post(route('product.wishlist_store'), ['product-id' => 999])
            ->assertOk()
            ->assertExactJson(['status' => 'Sản phẩm không tồn tại', 'icon' => 'error']);

        $this->assertSame(0, WishListModel::count());
    }

    // -------------------------------------------------------------- count

    public function test_dem_chi_tinh_san_pham_dang_ban_cua_chinh_khach(): void
    {
        $visible = $this->makeProduct(slug: 'giay-dem-1');
        $alsoVisible = $this->makeProduct(slug: 'giay-dem-2');
        $hidden = $this->makeProduct(slug: 'giay-dem-an');
        $hidden->pro_hidden = 0;
        $hidden->save();
        $this->like($this->customer, $visible);
        $this->like($this->customer, $alsoVisible);
        $this->like($this->customer, $hidden);
        $this->like($this->otherCustomer, $visible);

        $this->post(route('product.wishlist_count'))->assertExactJson(['count' => 0]);

        $this->actingAs($this->customer)->post(route('product.wishlist_count'))
            ->assertOk()
            ->assertExactJson(['count' => 2]);
    }

    // ------------------------------------------------------------- remove

    public function test_xoa_khoi_trang_yeu_thich(): void
    {
        $product = $this->makeProduct(slug: 'giay-xoa');
        $this->like($this->customer, $product);

        $this->actingAs($this->customer)->post(route('product.wishlist_destroy'), ['pro_id' => $product->pro_id])
            ->assertOk()
            ->assertExactJson(['status' => 'Xóa thành công', 'icon' => 'success']);

        $this->assertSame(0, WishListModel::count());
    }

    public function test_khong_xoa_duoc_yeu_thich_cua_khach_khac(): void
    {
        $product = $this->makeProduct(slug: 'giay-cua-ban');
        $this->like($this->otherCustomer, $product);

        $this->post(route('product.wishlist_destroy'), ['pro_id' => $product->pro_id]);
        $this->actingAs($this->customer)->post(route('product.wishlist_destroy'), ['pro_id' => $product->pro_id]);

        $this->assertDatabaseHas('like', ['user_id' => $this->otherCustomer->user_id, 'pro_id' => $product->pro_id]);
    }

    private function like(UserModel $user, ProductModel $product): WishListModel
    {
        return WishListModel::create(['user_id' => $user->user_id, 'pro_id' => $product->pro_id]);
    }
}
