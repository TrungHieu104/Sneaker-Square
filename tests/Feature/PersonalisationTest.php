<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\UserEventModel as Event;
use App\Models\UserModel;
use App\Services\UserEventLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * What the product page offers one shopper: products like this one, what they
 * already looked at, and the shoe size to pick for them.
 */
class PersonalisationTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const VISITOR = 'abcdefghijABCDEFGHIJ0123456789abcdefghij';

    private const OTHER_VISITOR = 'zyxwvutsrqZYXWVUTSRQ9876543210zyxwvutsrq';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        DB::table('category')->insertOrIgnore([
            'cate_id' => 2, 'cate_name' => 'Phụ kiện', 'cate_slug' => 'phu-kien', 'cate_sort' => 2, 'cate_hidden' => 1,
        ]);
        DB::table('size')->insertOrIgnore([
            ['size_id' => 2, 'size' => '40', 'size_hidden' => 1],
            ['size_id' => 3, 'size' => 'M', 'size_hidden' => 1],
        ]);
    }

    private function asVisitor(string $visitor = self::VISITOR): static
    {
        return $this->withCookie(UserEventLog::COOKIE, $visitor);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function history(string $type, array $fields, string $visitor = self::VISITOR, ?int $userId = null): void
    {
        Event::create($fields + [
            'session_id' => $visitor,
            'user_id' => $userId,
            'event_type' => $type,
            'created_at' => now(),
        ]);
    }

    private function product(string $slug, int $price = 1_000_000, int $category = 1, int $views = 0): ProductModel
    {
        $product = $this->makeProduct(price: $price, slug: $slug);
        $product->cate_id = $category;
        $product->pro_views = $views;
        $product->save();

        return $product;
    }

    /**
     * @return array<int, int>
     */
    private function shown(string $source): array
    {
        return Event::where('event_type', Event::RECOMMEND)->latest('event_id')->get()
            ->first(fn (Event $event) => $event->meta['source'] === $source)?->meta['pro_ids'] ?? [];
    }

    private function buy(UserModel $user, ProductModel $product, string $size, OrderStatus $status): void
    {
        $order = OrderModel::create([
            'order_code' => 'DH'.random_int(1000000, 9999999),
            'order_name' => 'Nguyễn Văn A',
            'order_email' => 'khach@example.test',
            'order_address' => '1 Võ Văn Ngân',
            'order_local' => 'Thủ Đức, TP.HCM',
            'order_phone' => '0912345678',
            'order_delivery_fee' => 30000,
            'order_coupon_value' => 0,
            'order_total' => 1_030_000,
            'order_payment' => 'cod',
            'order_payment_status' => 0,
            'order_date' => now()->toDateString(),
            'order_status' => $status,
            'user_id' => $user->user_id,
        ]);

        OrderDetailModel::create([
            'order_id' => $order->order_id,
            'pro_id' => $product->pro_id,
            'pro_name' => $product->pro_name,
            'size' => $size,
            'price' => 1_000_000,
            'quantity' => 1,
        ]);
    }

    // ------------------------------------------------------ recently viewed

    public function test_san_pham_da_xem_moi_nhat_dung_truoc_va_bo_san_pham_dang_xem(): void
    {
        $current = $this->product('dang-xem');
        $older = $this->product('xem-truoc');
        $newer = $this->product('xem-sau');
        $this->history(Event::VIEW_PRODUCT, ['pro_id' => $older->pro_id]);
        $this->history(Event::VIEW_PRODUCT, ['pro_id' => $newer->pro_id]);
        $this->history(Event::VIEW_PRODUCT, ['pro_id' => $current->pro_id]);

        $this->asVisitor()->get(route('product.recommendations', 'dang-xem'))
            ->assertOk()
            ->assertSee('Sản phẩm bạn đã xem');

        $this->assertSame([$newer->pro_id, $older->pro_id], $this->shown('recent'));
    }

    public function test_lich_su_xem_cua_khach_khac_khong_lot_sang(): void
    {
        $this->product('dang-xem');
        $theirs = $this->product('cua-nguoi-khac');
        $this->history(Event::VIEW_PRODUCT, ['pro_id' => $theirs->pro_id], self::OTHER_VISITOR);

        $this->asVisitor()->get(route('product.recommendations', 'dang-xem'))
            ->assertOk()
            ->assertDontSee('Sản phẩm bạn đã xem');

        $this->assertSame([], $this->shown('recent'));
    }

    public function test_dang_nhap_thay_ca_san_pham_da_xem_tren_thiet_bi_khac(): void
    {
        $customer = $this->makeUser();
        $this->product('dang-xem');
        $onPhone = $this->product('xem-tren-dien-thoai');
        $this->history(Event::VIEW_PRODUCT, ['pro_id' => $onPhone->pro_id], self::OTHER_VISITOR, $customer->user_id);

        $this->actingAs($customer)->asVisitor()->get(route('product.recommendations', 'dang-xem'))->assertOk();

        $this->assertSame([$onPhone->pro_id], $this->shown('recent'));
    }

    public function test_san_pham_an_khong_duoc_goi_y_lai(): void
    {
        $this->product('dang-xem');
        $hidden = $this->product('da-an');
        $hidden->pro_hidden = 0;
        $hidden->save();
        $this->history(Event::VIEW_PRODUCT, ['pro_id' => $hidden->pro_id]);

        $this->asVisitor()->get(route('product.recommendations', 'dang-xem'))->assertOk();

        $this->assertSame([], $this->shown('recent'));
    }

    // ------------------------------------------------------- similar products

    public function test_goi_y_uu_tien_cung_danh_muc_roi_den_cung_tam_gia(): void
    {
        $this->product('dang-xem', price: 1_000_000);
        $sameCategoryAndPrice = $this->product('cung-ca-hai', price: 1_100_000);
        $sameCategory = $this->product('cung-danh-muc', price: 3_000_000, views: 99);
        $samePriceOnly = $this->product('cung-gia-khac-loai', price: 950_000, category: 2);
        $nothingInCommon = $this->product('khong-lien-quan', price: 90_000, category: 2);

        $this->asVisitor()->get(route('product.recommendations', 'dang-xem'))
            ->assertOk()
            ->assertSee('Có thể bạn cũng thích')
            ->assertSee('Cùng tầm giá');

        $shown = $this->shown('similar');
        $this->assertSame([$sameCategoryAndPrice->pro_id, $sameCategory->pro_id, $samePriceOnly->pro_id], $shown);
        $this->assertNotContains($nothingInCommon->pro_id, $shown);
    }

    public function test_goi_y_cong_diem_cho_mau_khach_hay_chon_va_size_con_hang(): void
    {
        $this->product('dang-xem', price: 1_000_000);
        $plain = $this->product('khong-co-mau-do', price: 3_000_000, views: 99);
        $red = $this->product('co-mau-do', price: 3_000_000);
        $this->addVariant($red, colorId: 2);
        $this->history(Event::SELECT_VARIANT, ['pro_id' => $red->pro_id, 'color_id' => 2, 'size_id' => 1]);

        $this->asVisitor()->get(route('product.recommendations', 'dang-xem'))
            ->assertOk()
            ->assertSee('Có màu bạn quan tâm');

        $this->assertSame([$red->pro_id, $plain->pro_id], $this->shown('similar'));
    }

    public function test_bam_vao_goi_y_ghi_nguon_tren_luot_xem(): void
    {
        $this->product('duoc-goi-y');

        $this->asVisitor()->get(route('product.detail', ['duoc-goi-y', 'tu' => 'goi-y']))->assertOk();
        $this->asVisitor()->get(route('product.detail', ['duoc-goi-y', 'tu' => 'linh-tinh']))->assertOk();

        $views = Event::where('event_type', Event::VIEW_PRODUCT)->orderBy('event_id')->get();
        $this->assertSame('similar', $views[0]->meta['source']);
        $this->assertNull($views[1]->meta, 'Nguồn lạ trên URL không được ghi');
    }

    public function test_trang_san_pham_nap_goi_y_sau_khi_tai(): void
    {
        $this->product('dang-xem');

        $this->get(route('product.detail', 'dang-xem'))
            ->assertOk()
            ->assertSee(route('product.recommendations', 'dang-xem'), false)
            ->assertSee('frontend/js/app.js?v='.filemtime(public_path('frontend/js/app.js')), false);
    }

    // ------------------------------------------------------------ shoe size

    public function test_chua_do_chan_thi_chon_san_size_cua_don_gan_nhat(): void
    {
        $customer = $this->makeUser();
        $product = $this->product('da-mua');
        $this->buy($customer, $product, '41', OrderStatus::Completed);
        $this->buy($customer, $product, '42', OrderStatus::Delivering);

        $this->actingAs($customer)->getJson(route('foot.show'))
            ->assertExactJson(['size' => 42, 'source' => 'purchased']);
    }

    public function test_don_bi_huy_hoac_tra_hang_khong_dung_de_nho_size(): void
    {
        $customer = $this->makeUser();
        $product = $this->product('da-mua');
        $this->buy($customer, $product, '41', OrderStatus::Completed);
        $this->buy($customer, $product, '43', OrderStatus::Returned);
        $this->buy($customer, $product, '44', OrderStatus::Cancelled);

        $this->actingAs($customer)->getJson(route('foot.show'))
            ->assertExactJson(['size' => 41, 'source' => 'purchased']);
    }

    public function test_size_da_do_uu_tien_hon_size_tung_mua(): void
    {
        $customer = $this->makeUser();
        $customer->shoe_size = 40;
        $customer->save();
        $this->buy($customer, $this->product('da-mua'), '42', OrderStatus::Completed);

        $this->actingAs($customer)->getJson(route('foot.show'))
            ->assertExactJson(['size' => 40, 'source' => 'measured']);
    }

    public function test_khach_vang_lai_lay_size_lan_them_gio_gan_nhat_bo_qua_size_phu_kien(): void
    {
        $product = $this->product('giay');
        $this->history(Event::ADD_CART, ['pro_id' => $product->pro_id, 'size_id' => 2, 'color_id' => 1]);
        $this->history(Event::ADD_CART, ['pro_id' => $product->pro_id, 'size_id' => 3, 'color_id' => 1]);

        // A JSON request leaves cookies behind unless told otherwise.
        $this->asVisitor()->withCredentials()->getJson(route('foot.show'))
            ->assertExactJson(['size' => 40, 'source' => 'carted']);
        $this->asVisitor(self::OTHER_VISITOR)->withCredentials()->getJson(route('foot.show'))
            ->assertExactJson(['size' => null, 'source' => null]);
    }
}
