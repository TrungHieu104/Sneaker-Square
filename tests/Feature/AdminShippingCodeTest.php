<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\ShippingCarrier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The admin tying an order to the parcel GHN is carrying.
 */
class AdminShippingCodeTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const MOI_DAT = 0;

    private const DA_XAC_NHAN = 1;

    private const DA_HUY = 2;

    private const HOAN_HANG = 3;

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

    private function makeOrder(string $slug): OrderModel
    {
        $product = $this->makeProduct(slug: $slug);
        $address = $this->makeAddress($this->customer, districtId: 1442, wardCode: '20110');

        $order = app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($product)],
            null,
            $address,
            ['payment' => 'COD', 'note_customer' => null],
        );

        // Checkout leaves an order waiting for the shop to look at it; a parcel
        // only belongs to one the shop has already confirmed.
        $order->update(['order_status' => self::DA_XAC_NHAN]);

        return $order->fresh();
    }

    public function test_luu_ma_van_don(): void
    {
        $order = $this->makeOrder('giay-ma-van-don');

        $this->actingAs($this->admin)
            ->patch(route('order.shipping_code', $order->order_id), ['order_shipping_code' => 'LFV3G8'])
            ->assertSessionHasNoErrors();

        $this->assertSame('LFV3G8', $order->fresh()->order_shipping_code);
    }

    /**
     * Two orders on one parcel would send one customer the other's history.
     */
    public function test_ma_van_don_trung_bi_chan(): void
    {
        $first = $this->makeOrder('giay-don-mot');
        $first->update(['order_shipping_code' => 'LFV3G8']);
        $second = $this->makeOrder('giay-don-hai');

        $this->actingAs($this->admin)
            ->patch(route('order.shipping_code', $second->order_id), ['order_shipping_code' => 'LFV3G8'])
            ->assertSessionHasErrors('order_shipping_code');

        $this->assertNull($second->fresh()->order_shipping_code);
    }

    public function test_luu_lai_chinh_ma_cu_khong_bi_bao_trung(): void
    {
        $order = $this->makeOrder('giay-giu-ma');
        $order->update(['order_shipping_code' => 'LFV3G8']);

        $this->actingAs($this->admin)
            ->patch(route('order.shipping_code', $order->order_id), ['order_shipping_code' => 'LFV3G8'])
            ->assertSessionHasNoErrors();
    }

    public function test_xoa_ma_van_don_bang_cach_de_trong(): void
    {
        $order = $this->makeOrder('giay-xoa-ma');
        $order->update(['order_shipping_code' => 'LFV3G8']);

        $this->actingAs($this->admin)
            ->patch(route('order.shipping_code', $order->order_id), ['order_shipping_code' => ''])
            ->assertSessionHasNoErrors();

        $this->assertNull($order->fresh()->order_shipping_code);
    }

    public function test_khach_thuong_khong_gan_duoc_ma_van_don(): void
    {
        $order = $this->makeOrder('giay-khong-quyen');

        // The admin area bounces a customer to the login screen rather than
        // answering 403, so the saved value is what proves the guard held.
        $this->actingAs($this->customer)
            ->patch(route('order.shipping_code', $order->order_id), ['order_shipping_code' => 'LFV3G8'])
            ->assertRedirect();

        $this->assertNull($order->fresh()->order_shipping_code);
    }

    public function test_trang_chi_tiet_don_hien_o_ma_van_don_va_hanh_trinh(): void
    {
        $order = $this->makeOrder('giay-trang-admin');
        $order->update(['order_shipping_code' => 'LFV3G8', 'order_shipping_status' => 'delivering']);

        $this->actingAs($this->admin)
            ->get(route('orders.edit', Crypt::encrypt($order->order_id)))
            ->assertOk()
            ->assertSee('name="order_shipping_code"', false)
            ->assertSee('Hành trình vận đơn')
            ->assertSee('Đang giao hàng');
    }
}
