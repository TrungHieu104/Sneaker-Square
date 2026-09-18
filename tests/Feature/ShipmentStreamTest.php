<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\ShipmentPulse;
use App\Services\Shipping\ShipmentTracker;
use App\Services\Shipping\ShippingCarrier;
use App\Services\ShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The admin page is written to when GHN's callback lands.
 *
 * Nothing on the client asks again: the marker these tests watch is what an
 * open event stream is waiting on, and moving it is what pushes a new render.
 */
class ShipmentStreamTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const DA_XAC_NHAN = 1;

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

    // ------------------------------------------------------- luồng sự kiện

    public function test_luong_gui_ve_hanh_trinh_hien_tai(): void
    {
        $order = $this->makeOrder();
        app(ShippingService::class)->book($order);

        $response = $this->actingAs($this->admin)
            ->get(route('order.shipment_stream', $order->order_id));

        $response->assertOk();
        $this->assertStringStartsWith('text/event-stream', $response->headers->get('Content-Type'));

        $noiDung = $response->streamedContent();

        $this->assertStringContainsString('event: hanhtrinh', $noiDung);
        $this->assertStringContainsString('FAKE001', $noiDung);
    }

    public function test_luong_khong_gui_nut_huy_khi_tat_tao_van_don(): void
    {
        $order = $this->makeOrder();
        app(ShippingService::class)->book($order);
        config(['services.ghn.create_orders' => false]);

        $noiDung = $this->actingAs($this->admin)
            ->get(route('order.shipment_stream', $order->order_id))
            ->streamedContent();

        $this->assertStringNotContainsString('Huỷ vận đơn', $noiDung);
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

    public function test_luong_day_ve_ca_khoi_van_don_lan_hanh_trinh(): void
    {
        $order = $this->makeOrder();
        app(ShippingService::class)->book($order);

        $noiDung = $this->actingAs($this->admin)
            ->get(route('order.shipment_stream', $order->order_id))
            ->streamedContent();

        $goi = json_decode($this->duLieuSuKien($noiDung), true);

        $this->assertArrayHasKey('vandon', $goi);
        $this->assertArrayHasKey('hanhtrinh', $goi);
        $this->assertStringContainsString('FAKE001', $goi['hanhtrinh']);
        $this->assertStringContainsString('Sửa mã vận đơn', $goi['vandon']);
    }

    public function test_sau_khi_ghn_huy_thi_khoi_day_ve_co_nut_tao_lai(): void
    {
        $order = $this->makeOrder();
        app(ShippingService::class)->book($order);

        app(ShipmentTracker::class)->record([
            'OrderCode' => 'FAKE001',
            'ClientOrderCode' => $order->order_code,
            'Status' => 'cancel',
            'Time' => now()->toIso8601String(),
        ]);

        $noiDung = $this->actingAs($this->admin)
            ->get(route('order.shipment_stream', $order->order_id))
            ->streamedContent();

        $goi = json_decode($this->duLieuSuKien($noiDung), true);

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

        $noiDung = $this->actingAs($this->admin)
            ->get(route('order.shipment_stream', $order->order_id))
            ->streamedContent();

        $goi = json_decode($this->duLieuSuKien($noiDung), true);

        $this->assertStringContainsString('Giao hàng thành công', $goi['hanhtrinh']);
        $this->assertStringNotContainsString('Huỷ vận đơn', $goi['hanhtrinh'], 'Hàng đã tới nơi thì gọi về làm sao được');
    }

    /**
     * The payload of the first event the stream writes.
     */
    private function duLieuSuKien(string $noiDung): string
    {
        foreach (explode("\n", $noiDung) as $dong) {
            if (str_starts_with($dong, 'data: ')) {
                return substr($dong, 6);
            }
        }

        return '';
    }

    public function test_khach_thuong_khong_mo_duoc_luong(): void
    {
        $order = $this->makeOrder();

        // The admin area bounces a customer to the login screen rather than
        // answering 403.
        $this->actingAs($this->customer)
            ->get(route('order.shipment_stream', $order->order_id))
            ->assertRedirect();
    }

    public function test_don_khong_ton_tai_thi_khong_mo_luong(): void
    {
        $this->actingAs($this->admin)
            ->get(route('order.shipment_stream', 999999))
            ->assertNotFound();
    }

    public function test_trang_chi_tiet_don_mo_luong_su_kien(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin)
            ->get(route('orders.edit', Crypt::encrypt($order->order_id)))
            ->assertOk()
            ->assertSee('id="hanh-trinh-van-don"', false)
            ->assertSee(route('order.shipment_stream', $order->order_id), false)
            ->assertSee('new EventSource', false);
    }
}
