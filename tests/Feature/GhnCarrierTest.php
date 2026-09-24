<?php

namespace Tests\Feature;

use App\Services\Shipping\GhnCarrier;
use App\Services\Shipping\Shipment;
use App\Services\Shipping\ShipmentOrder;
use App\Services\Shipping\ShipmentSender;
use App\Services\Shipping\ShippingUnavailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The GHN client against recorded responses.
 *
 * The bodies below are the shapes the live API returned on 15/09/2026, kept
 * verbatim: GHN answers HTTP 200 with a `code` of its own, so a client that
 * only reads the status treats a refusal as a price.
 */
class GhnCarrierTest extends TestCase
{
    private const HOST = 'https://online-gateway.ghn.vn';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'services.ghn.host' => self::HOST,
            'services.ghn.token' => 'token-cua-shop',
            'services.ghn.shop_id' => 4701794,
            'services.ghn.from_district_id' => 3695,
            'services.ghn.from_ward_code' => '90742',
            'services.ghn.box' => ['length' => 32, 'width' => 22, 'height' => 13],
        ]);
    }

    private function fakeHappyPath(): void
    {
        Http::fake([
            self::HOST.'/shiip/public-api/v2/shipping-order/available-services' => Http::response([
                'code' => 200,
                'data' => [
                    ['service_id' => 100039, 'short_name' => 'Hàng nặng', 'service_type_id' => 5],
                    ['service_id' => 53321, 'short_name' => 'Hàng nhẹ', 'service_type_id' => 2],
                ],
            ]),
            self::HOST.'/shiip/public-api/v2/shipping-order/fee' => Http::response([
                'code' => 200,
                'data' => ['total' => 28501, 'service_fee' => 21001, 'insurance_fee' => 7500],
            ]),
            self::HOST.'/shiip/public-api/v2/shipping-order/leadtime' => Http::response([
                'code' => 200,
                'data' => [
                    'leadtime' => 1789577999,
                    'leadtime_order' => ['to_estimate_date' => '2026-09-16T16:59:59Z'],
                ],
            ]),
        ]);
    }

    public function test_doc_duoc_phi_va_ngay_giao(): void
    {
        $this->fakeHappyPath();

        $quote = (new GhnCarrier)->quote(new Shipment(1442, '20110', 1200, 1_500_000));

        $this->assertSame(28501, $quote->fee);
        $this->assertSame(53321, $quote->serviceId, 'Phải chọn dịch vụ hàng nhẹ, không phải dòng đầu tiên');
        $this->assertSame('2026-09-16', $quote->estimated->toDateString());
    }

    public function test_gui_token_va_shop_id_trong_header(): void
    {
        $this->fakeHappyPath();

        (new GhnCarrier)->quote(new Shipment(1442, '20110', 1200));

        Http::assertSent(fn ($request) => $request->hasHeader('Token', 'token-cua-shop')
            && $request->hasHeader('ShopId', '4701794'));
    }

    public function test_gui_dung_can_nang_va_kich_thuoc_hop(): void
    {
        $this->fakeHappyPath();

        (new GhnCarrier)->quote(new Shipment(1442, '20110', 3600, 4_500_000));

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/shipping-order/fee')) {
                return false;
            }

            return $request['weight'] === 3600
                && $request['length'] === 32
                && $request['width'] === 22
                && $request['height'] === 13
                && $request['insurance_value'] === 4_500_000
                && $request['from_district_id'] === 3695;
        });
    }

    /**
     * GHN answers 200 with its own code, so the status alone says nothing.
     */
    public function test_ghn_tra_200_nhung_code_400_van_la_that_bai(): void
    {
        Http::fake([
            self::HOST.'/*' => Http::response([
                'code' => 400,
                'message' => 'Vui lòng cập nhật thông tin địa chỉ cửa hàng',
                'code_message_value' => 'Sai thông tin đầu vào. Vui lòng thử lại.',
            ], 200),
        ]);

        $this->expectException(ShippingUnavailable::class);

        (new GhnCarrier)->quote(new Shipment(1442, '20110', 1200));
    }

    public function test_khu_vuc_khong_co_dich_vu_bi_tu_choi(): void
    {
        Http::fake([
            self::HOST.'/shiip/public-api/v2/shipping-order/available-services' => Http::response([
                'code' => 200, 'data' => [],
            ]),
        ]);

        $this->expectException(ShippingUnavailable::class);

        (new GhnCarrier)->quote(new Shipment(9999, '00000', 1200));
    }

    /**
     * A route with no leadtime still has a price.
     */
    public function test_khong_co_ngay_giao_van_tra_ve_phi(): void
    {
        Http::fake([
            self::HOST.'/shiip/public-api/v2/shipping-order/available-services' => Http::response([
                'code' => 200, 'data' => [['service_id' => 53321, 'service_type_id' => 2]],
            ]),
            self::HOST.'/shiip/public-api/v2/shipping-order/fee' => Http::response([
                'code' => 200, 'data' => ['total' => 28501],
            ]),
            self::HOST.'/shiip/public-api/v2/shipping-order/leadtime' => Http::response([
                'code' => 500, 'message' => 'lỗi',
            ], 500),
        ]);

        $quote = (new GhnCarrier)->quote(new Shipment(1442, '20110', 1200));

        $this->assertSame(28501, $quote->fee);
        $this->assertNull($quote->estimated);
    }

    public function test_thieu_token_thi_bao_ngay_chu_khong_goi_api(): void
    {
        config(['services.ghn.token' => '']);
        Http::fake();

        $this->expectException(ShippingUnavailable::class);

        try {
            (new GhnCarrier)->quote(new Shipment(1442, '20110', 1200));
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_danh_sach_tinh_thanh_duoc_cache(): void
    {
        Http::fake([
            self::HOST.'/shiip/public-api/master-data/province' => Http::response([
                'code' => 200,
                'data' => [
                    ['ProvinceID' => 269, 'ProvinceName' => 'Lào Cai', 'IsEnable' => 1],
                    ['ProvinceID' => 201, 'ProvinceName' => 'Hà Nội', 'IsEnable' => 1],
                    ['ProvinceID' => 999, 'ProvinceName' => 'Đã ngừng', 'IsEnable' => 0],
                ],
            ]),
        ]);

        $carrier = new GhnCarrier;
        $first = $carrier->provinces();
        $carrier->provinces();

        $this->assertSame(['Hà Nội', 'Lào Cai'], array_column($first, 'name'), 'Bỏ tỉnh đã tắt và sắp theo tên');
        Http::assertSentCount(1);
    }

    public function test_phuong_xa_tra_ve_ma_chu_khong_phai_id(): void
    {
        Http::fake([
            self::HOST.'/shiip/public-api/master-data/ward' => Http::response([
                'code' => 200,
                'data' => [['WardCode' => '20110', 'WardName' => 'Phường Tân Định', 'IsEnable' => 1]],
            ]),
        ]);

        $this->assertSame(
            [['code' => '20110', 'name' => 'Phường Tân Định']],
            (new GhnCarrier)->wards(1442),
        );
    }

    private function bookAndCapture(?ShipmentSender $from): void
    {
        config(['services.ghn.create_orders' => true]);
        Http::fake([
            self::HOST.'/shiip/public-api/v2/shipping-order/create' => Http::response([
                'code' => 200,
                'data' => ['order_code' => 'LTEST1', 'total_fee' => 27000],
            ]),
        ]);

        (new GhnCarrier)->book(new ShipmentOrder(
            reference: 'DH01-TH',
            toName: 'Sneaker Square',
            toPhone: '0585818504',
            toAddress: '1 Võ Văn Ngân',
            toDistrictId: 3695,
            toWardCode: '90742',
            weight: 1000,
            insuranceValue: 1_000_000,
            codAmount: 0,
            items: [['name' => 'Giày', 'quantity' => 1, 'weight' => 1000]],
            from: $from,
        ));
    }

    public function test_van_don_thuong_lay_hang_tai_kho_shop(): void
    {
        $this->bookAndCapture(null);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/shipping-order/create')
            && $request['from_district_id'] === 3695
            && $request['from_ward_code'] === '90742');
    }

    public function test_van_don_tra_hang_lay_hang_tai_nha_khach(): void
    {
        $this->bookAndCapture(new ShipmentSender('Nguyễn Văn A', '0912345678', '780 Bình Giã', 1442, '20110'));

        Http::assertSent(fn ($request) => str_contains($request->url(), '/shipping-order/create')
            && $request['from_name'] === 'Nguyễn Văn A'
            && $request['from_phone'] === '0912345678'
            && $request['from_district_id'] === 1442
            && $request['from_ward_code'] === '20110'
            && $request['to_district_id'] === 3695
            && $request['cod_amount'] === 0);
    }

    public function test_ghn_tra_ve_success_nhung_tu_choi_tung_van_don_thi_van_la_loi(): void
    {
        Http::fake([
            self::HOST.'/shiip/public-api/v2/switch-status/storing' => Http::response([
                'code' => 200,
                'message' => 'Success',
                'data' => [['order_code' => 'L8TY76', 'result' => false, 'message' => 'Trạng thái đơn hàng không hợp lệ']],
            ]),
        ]);

        $this->expectException(ShippingUnavailable::class);
        $this->expectExceptionMessage('Trạng thái đơn hàng không hợp lệ');

        (new GhnCarrier)->switchStatus('L8TY76', 'storing');
    }

    public function test_ghn_nhan_chuyen_trang_thai_thi_khong_bao_loi(): void
    {
        Http::fake([
            self::HOST.'/shiip/public-api/v2/switch-status/cancel' => Http::response([
                'code' => 200,
                'data' => [['order_code' => 'L8TY76', 'result' => true, 'message' => 'Thành công']],
            ]),
        ]);

        (new GhnCarrier)->cancel('L8TY76');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/switch-status/cancel')
            && $request['order_codes'] === ['L8TY76']);
    }
}
