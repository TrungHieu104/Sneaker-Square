<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Models\CouponModel;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\ShippingCarrier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The lines and the money of an order, as the admin reads them before acting
 * on it.
 */
class AdminOrderDetailTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $admin;

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->app->instance(ShippingCarrier::class, new FakeCarrier);

        $this->admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $this->admin->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web'));

        $this->customer = $this->makeUser();
    }

    private function placeOrder(?CouponModel $coupon): OrderModel
    {
        return app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($this->makeProduct(price: 1_000_000, slug: 'giay-chi-tiet'), 3)],
            $coupon,
            $this->makeAddress($this->customer),
            ['payment' => 'COD', 'note_customer' => null],
        );
    }

    private function open(OrderModel $order): TestResponse
    {
        return $this->actingAs($this->admin)
            ->get(route('orders.edit', Crypt::encrypt($order->order_id)))
            ->assertOk();
    }

    public function test_chi_tiet_don_hien_tien_hang_phi_ship_ma_giam_va_tong(): void
    {
        $coupon = CouponModel::create([
            'coupon_name' => 'Ưu đãi', 'coupon_code' => 'GIOHANGTEST', 'coupon_value' => 10,
            'coupon_max_discount' => 150_000, 'coupon_quantity' => 1, 'coupon_used' => 0, 'coupon_condition' => 0,
            'coupon_start' => now()->toDateString(), 'coupon_end' => now()->addDays(3)->toDateString(),
        ]);

        $this->open($this->placeOrder($coupon))
            ->assertSeeInOrder(['Sản Phẩm Giay-chi-tiet', 'Size 42 · Đen', '1.000.000đ', '× 3', '3.000.000đ'])
            ->assertSeeInOrder([
                'Tiền hàng (3 sản phẩm)', '3.000.000đ',
                'Phí vận chuyển', '37.000đ',
                'GIOHANGTEST', 'Giảm 10% · giảm tối đa 150.000 VNĐ', '− 150.000đ',
                'Tổng thanh toán', '2.887.000đ',
            ]);
    }

    public function test_don_khong_dung_ma_ghi_khong_ap_dung(): void
    {
        $this->open($this->placeOrder(null))->assertSeeInOrder(['Mã giảm giá', 'Không áp dụng', 'Tổng thanh toán', '3.037.000đ']);
    }
}
