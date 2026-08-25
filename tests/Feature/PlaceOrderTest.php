<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Exceptions\InsufficientStockException;
use App\Models\CouponModel;
use App\Models\DeliveryInfoModel;
use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Pins down the fixes made to the checkout flow.
 *
 * Each test here maps to a defect found during the audit:
 *  - B1 order placement had no transaction
 *  - B2 stock could be oversold through a race condition
 *  - B3 the order total was taken from the request
 *  - B4 the selling price was taken from the session cart
 */
class PlaceOrderTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seedLookupTables();
    }

    private function placeOrder(UserModel $user, array $cart, ?CouponModel $coupon = null, ?DeliveryInfoModel $address = null): OrderModel
    {
        return app(PlaceOrderAction::class)->execute(
            $user,
            $cart,
            $coupon,
            $address ?? $this->makeAddress($user),
            ['payment' => 'cod', 'note_customer' => null],
        );
    }

    // --------------------------------------------------- B3: the order total

    public function test_tong_tien_duoc_tinh_lai_o_server_bo_qua_gia_tri_tu_request(): void
    {
        $user = $this->makeUser();
        $this->makeAddress($user);
        $product = $this->makeProduct(price: 1_000_000, stock: 5);
        session(['cart' => [$this->cartLine($product, 2)]]);

        $this->actingAs($user)->post('/thanh-toan', [
            'order_code' => 'TAMPER01',
            'payment' => 'cod',
            'thanhtien' => 1000,      // customer rewrites the total down to 1,000 VND
            'deliFee' => 0,           // drops the shipping fee
            'couVal' => 5_000_000,    // and invents a discount
        ]);

        $order = OrderModel::where('user_id', $user->user_id)->firstOrFail();

        $this->assertSame(2_030_000, (int) $order->order_total, 'Tổng tiền phải do server tính: 2×1.000.000 + 30.000 ship');
        $this->assertSame(self::SHIPPING_FEE, (int) $order->order_delivery_fee, 'Phí ship phải lấy từ địa chỉ nhận hàng');
        $this->assertSame(0, (int) $order->order_coupon_value, 'Không có mã giảm giá thì không được giảm đồng nào');
        $this->assertNotSame('TAMPER01', $order->order_code, 'Mã đơn hàng phải do server sinh, không lấy từ request');
    }

    // -------------------------------------------------- B4: the selling price

    public function test_gia_ban_lay_tu_database_khong_lay_tu_gio_hang(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct(price: 2_000_000, stock: 5);

        // The session cart claims a price of 1 VND.
        $order = $this->placeOrder($user, [$this->cartLine($product, 1, fakePrice: 1)]);

        $detail = OrderDetailModel::where('order_id', $order->order_id)->firstOrFail();

        $this->assertSame(2_000_000, (int) $detail->price);
        $this->assertSame(2_030_000, (int) $order->order_total);
    }

    public function test_uu_tien_gia_khuyen_mai_khi_san_pham_dang_sale(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct(price: 2_000_000, salePrice: 1_500_000, stock: 5);

        $order = $this->placeOrder($user, [$this->cartLine($product, 2)]);

        $detail = OrderDetailModel::where('order_id', $order->order_id)->firstOrFail();

        $this->assertSame(1_500_000, (int) $detail->price);
        $this->assertSame(3_030_000, (int) $order->order_total);
    }

    // ----------------------------------------------------------- coupons

    public function test_ma_giam_gia_theo_phan_tram_duoc_tinh_lai_tu_database(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct(price: 1_000_000, stock: 5);

        $coupon = CouponModel::create([
            'coupon_name' => 'Giảm 10%', 'coupon_code' => 'SALE10', 'coupon_value' => 10,
            'coupon_quantity' => 3, 'coupon_used' => 0, 'coupon_condition' => 0,
            'coupon_start' => now()->subDay()->toDateString(),
            'coupon_end' => now()->addDay()->toDateString(),
        ]);

        $order = $this->placeOrder($user, [$this->cartLine($product, 1)], $coupon);

        $this->assertSame(100_000, (int) $order->order_coupon_value);
        $this->assertSame(930_000, (int) $order->order_total, '1.000.000 − 100.000 + 30.000');
        $this->assertSame(2, (int) $coupon->fresh()->coupon_quantity, 'Phải trừ đúng một lượt');
        $this->assertSame(1, (int) $coupon->fresh()->coupon_used);
    }

    public function test_ma_giam_gia_het_han_bi_bo_qua(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct(price: 1_000_000, stock: 5);

        $coupon = CouponModel::create([
            'coupon_name' => 'Đã hết hạn', 'coupon_code' => 'EXPIRED', 'coupon_value' => 500_000,
            'coupon_quantity' => 3, 'coupon_used' => 0, 'coupon_condition' => 1,
            'coupon_start' => now()->subMonth()->toDateString(),
            'coupon_end' => now()->subDay()->toDateString(),
        ]);

        $order = $this->placeOrder($user, [$this->cartLine($product, 1)], $coupon);

        $this->assertSame(0, (int) $order->order_coupon_value);
        $this->assertNull($order->coupon_id);
        $this->assertSame(1_030_000, (int) $order->order_total);
    }

    // ---------------------------------------------------------- B2: stock

    public function test_khong_dat_duoc_khi_kho_khong_du_hang(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct(price: 1_000_000, stock: 1);

        $this->expectException(InsufficientStockException::class);

        try {
            $this->placeOrder($user, [$this->cartLine($product, 5)]);
        } finally {
            $this->assertSame(0, OrderModel::count(), 'Không được tạo đơn nào');
            $this->assertSame(1, $this->stockOf($product), 'Tồn kho phải giữ nguyên');
        }
    }

    public function test_ton_kho_khong_bao_gio_am_khi_hai_don_cung_mua_doi_cuoi(): void
    {
        $user = $this->makeUser();
        $address = $this->makeAddress($user);
        $product = $this->makeProduct(price: 1_000_000, stock: 1);
        $cart = [$this->cartLine($product, 1)];

        // The first order takes the very last pair.
        $this->placeOrder($user, $cart, null, $address);
        $this->assertSame(0, $this->stockOf($product));

        // The second must be rejected rather than driving stock negative.
        $this->expectException(InsufficientStockException::class);

        try {
            $this->placeOrder($user, $cart, null, $address);
        } finally {
            $this->assertSame(0, $this->stockOf($product), 'Tồn kho không được xuống dưới 0');
            $this->assertSame(1, OrderModel::count(), 'Chỉ đơn đầu tiên được tạo');
        }
    }

    // ---------------------------------------------------- B1: transaction

    public function test_toan_bo_don_bi_rollback_khi_mot_dong_hang_het_ton(): void
    {
        $user = $this->makeUser();
        $address = $this->makeAddress($user);

        $inStock = $this->makeProduct(price: 1_000_000, stock: 10, slug: 'in-stock');
        $soldOut = $this->makeProduct(price: 2_000_000, stock: 1, slug: 'sold-out');

        $coupon = CouponModel::create([
            'coupon_name' => 'Giảm 50k', 'coupon_code' => 'GIAM50K', 'coupon_value' => 50_000,
            'coupon_quantity' => 5, 'coupon_used' => 0, 'coupon_condition' => 1,
            'coupon_start' => now()->subDay()->toDateString(),
            'coupon_end' => now()->addDay()->toDateString(),
        ]);

        $cart = [
            $this->cartLine($inStock, 2),   // this line decrements stock successfully…
            $this->cartLine($soldOut, 5),   // …and only then does this one fail
        ];

        try {
            $this->placeOrder($user, $cart, $coupon, $address);
            $this->fail('Expected an InsufficientStockException');
        } catch (InsufficientStockException) {
            // expected
        }

        $this->assertSame(0, OrderModel::count(), 'Không được để lại đơn hàng dở dang');
        $this->assertSame(0, OrderDetailModel::count(), 'Không được để lại chi tiết đơn');
        $this->assertSame(10, $this->stockOf($inStock), 'Tồn kho dòng đầu phải được hoàn lại');
        $this->assertSame(1, $this->stockOf($soldOut));
        $this->assertSame(5, (int) $coupon->fresh()->coupon_quantity, 'Mã giảm giá không được trừ lượt');
        $this->assertSame(0, (int) $coupon->fresh()->coupon_used);
    }

    public function test_don_hang_thanh_cong_ghi_du_chi_tiet_va_tru_dung_ton_kho(): void
    {
        $user = $this->makeUser();
        $address = $this->makeAddress($user);
        $a = $this->makeProduct(price: 1_000_000, stock: 10, slug: 'san-pham-a');
        $b = $this->makeProduct(price: 500_000, stock: 4, slug: 'san-pham-b');

        $order = $this->placeOrder($user, [$this->cartLine($a, 2), $this->cartLine($b, 3)], null, $address);

        $this->assertSame(2, OrderDetailModel::where('order_id', $order->order_id)->count());
        $this->assertSame(8, $this->stockOf($a));
        $this->assertSame(1, $this->stockOf($b));
        $this->assertSame(3_530_000, (int) $order->order_total, '2.000.000 + 1.500.000 + 30.000');

        // The delivery address is copied onto the order.
        $this->assertSame('Nguyễn Văn A', $order->order_name);
        $this->assertSame('Linh Chiểu, Thủ Đức, TP.HCM', $order->order_local);
    }
}
