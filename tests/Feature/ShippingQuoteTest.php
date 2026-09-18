<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Http\Controllers\Frontend\ProductController;
use App\Models\DeliveryInfoModel;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\Shipment;
use App\Services\Shipping\ShippingCarrier;
use App\Services\Shipping\ShippingQuote;
use App\Services\Shipping\ShippingUnavailable;
use App\Services\ShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Shipping priced by the carrier, on the server.
 *
 * The browser used to compute the fee itself and post it in a hidden field,
 * with the shop's GHN token handed out so it could. Both of those are what
 * these tests hold shut.
 */
class ShippingQuoteTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private FakeCarrier $carrier;

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();

        $this->carrier = new FakeCarrier;
        $this->app->instance(ShippingCarrier::class, $this->carrier);

        $this->customer = $this->makeUser();
    }

    // ------------------------------------------------------- cân nặng

    public function test_phi_tinh_theo_tong_can_nang_gio_hang(): void
    {
        $product = $this->makeProduct(slug: 'giay-nang');
        $product->update(['pro_weight' => 1200]);

        $quote = app(ShippingService::class)->quoteForCart(
            [$this->cartLine($product, quantity: 3)],
            districtId: 1442,
            wardCode: '20110',
        );

        $this->assertSame(3600, $this->carrier->quoted[0]->weight, 'Ba đôi 1200g phải ra 3600g');
        $this->assertSame(22000 + 4 * 5000, $quote->fee);
    }

    public function test_gio_hang_qua_nhe_van_du_can_toi_thieu(): void
    {
        $product = $this->makeProduct(slug: 'day-giay');
        $product->update(['pro_weight' => 40]);

        app(ShippingService::class)->quoteForCart(
            [$this->cartLine($product)],
            districtId: 1442,
            wardCode: '20110',
        );

        $this->assertSame(100, $this->carrier->quoted[0]->weight, 'Hãng vận chuyển từ chối kiện nặng 0g');
    }

    public function test_gia_tri_khai_bao_bang_gia_tri_gio_hang(): void
    {
        $product = $this->makeProduct(price: 1_500_000, slug: 'giay-khai-gia');

        app(ShippingService::class)->quoteForCart(
            [$this->cartLine($product, quantity: 2)],
            districtId: 1442,
            wardCode: '20110',
        );

        $this->assertSame(3_000_000, $this->carrier->quoted[0]->insuranceValue);
    }

    // ------------------------------------------------------- endpoint

    public function test_endpoint_bao_gia_tra_ve_phi_va_ngay_du_kien(): void
    {
        $product = $this->makeProduct(slug: 'giay-bao-gia');

        $this->actingAs($this->customer)
            ->withSession(['cart' => [$this->cartLine($product)]])
            ->postJson(route('shipping.quote'), ['district_id' => 1442, 'ward_code' => '20110'])
            ->assertOk()
            ->assertJsonStructure(['fee', 'fee_text', 'estimated', 'estimated_text']);
    }

    public function test_endpoint_bao_gia_can_dia_chi_day_du(): void
    {
        $this->actingAs($this->customer)
            ->postJson(route('shipping.quote'), ['district_id' => 1442])
            ->assertStatus(422);
    }

    public function test_endpoint_tra_ve_503_khi_hang_van_chuyen_tu_choi(): void
    {
        $this->app->instance(ShippingCarrier::class, new class extends FakeCarrier
        {
            public function quote(Shipment $shipment): ShippingQuote
            {
                throw new ShippingUnavailable('GHN không giao tới khu vực này.');
            }
        });

        $product = $this->makeProduct(slug: 'giay-vung-xa');

        $this->actingAs($this->customer)
            ->withSession(['cart' => [$this->cartLine($product)]])
            ->postJson(route('shipping.quote'), ['district_id' => 9999, 'ward_code' => '00000'])
            ->assertStatus(503)
            ->assertJson(['message' => 'GHN không giao tới khu vực này.']);
    }

    public function test_danh_sach_tinh_thanh_khong_lo_token(): void
    {
        config(['services.ghn.token' => 'bi-mat-cua-shop']);

        $response = $this->actingAs($this->customer)
            ->getJson(route('shipping.provinces'))
            ->assertOk();

        $this->assertStringNotContainsString('bi-mat-cua-shop', $response->getContent());
    }

    /**
     * The URI still matches a catch-all GET route, so the check is that no
     * verb on it hands out a credential any more.
     */
    public function test_khong_con_route_phat_token_cho_trinh_duyet(): void
    {
        $response = $this->actingAs($this->customer)->post('/token-delivery');

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse(
            method_exists(ProductController::class, 'getToken'),
            'Không còn action nào phát token GHN ra trình duyệt',
        );
    }

    // ------------------------------------------------------- đặt hàng

    public function test_don_hang_luu_phi_do_hang_van_chuyen_bao(): void
    {
        $product = $this->makeProduct(price: 1_000_000, slug: 'giay-dat-hang');
        $product->update(['pro_weight' => 1000]);

        // 30000 is what the address carries; the carrier says something else,
        // and the carrier is the one that gets paid.
        $address = $this->makeAddress($this->customer, shippingFee: 30000, districtId: 1442, wardCode: '20110');

        $order = app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($product)],
            null,
            $address,
            ['payment' => 'COD', 'note_customer' => null],
        );

        $this->assertSame(27000, (int) $order->order_delivery_fee);
        $this->assertSame(1_027_000, (int) $order->order_total);
    }

    public function test_don_hang_luu_ngay_giao_du_kien(): void
    {
        $product = $this->makeProduct(slug: 'giay-ngay-giao');
        $address = $this->makeAddress($this->customer, districtId: 1442, wardCode: '20110');

        $order = app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($product)],
            null,
            $address,
            ['payment' => 'COD', 'note_customer' => null],
        );

        $this->assertSame(
            now()->addDays(3)->toDateString(),
            OrderModel::find($order->order_id)->order_expected_delivery,
        );
    }

    public function test_hang_van_chuyen_chet_thi_khong_dat_duoc_don(): void
    {
        $this->app->instance(ShippingCarrier::class, new class extends FakeCarrier
        {
            public function quote(Shipment $shipment): ShippingQuote
            {
                throw new ShippingUnavailable('Hết thời gian chờ GHN.');
            }
        });

        $product = $this->makeProduct(price: 1_000_000, slug: 'giay-ghn-chet');
        $address = $this->makeAddress($this->customer, shippingFee: 31000, districtId: 1442, wardCode: '20110');

        $this->expectException(ShippingUnavailable::class);

        try {
            app(PlaceOrderAction::class)->execute(
                $this->customer,
                [$this->cartLine($product)],
                null,
                $address,
                ['payment' => 'COD', 'note_customer' => null],
            );
        } finally {
            $this->assertSame(0, OrderModel::count(), 'Không báo được giá thì không được ghi đơn');
        }
    }

    public function test_dia_chi_chua_co_ma_vung_thi_khong_bao_gia_duoc(): void
    {
        $product = $this->makeProduct(slug: 'giay-dia-chi-cu');
        $address = $this->makeAddress($this->customer, shippingFee: 25000, districtId: null, wardCode: null);

        $quote = app(ShippingService::class)->quoteForAddress([$this->cartLine($product)], $address);

        $this->assertNull($quote, 'Phí đã lưu không phải giá của giỏ hàng này');
        $this->assertSame([], $this->carrier->quoted, 'Không có mã vùng thì đừng gọi hãng vận chuyển');
    }

    public function test_chua_chon_dia_chi_thi_khong_co_phi_van_chuyen(): void
    {
        $product = $this->makeProduct(slug: 'giay-chua-co-dia-chi');

        $quote = app(ShippingService::class)->quoteForAddress([$this->cartLine($product)], null);

        $this->assertNull($quote, 'Không được bịa ra một con số phí khi chưa biết giao đi đâu');
        $this->assertSame([], $this->carrier->quoted);
    }

    // ------------------------------------------------------- lưu địa chỉ

    public function test_luu_dia_chi_giu_lai_ma_vung_cua_hang_van_chuyen(): void
    {
        $this->actingAs($this->customer)
            ->post(route('diachi.store'), $this->addressPayload())
            ->assertSessionHasNoErrors();

        $saved = DeliveryInfoModel::where('user_id', $this->customer->user_id)->latest('info_id')->first();

        $this->assertSame(1442, (int) $saved->info_district_id);
        $this->assertSame('20110', $saved->info_ward_code);
    }

    public function test_phi_luu_theo_hang_van_chuyen_chu_khong_theo_form(): void
    {
        $this->actingAs($this->customer)
            ->post(route('diachi.store'), $this->addressPayload(['infoFeeForm' => 1]))
            ->assertSessionHasNoErrors();

        $saved = DeliveryInfoModel::where('user_id', $this->customer->user_id)->latest('info_id')->first();

        $this->assertSame(32000, (int) $saved->info_delivery_fee, 'Phí do hãng báo, không phải số form gửi lên');
    }

    public function test_thieu_ma_vung_thi_khong_luu_duoc_dia_chi(): void
    {
        $payload = $this->addressPayload();
        unset($payload['info_ward_code']);

        $this->actingAs($this->customer)
            ->post(route('diachi.store'), $payload)
            ->assertSessionHasErrors('info_ward_code');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function addressPayload(array $overrides = []): array
    {
        return array_merge([
            'info_name' => 'Nguyễn Văn B',
            'info_phone' => '0912345678',
            'info_email' => 'khachmoi@gmail.com',
            'info_address' => '2 Võ Văn Ngân',
            'info_province' => 'Hà Nội',
            'info_district' => 'Quận Ba Đình',
            'info_ward' => 'Phường Tân Định',
            'info_district_id' => 1442,
            'info_ward_code' => '20110',
        ], $overrides);
    }

    // ------------------------------------------------------- trang thanh toán

    public function test_trang_thanh_toan_in_phi_va_ngay_giao_cua_hang_van_chuyen(): void
    {
        $product = $this->makeProduct(price: 1_000_000, slug: 'giay-trang-tt');
        $product->update(['pro_weight' => 1000]);
        $this->makeAddress($this->customer, shippingFee: 30000, districtId: 1442, wardCode: '20110');

        $this->actingAs($this->customer)
            ->withSession(['cart' => [$this->cartLine($product)]])
            ->get(route('product.checkout'))
            ->assertOk()
            ->assertSee('27.000')
            ->assertDontSee('22 Th10');
    }
}
