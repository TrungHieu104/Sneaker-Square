<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Models\OrderModel;
use App\Models\ShipmentEventModel;
use App\Models\UserModel;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\ShippingCarrier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * GHN reporting what happened to a parcel.
 *
 * GHN signs nothing and retries a non-200 ten times, five seconds apart, so
 * two things matter more than usual here: the secret in the URL, and that a
 * repeated callback changes nothing.
 */
class ShipmentWebhookTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const SECRET = 'bi-mat-webhook';

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->app->instance(ShippingCarrier::class, new FakeCarrier);

        config(['services.ghn.webhook_token' => self::SECRET]);

        $this->customer = $this->makeUser();
    }

    private function makeOrder(string $slug = 'giay-van-don'): OrderModel
    {
        $product = $this->makeProduct(slug: $slug);
        $address = $this->makeAddress($this->customer, districtId: 1442, wardCode: '20110');

        return app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($product)],
            null,
            $address,
            ['payment' => 'COD', 'note_customer' => null],
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function ghnPayload(array $overrides = []): array
    {
        // The shape GHN posts, trimmed to the fields this application reads.
        return array_merge([
            'CODAmount' => 0,
            'ClientOrderCode' => '',
            'Description' => 'Đơn hàng đã được tiếp nhận',
            'OrderCode' => 'LFV3G8',
            'Status' => 'ready_to_pick',
            'Time' => '2026-09-16T03:15:00.000Z',
            'TotalFee' => 27000,
            'Type' => 'switch_status',
            'Warehouse' => 'Bưu Cục 229 Quan Nhân-Q.Thanh Xuân-HN',
            'Weight' => 1000,
        ], $overrides);
    }

    private function send(array $payload, ?string $secret = null)
    {
        return $this->postJson(route('webhook.ghn', ['token' => $secret ?? self::SECRET]), $payload);
    }

    // ------------------------------------------------------- bảo mật

    public function test_sai_bi_mat_tren_url_thi_khong_ghi_gi(): void
    {
        $order = $this->makeOrder();
        $order->update(['order_shipping_code' => 'LFV3G8']);

        $this->send($this->ghnPayload(), secret: 'doan-bua')->assertNotFound();

        $this->assertSame(0, ShipmentEventModel::count());
    }

    public function test_chua_cau_hinh_bi_mat_thi_webhook_dong(): void
    {
        config(['services.ghn.webhook_token' => '']);

        $this->postJson('/webhook/ghn/bat-ky-cai-gi', $this->ghnPayload())->assertNotFound();
    }

    // ------------------------------------------------------- ghi nhận

    public function test_ghi_lai_su_kien_va_cap_nhat_trang_thai_don(): void
    {
        $order = $this->makeOrder();
        $order->update(['order_shipping_code' => 'LFV3G8']);

        $this->send($this->ghnPayload())->assertOk()->assertJson(['recorded' => true]);

        $event = ShipmentEventModel::sole();

        $this->assertSame($order->order_id, $event->order_id);
        $this->assertSame('ready_to_pick', $event->status);
        $this->assertSame('Bưu Cục 229 Quan Nhân-Q.Thanh Xuân-HN', $event->warehouse);
        $this->assertSame('ready_to_pick', $order->fresh()->order_shipping_status);
    }

    /**
     * The first callback for a parcel the shop created on GHN's dashboard is
     * where GHN's code gets tied to the order.
     */
    public function test_khop_don_bang_ma_don_cua_shop_va_gan_luon_ma_van_don(): void
    {
        $order = $this->makeOrder();

        $this->send($this->ghnPayload(['ClientOrderCode' => $order->order_code]))->assertOk();

        $this->assertSame('LFV3G8', $order->fresh()->order_shipping_code);
        $this->assertSame(1, ShipmentEventModel::count());
    }

    public function test_goi_lai_cung_su_kien_khong_tao_dong_trung(): void
    {
        $order = $this->makeOrder();
        $order->update(['order_shipping_code' => 'LFV3G8']);

        $this->send($this->ghnPayload())->assertOk()->assertJson(['recorded' => true]);
        $this->send($this->ghnPayload())->assertOk()->assertJson(['recorded' => false]);
        $this->send($this->ghnPayload())->assertOk();

        $this->assertSame(1, ShipmentEventModel::count(), 'GHN gửi lại tới 10 lần, không được nhân bản');
    }

    public function test_giao_thanh_cong_thi_bat_co_da_giao(): void
    {
        $order = $this->makeOrder();
        $order->update(['order_shipping_code' => 'LFV3G8']);

        $this->send($this->ghnPayload(['Status' => 'delivered', 'Time' => '2026-09-18T09:00:00.000Z']))->assertOk();

        $this->assertSame(1, (int) $order->fresh()->order_delivery_status);
    }

    /**
     * Callbacks arrive out of order often enough that an older one must not
     * roll the order back.
     */
    public function test_su_kien_cu_den_sau_khong_ghi_de_trang_thai_moi(): void
    {
        $order = $this->makeOrder();
        $order->update(['order_shipping_code' => 'LFV3G8']);

        $this->send($this->ghnPayload(['Status' => 'delivering', 'Time' => '2026-09-18T08:00:00.000Z']));
        $this->send($this->ghnPayload(['Status' => 'picked', 'Time' => '2026-09-16T08:00:00.000Z']));

        $this->assertSame('delivering', $order->fresh()->order_shipping_status);
        $this->assertSame(2, ShipmentEventModel::count(), 'Vẫn phải lưu đủ hai mốc');
    }

    public function test_trang_thai_la_van_duoc_luu_chu_khong_bi_bo(): void
    {
        $order = $this->makeOrder();
        $order->update(['order_shipping_code' => 'LFV3G8']);

        $this->send($this->ghnPayload(['Status' => 'mot_trang_thai_moi_cua_ghn']))->assertOk();

        $this->assertSame('mot_trang_thai_moi_cua_ghn', ShipmentEventModel::sole()->status);
    }

    /**
     * GHN would resend a non-200 ten times, and this one can never succeed.
     */
    public function test_don_khong_khop_van_tra_200(): void
    {
        $this->send($this->ghnPayload(['OrderCode' => 'KHONG-CO', 'ClientOrderCode' => 'KHONG-CO']))
            ->assertOk()
            ->assertJson(['message' => 'Không khớp đơn hàng nào']);

        $this->assertSame(0, ShipmentEventModel::count());
    }

    public function test_thieu_truong_status_thi_khong_ghi(): void
    {
        $order = $this->makeOrder();
        $order->update(['order_shipping_code' => 'LFV3G8']);

        $payload = $this->ghnPayload();
        unset($payload['Status']);

        $this->send($payload)->assertOk();

        $this->assertSame(0, ShipmentEventModel::count());
    }

    // ------------------------------------------------------- hiển thị

    public function test_trang_don_hang_cua_khach_in_ra_hanh_trinh(): void
    {
        $order = $this->makeOrder();
        $order->update(['order_shipping_code' => 'LFV3G8']);

        $this->send($this->ghnPayload(['Status' => 'delivering']));

        $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->assertSee('Đang giao hàng')
            ->assertSee('LFV3G8');
    }

    public function test_chua_co_ma_van_don_thi_bao_chua_ban_giao(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->assertSee('Đơn hàng chưa được bàn giao cho đơn vị vận chuyển');
    }
}
