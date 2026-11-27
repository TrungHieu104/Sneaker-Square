<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Enums\OrderStatus;
use App\Events\ShipmentChanged;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\ShipmentPulse;
use App\Services\Shipping\ShipmentTracker;
use App\Services\Shipping\ShippingCarrier;
use App\Services\ShippingService;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Spatie\Permission\Models\Permission;
use Tests\Support\ReverbFixtures;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The order page learns about a shipment change over Reverb and fetches its
 * shipment blocks; without the socket it polls with the marker it last saw.
 */
class ShipmentLiveTest extends TestCase
{
    use RefreshDatabase;
    use ReverbFixtures;
    use ShopFixtures;

    private const DA_XAC_NHAN = OrderStatus::Confirmed;

    private FakeCarrier $carrier;

    private UserModel $admin;

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->carrier = new FakeCarrier;
        $this->app->instance(ShippingCarrier::class, $this->carrier);
        config(['services.ghn.create_orders' => true]);

        $this->admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $this->admin->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web'));

        $this->customer = $this->makeUser();
    }

    private function makeOrder(string $slug = 'giay-stream'): OrderModel
    {
        $product = $this->makeProduct(price: 1_000_000, slug: $slug);
        $address = $this->makeAddress($this->customer);

        $order = app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($product)],
            null,
            $address,
            ['payment' => 'COD', 'note_customer' => null],
        );

        $order->update(['order_status' => self::DA_XAC_NHAN]);

        return $order->fresh();
    }

    private function pulse(OrderModel $order): string
    {
        return app(ShipmentPulse::class)->current((int) $order->order_id);
    }

    // ------------------------------------------------ cái mốc được đẩy lên

    public function test_webhook_tu_ghn_lam_doi_moc(): void
    {
        $order = $this->makeOrder();
        app(ShippingService::class)->book($order);

        $truoc = $this->pulse($order);

        app(ShipmentTracker::class)->record([
            'OrderCode' => 'FAKE001',
            'ClientOrderCode' => $order->order_code,
            'Status' => 'delivering',
            'Time' => now()->toIso8601String(),
        ]);

        $this->assertNotSame($truoc, $this->pulse($order), 'Có cập nhật mà mốc không đổi thì trang không được báo');
    }

    public function test_webhook_lap_lai_khong_lam_doi_moc(): void
    {
        $order = $this->makeOrder();
        app(ShippingService::class)->book($order);

        $goi = [
            'OrderCode' => 'FAKE001',
            'ClientOrderCode' => $order->order_code,
            'Status' => 'delivering',
            'Time' => now()->toIso8601String(),
        ];

        app(ShipmentTracker::class)->record($goi);
        $sauLanMot = $this->pulse($order);

        app(ShipmentTracker::class)->record($goi);

        $this->assertSame($sauLanMot, $this->pulse($order), 'GHN gửi lại cùng một sự kiện thì đừng vẽ lại trang');
    }

    public function test_tao_va_huy_van_don_deu_lam_doi_moc(): void
    {
        $order = $this->makeOrder();

        $truocKhiTao = $this->pulse($order);
        app(ShippingService::class)->book($order);
        $sauKhiTao = $this->pulse($order);

        $this->assertNotSame($truocKhiTao, $sauKhiTao);

        app(ShippingService::class)->cancelBooking($order->fresh());

        $this->assertNotSame($sauKhiTao, $this->pulse($order));
    }

    // ------------------------------------------------- báo qua socket

    public function test_tao_va_huy_van_don_deu_bao_qua_socket(): void
    {
        Event::fake([ShipmentChanged::class]);
        $order = $this->makeOrder();

        app(ShippingService::class)->book($order);
        app(ShippingService::class)->cancelBooking($order->fresh());

        $this->assertSame(2, $this->signalsFor($order));
    }

    public function test_webhook_tu_ghn_bao_qua_socket(): void
    {
        $order = $this->makeOrder();
        app(ShippingService::class)->book($order);
        Event::fake([ShipmentChanged::class]);

        app(ShipmentTracker::class)->record([
            'OrderCode' => 'FAKE001',
            'ClientOrderCode' => $order->order_code,
            'Status' => 'delivering',
            'Time' => now()->toIso8601String(),
        ]);

        $this->assertSame(1, $this->signalsFor($order));
    }

    public function test_chi_nhan_vien_quan_tri_don_moi_nghe_duoc_kenh_van_don(): void
    {
        $order = $this->makeOrder();
        $this->reverbOn();
        $kenh = 'private-'.ShipmentChanged::channelFor((int) $order->order_id);
        $khongCoQuyen = $this->makeUser(email: 'nv@example.test', username: 'nhanvien', role: 1);

        $this->actingAs($this->admin)->post('/broadcasting/auth', ['socket_id' => self::SOCKET, 'channel_name' => $kenh])
            ->assertOk()
            ->assertExactJson(['auth' => $this->signature($kenh)]);

        $this->actingAs($khongCoQuyen)->post('/broadcasting/auth', ['socket_id' => self::SOCKET, 'channel_name' => $kenh])
            ->assertForbidden();
        $this->actingAs($this->customer)->post('/broadcasting/auth', ['socket_id' => self::SOCKET, 'channel_name' => $kenh])
            ->assertForbidden();
    }

    public function test_may_chu_socket_khong_chay_thi_van_tao_duoc_van_don(): void
    {
        $order = $this->makeOrder();
        // Nothing listens on port 1, so every signal fails to go out.
        $this->reverbOn(port: 1);
        Exceptions::fake();

        app(ShippingService::class)->book($order);

        $this->assertSame('FAKE001', $order->fresh()->order_shipping_code);
        Exceptions::assertReported(BroadcastException::class);
    }

    private function signalsFor(OrderModel $order): int
    {
        return Event::dispatched(ShipmentChanged::class, fn (ShipmentChanged $event) => $event->orderId === (int) $order->order_id)->count();
    }

    // ------------------------------------------------ khối trang tự tải lại

    public function test_khoi_gui_ve_hanh_trinh_va_moc_hien_tai(): void
    {
        $order = $this->makeOrder();
        app(ShippingService::class)->book($order);

        $goi = $this->actingAs($this->admin)
            ->getJson(route('order.shipment_blocks', $order->order_id))
            ->assertOk()
            ->json();

        $this->assertStringContainsString('FAKE001', $goi['hanhtrinh']);
        $this->assertSame($this->pulse($order), $goi['marker']);
    }

    public function test_moc_chua_doi_thi_khong_gui_lai_khoi(): void
    {
        $order = $this->makeOrder();
        app(ShippingService::class)->book($order);
        $moc = $this->pulse($order);

        $this->actingAs($this->admin)
            ->getJson(route('order.shipment_blocks', ['order_id' => $order->order_id, 'since' => $moc]))
            ->assertNoContent();

        app(ShippingService::class)->cancelBooking($order->fresh());

        $this->actingAs($this->admin)
            ->getJson(route('order.shipment_blocks', ['order_id' => $order->order_id, 'since' => $moc]))
            ->assertOk()
            ->assertJsonPath('marker', $this->pulse($order));
    }

    public function test_khoi_khong_gui_nut_huy_khi_tat_tao_van_don(): void
    {
        $order = $this->makeOrder();
        app(ShippingService::class)->book($order);
        config(['services.ghn.create_orders' => false]);

        $goi = $this->blocks($order);

        $this->assertStringNotContainsString('Huỷ vận đơn', $goi['hanhtrinh']);
    }

    // --------------------------------- GHN huỷ từ phía họ thì nút cũng phải đổi

    public function test_webhook_huy_tra_lai_ma_de_tao_van_don_khac(): void
    {
        $order = $this->makeOrder();
        app(ShippingService::class)->book($order);

        app(ShipmentTracker::class)->record([
            'OrderCode' => 'FAKE001',
            'ClientOrderCode' => $order->order_code,
            'Status' => 'cancel',
            'Time' => now()->toIso8601String(),
        ]);

        $order = $order->fresh();

        $this->assertNull($order->order_shipping_code, 'Huỷ trên web GHN cũng phải thả mã ra');
        $this->assertNull($order->order_expected_delivery);
        $this->assertSame('cancel', $order->order_shipping_status);
    }

    public function test_khoi_gui_ve_ca_van_don_lan_hanh_trinh(): void
    {
        $order = $this->makeOrder();
        app(ShippingService::class)->book($order);

        $goi = $this->blocks($order);

        $this->assertArrayHasKey('vandon', $goi);
        $this->assertArrayHasKey('hanhtrinh', $goi);
        $this->assertStringContainsString('FAKE001', $goi['hanhtrinh']);
        $this->assertStringContainsString('Sửa mã vận đơn', $goi['vandon']);
    }

    public function test_sau_khi_ghn_huy_thi_khoi_gui_ve_co_nut_tao_lai(): void
    {
        $order = $this->makeOrder();
        app(ShippingService::class)->book($order);

        app(ShipmentTracker::class)->record([
            'OrderCode' => 'FAKE001',
            'ClientOrderCode' => $order->order_code,
            'Status' => 'cancel',
            'Time' => now()->toIso8601String(),
        ]);

        $goi = $this->blocks($order);

        $this->assertStringContainsString('Tạo vận đơn GHN', $goi['vandon']);
        $this->assertStringNotContainsString('Huỷ vận đơn', $goi['hanhtrinh']);
    }

    public function test_don_da_giao_thi_khong_con_nut_huy(): void
    {
        $order = $this->makeOrder();
        app(ShippingService::class)->book($order);

        app(ShipmentTracker::class)->record([
            'OrderCode' => 'FAKE001',
            'ClientOrderCode' => $order->order_code,
            'Status' => 'delivered',
            'Time' => now()->toIso8601String(),
        ]);

        $goi = $this->blocks($order);

        $this->assertStringContainsString('Giao hàng thành công', $goi['hanhtrinh']);
        $this->assertStringNotContainsString('Huỷ vận đơn', $goi['hanhtrinh'], 'Hàng đã tới nơi thì gọi về làm sao được');
    }

    /**
     * @return array{marker: string, vandon: string, hanhtrinh: string}
     */
    private function blocks(OrderModel $order): array
    {
        return $this->actingAs($this->admin)
            ->getJson(route('order.shipment_blocks', $order->order_id))
            ->assertOk()
            ->json();
    }

    public function test_khach_thuong_khong_lay_duoc_khoi_van_don(): void
    {
        $order = $this->makeOrder();

        // The admin area bounces a customer to the login screen rather than
        // answering 403.
        $this->actingAs($this->customer)
            ->get(route('order.shipment_blocks', $order->order_id))
            ->assertRedirect();
    }

    public function test_don_khong_ton_tai_thi_bao_khong_tim_thay(): void
    {
        $this->actingAs($this->admin)
            ->get(route('order.shipment_blocks', 999999))
            ->assertNotFound();
    }

    public function test_trang_chi_tiet_don_nghe_kenh_van_don_va_khong_giu_luong(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin)
            ->get(route('orders.edit', Crypt::encrypt($order->order_id)))
            ->assertOk()
            ->assertSee('id="hanh-trinh-van-don"', false)
            ->assertSee(route('order.shipment_blocks', $order->order_id), false)
            ->assertSee('data-channel="'.ShipmentChanged::channelFor((int) $order->order_id).'"', false)
            ->assertSee('data-marker="'.$this->pulse($order).'"', false)
            ->assertDontSee('EventSource', false);
    }
}
