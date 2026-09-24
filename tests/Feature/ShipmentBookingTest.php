<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\GhnCarrier;
use App\Services\Shipping\ShipmentTracker;
use App\Services\Shipping\ShippingCarrier;
use App\Services\Shipping\ShippingUnavailable;
use App\Services\ShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Handing a parcel to the carrier.
 *
 * This is the one call in the integration with a consequence outside the
 * database: against the production host it books a courier who turns up at the
 * shop. Hence the config gate, and hence these tests.
 */
class ShipmentBookingTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const MOI_DAT = OrderStatus::New;

    private const DA_XAC_NHAN = OrderStatus::Confirmed;

    private const DA_HUY = OrderStatus::Cancelled;

    private const HOAN_HANG = OrderStatus::Returned;

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

    private function makeOrder(string $slug = 'giay-van-don', bool $withCodes = true): OrderModel
    {
        $product = $this->makeProduct(price: 1_000_000, slug: $slug);
        $product->update(['pro_weight' => 1100]);

        $address = $this->makeAddress($this->customer);

        $order = app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($product, quantity: 2)],
            null,
            $address,
            ['payment' => 'COD', 'note_customer' => 'Gọi trước khi giao'],
        );

        // Checkout will not write one of these any more, but orders placed
        // before the carrier ids existed are still sitting in the table.
        if (! $withCodes) {
            $order->forceFill(['order_district_id' => null, 'order_ward_code' => null])->save();
        }

        // Checkout leaves an order waiting for the shop to look at it; a parcel
        // only belongs to one the shop has already confirmed.
        $order->update(['order_status' => self::DA_XAC_NHAN]);

        return $order->fresh();
    }

    // ------------------------------------------------------- dịch vụ

    public function test_tao_van_don_luu_ma_va_ngay_giao(): void
    {
        $order = $this->makeOrder();

        $booking = app(ShippingService::class)->book($order);

        $this->assertSame('FAKE001', $booking->code);
        $this->assertSame('FAKE001', $order->fresh()->order_shipping_code);
        $this->assertSame(
            now()->addDays(3)->toDateString(),
            $order->fresh()->order_expected_delivery,
        );
    }

    public function test_gui_dung_dia_chi_nguoi_nhan_va_can_nang(): void
    {
        $order = $this->makeOrder();

        app(ShippingService::class)->book($order);

        $sent = $this->carrier->booked[0];

        $this->assertSame($order->order_code, $sent->reference, 'client_order_code phải là mã đơn của shop');
        $this->assertSame(1442, $sent->toDistrictId);
        $this->assertSame('20110', $sent->toWardCode);
        $this->assertSame(2200, $sent->weight, 'Hai đôi 1100g');
        $this->assertCount(1, $sent->items);
        $this->assertSame(2, $sent->items[0]['quantity']);
    }

    public function test_don_chua_thanh_toan_thi_thu_ho_tien(): void
    {
        $order = $this->makeOrder();

        app(ShippingService::class)->book($order);

        $this->assertSame((int) $order->order_total, $this->carrier->booked[0]->codAmount);
    }

    public function test_don_da_thanh_toan_thi_khong_thu_ho(): void
    {
        $order = $this->makeOrder();
        $order->update(['order_payment_status' => 1]);

        app(ShippingService::class)->book($order->fresh());

        $this->assertSame(0, $this->carrier->booked[0]->codAmount);
    }

    public function test_khong_tao_hai_van_don_cho_mot_don(): void
    {
        $order = $this->makeOrder();
        app(ShippingService::class)->book($order);

        $this->expectException(ShippingUnavailable::class);

        app(ShippingService::class)->book($order->fresh());
    }

    public function test_don_thieu_ma_vung_thi_khong_tao_duoc(): void
    {
        $order = $this->makeOrder(slug: 'giay-thieu-vung', withCodes: false);

        $this->expectException(ShippingUnavailable::class);

        app(ShippingService::class)->book($order);
    }

    /**
     * The gate is the whole point: a production token plus a stray click is a
     * courier at the door.
     */
    public function test_tat_co_thi_khong_goi_hang_van_chuyen(): void
    {
        $order = $this->makeOrder();

        config(['services.ghn.create_orders' => false]);
        $this->app->instance(ShippingCarrier::class, new GhnCarrier);
        config(['services.ghn.token' => 'co-token-that']);

        $this->expectException(ShippingUnavailable::class);

        app(ShippingService::class)->book($order);
    }

    /**
     * The fee GHN bills the shop is the fee the shop charged the customer, and
     * that only holds while both calls declare the same value.
     */
    public function test_khai_gia_bang_gia_tri_hang_khong_gom_phi_ship(): void
    {
        $order = $this->makeOrder('giay-khai-gia');

        app(ShippingService::class)->book($order);

        $hangHoa = 2 * 1_000_000;

        $this->assertSame($hangHoa, $this->carrier->booked[0]->insuranceValue);
        $this->assertGreaterThan(
            $hangHoa,
            (int) $order->fresh()->order_total,
            'Tổng đơn phải lớn hơn tiền hàng, nếu không thì test này không chứng minh được gì'
        );
    }

    public function test_khai_gia_khop_voi_luc_bao_gia(): void
    {
        $order = $this->makeOrder('giay-khop-bao-gia');

        app(ShippingService::class)->book($order);

        $luocBaoGia = $this->carrier->quoted[0];
        $luocDatVanDon = $this->carrier->booked[0];

        $this->assertSame($luocBaoGia->insuranceValue, $luocDatVanDon->insuranceValue);
        $this->assertSame($luocBaoGia->weight, $luocDatVanDon->weight);
    }

    /**
     * COD is the other way round: at the door the customer really does pay for
     * the goods and the postage together.
     */
    public function test_tien_thu_ho_van_gom_ca_phi_ship(): void
    {
        $order = $this->makeOrder('giay-thu-ho');

        app(ShippingService::class)->book($order);

        $this->assertSame((int) $order->fresh()->order_total, $this->carrier->booked[0]->codAmount);
    }

    // ----------------------------------------------- huỷ rồi tạo lại vận đơn

    public function test_huy_van_don_thi_tra_lai_ma_de_tao_don_khac(): void
    {
        $order = $this->makeOrder('giay-tra-ma');
        app(ShippingService::class)->book($order);

        app(ShippingService::class)->cancelBooking($order->fresh());

        $order = $order->fresh();
        $this->assertNull($order->order_shipping_code, 'Còn mã cũ thì không tạo được vận đơn mới');
        $this->assertNull($order->order_expected_delivery, 'Ngày giao của kiện đã huỷ không còn nghĩa gì');
        $this->assertSame('cancel', $order->order_shipping_status);
    }

    public function test_tao_lai_van_don_sau_khi_huy(): void
    {
        $order = $this->makeOrder('giay-tao-lai');
        app(ShippingService::class)->book($order);
        app(ShippingService::class)->cancelBooking($order->fresh());

        app(ShippingService::class)->book($order->fresh());

        $order = $order->fresh();
        $this->assertSame('FAKE002', $order->order_shipping_code, 'Mã mới, không phải mã cũ');
        $this->assertNull($order->order_shipping_status, 'Kiện mới chưa có trạng thái của riêng nó');
        $this->assertCount(2, $this->carrier->booked);
    }

    public function test_nut_tao_van_don_hien_lai_sau_khi_huy(): void
    {
        $order = $this->makeOrder('giay-nut-hien-lai');
        app(ShippingService::class)->book($order);
        app(ShippingService::class)->cancelBooking($order->fresh());

        $this->actingAs($this->admin)
            ->get(route('orders.edit', Crypt::encrypt($order->order_id)))
            ->assertOk()
            ->assertSee('Tạo vận đơn GHN')
            ->assertDontSee('Huỷ vận đơn');
    }

    /**
     * The shop let that parcel go; a callback arriving afterwards must not put
     * its code back on the order and block the next booking.
     */
    public function test_webhook_muon_khong_gan_lai_ma_da_huy(): void
    {
        $order = $this->makeOrder('giay-webhook-muon');
        app(ShippingService::class)->book($order);
        app(ShippingService::class)->cancelBooking($order->fresh());

        app(ShipmentTracker::class)->record([
            'OrderCode' => 'FAKE001',
            'ClientOrderCode' => $order->order_code,
            'Status' => 'ready_to_pick',
            'Time' => now()->toIso8601String(),
        ]);

        $this->assertNull($order->fresh()->order_shipping_code);
    }

    public function test_hanh_trinh_chi_hien_kien_dang_giao(): void
    {
        $order = $this->makeOrder('giay-hanh-trinh');
        app(ShippingService::class)->book($order);

        app(ShipmentTracker::class)->record([
            'OrderCode' => 'FAKE001',
            'ClientOrderCode' => $order->order_code,
            'Status' => 'ready_to_pick',
            'Time' => now()->subHour()->toIso8601String(),
        ]);

        app(ShippingService::class)->cancelBooking($order->fresh());
        app(ShippingService::class)->book($order->fresh());

        app(ShipmentTracker::class)->record([
            'OrderCode' => 'FAKE002',
            'ClientOrderCode' => $order->order_code,
            'Status' => 'picked',
            'Time' => now()->toIso8601String(),
        ]);

        $order = $order->fresh();

        $this->assertCount(2, $order->shipmentEvents, 'Lịch sử kiện cũ vẫn phải được giữ lại');
        $this->assertSame(
            ['picked'],
            $order->currentShipmentEvents()->pluck('status')->all(),
            'Hành trình chỉ được hiện sự kiện của kiện đang mang mã hiện tại'
        );
    }

    public function test_su_kien_ghi_kem_ma_van_don(): void
    {
        $order = $this->makeOrder('giay-ghi-ma');
        app(ShippingService::class)->book($order);

        app(ShipmentTracker::class)->record([
            'OrderCode' => 'FAKE001',
            'ClientOrderCode' => $order->order_code,
            'Status' => 'ready_to_pick',
            'Time' => now()->toIso8601String(),
        ]);

        $this->assertSame('FAKE001', $order->fresh()->shipmentEvents->first()->shipping_code);
    }

    public function test_hanh_trinh_liet_ke_cac_van_don_truoc_do(): void
    {
        $order = $this->makeOrder('giay-van-don-cu');
        app(ShippingService::class)->book($order);

        app(ShipmentTracker::class)->record([
            'OrderCode' => 'FAKE001',
            'ClientOrderCode' => $order->order_code,
            'Status' => 'ready_to_pick',
            'Time' => now()->subHour()->toIso8601String(),
        ]);

        app(ShippingService::class)->cancelBooking($order->fresh());
        app(ShippingService::class)->book($order->fresh());

        $this->actingAs($this->admin)
            ->get(route('orders.edit', Crypt::encrypt($order->order_id)))
            ->assertOk()
            ->assertSee('1 vận đơn trước đó')
            ->assertSee('FAKE001')
            ->assertSee('FAKE002');
    }

    public function test_don_chi_giao_mot_lan_thi_khong_co_muc_van_don_cu(): void
    {
        $order = $this->makeOrder('giay-giao-mot-lan');
        app(ShippingService::class)->book($order);

        $this->assertTrue($order->fresh()->previousShipments()->isEmpty());

        $this->actingAs($this->admin)
            ->get(route('orders.edit', Crypt::encrypt($order->order_id)))
            ->assertOk()
            ->assertDontSee('vận đơn trước đó');
    }

    // ------------------------------------------------- nút huỷ đứng cạnh mã

    public function test_nut_huy_nam_canh_ma_van_don_trong_hanh_trinh(): void
    {
        $order = $this->makeOrder('giay-nut-huy');
        app(ShippingService::class)->book($order);

        $html = $this->actingAs($this->admin)
            ->get(route('orders.edit', Crypt::encrypt($order->order_id)))
            ->assertOk()
            ->getContent();

        $viTriMa = strpos($html, 'Mã vận đơn');
        $viTriNutHuy = strpos($html, 'Huỷ vận đơn');

        $this->assertNotFalse($viTriNutHuy, 'Đơn đã có vận đơn thì phải huỷ được');
        $this->assertGreaterThan($viTriMa, $viTriNutHuy, 'Nút huỷ phải nằm trong khối hành trình, cạnh mã');
    }

    public function test_khach_khong_thay_nut_huy_van_don(): void
    {
        $order = $this->makeOrder('giay-khach-xem');
        app(ShippingService::class)->book($order);

        $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->assertSee('Mã vận đơn')
            ->assertDontSee('Huỷ vận đơn');
    }

    public function test_o_nhap_ma_khong_lap_lai_ma_dang_hien_o_hanh_trinh(): void
    {
        $order = $this->makeOrder('giay-khong-lap');

        $chuaCoVanDon = $this->actingAs($this->admin)
            ->get(route('orders.edit', Crypt::encrypt($order->order_id)))
            ->assertOk();

        $chuaCoVanDon->assertSee('Tạo vận đơn GHN');
        $chuaCoVanDon->assertSee('Hoặc gắn mã vận đơn đã tạo sẵn trên GHN');

        app(ShippingService::class)->book($order);

        $this->actingAs($this->admin)
            ->get(route('orders.edit', Crypt::encrypt($order->order_id)))
            ->assertOk()
            ->assertSee('Sửa mã vận đơn')
            ->assertDontSee('Tạo vận đơn GHN');
    }

    public function test_hien_ngay_du_kien_giao_sau_khi_tao_van_don(): void
    {
        $order = $this->makeOrder('giay-ngay-du-kien');
        app(ShippingService::class)->book($order);

        $ngayGiao = now()->addDays(3)->format('d/m/Y');

        $this->actingAs($this->admin)
            ->get(route('orders.edit', Crypt::encrypt($order->order_id)))
            ->assertOk()
            ->assertSee('Dự kiến giao: '.$ngayGiao);
    }

    /**
     * The whole page is wrapped in the order-update form. A form nested inside
     * another is dropped by the browser, and its submit button then posts the
     * outer one: clicking "Tạo vận đơn" would change the order status instead
     * of reaching the carrier.
     */
    public function test_khong_co_form_long_nhau_tren_trang_chi_tiet_don(): void
    {
        $order = $this->makeOrder('giay-form-long');

        $html = $this->actingAs($this->admin)
            ->get(route('orders.edit', Crypt::encrypt($order->order_id)))
            ->assertOk()
            ->getContent();

        $doSau = 0;
        foreach (preg_split('#(<form\b|</form>)#i', $html, -1, PREG_SPLIT_DELIM_CAPTURE) as $manh) {
            if (stripos($manh, '<form') === 0) {
                $doSau++;
                $this->assertLessThanOrEqual(1, $doSau, 'Có form lồng trong form');
            } elseif (strcasecmp($manh, '</form>') === 0) {
                $doSau--;
            }
        }

        $this->assertSame(0, $doSau, 'Số thẻ mở và đóng form không khớp');
    }

    public function test_nut_tao_van_don_tro_dung_form_dat_van_don(): void
    {
        $order = $this->makeOrder('giay-nut-tro-form');

        $html = $this->actingAs($this->admin)
            ->get(route('orders.edit', Crypt::encrypt($order->order_id)))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('form="form-tao-van-don"', $html);
        $this->assertStringContainsString(
            'id="form-tao-van-don" action="'.route('order.book_shipment', $order->order_id).'"',
            $html
        );
    }

    private function ghiSuKien(OrderModel $order, string $maVanDon, string $trangThai): void
    {
        app(ShipmentTracker::class)->record([
            'OrderCode' => $maVanDon,
            'ClientOrderCode' => $order->order_code,
            'Status' => $trangThai,
            'Time' => now()->toIso8601String(),
        ]);
    }

    // ------------------------------------------- trang tra cứu đơn của khách

    public function test_khach_khong_thay_cac_van_don_da_huy(): void
    {
        $order = $this->makeOrder('giay-khach-khong-thay-cu');
        app(ShippingService::class)->book($order);
        $this->ghiSuKien($order, 'FAKE001', 'ready_to_pick');
        app(ShippingService::class)->cancelBooking($order->fresh());
        app(ShippingService::class)->book($order->fresh());

        $this->assertCount(1, $order->fresh()->previousShipments(), 'Đơn này phải có vận đơn cũ để test có nghĩa');

        $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->assertDontSee('vận đơn trước đó');
    }

    public function test_admin_van_thay_cac_van_don_da_huy(): void
    {
        $order = $this->makeOrder('giay-admin-van-thay-cu');
        app(ShippingService::class)->book($order);
        $this->ghiSuKien($order, 'FAKE001', 'ready_to_pick');
        app(ShippingService::class)->cancelBooking($order->fresh());
        app(ShippingService::class)->book($order->fresh());

        $this->actingAs($this->admin)
            ->get(route('orders.edit', Crypt::encrypt($order->order_id)))
            ->assertOk()
            ->assertSee('1 vận đơn trước đó');
    }

    public function test_hanh_trinh_cua_khach_nam_trong_modal(): void
    {
        $order = $this->makeOrder('giay-modal-hanh-trinh');
        app(ShippingService::class)->book($order);

        $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->assertSee('Xem hành trình')
            ->assertSee('data-bs-target="#HanhTrinhModal"', false)
            ->assertSee('id="HanhTrinhModal"', false)
            ->assertSee('FAKE001');
    }

    // ------------------------------------- chỉ đơn đã xác nhận mới có vận đơn

    /**
     * @return array<string, array{OrderStatus}>
     */
    public static function trangThaiKhongDuocTaoVanDon(): array
    {
        return [
            'đơn hàng mới' => [self::MOI_DAT],
            'đã hủy' => [self::DA_HUY],
            'hoàn hàng' => [self::HOAN_HANG],
            'thành công' => [OrderStatus::Completed],
            'đã có vận đơn' => [OrderStatus::ReadyToShip],
            'đang giao' => [OrderStatus::Delivering],
        ];
    }

    /**
     * A cancelled order has already put its stock back and released its coupon.
     * Letting a parcel out for one sends goods that the books say never left.
     */
    #[DataProvider('trangThaiKhongDuocTaoVanDon')]
    public function test_don_chua_xac_nhan_thi_khong_tao_duoc_van_don(OrderStatus $trangThai): void
    {
        $order = $this->makeOrder('giay-trang-thai-'.$trangThai->value);
        $order->update(['order_status' => $trangThai]);

        $this->actingAs($this->admin)
            ->post(route('order.book_shipment', $order->order_id))
            ->assertSessionHas('message', 'Chỉ đơn hàng đã xác nhận mới gắn được vận đơn!');

        $this->assertNull($order->fresh()->order_shipping_code);
        $this->assertSame([], $this->carrier->booked, 'Không được gọi sang hãng vận chuyển');
    }

    public function test_nut_tao_van_don_an_di_khi_don_chua_duoc_xac_nhan(): void
    {
        $order = $this->makeOrder('giay-an-nut');
        $order->update(['order_status' => self::MOI_DAT]);

        $this->actingAs($this->admin)
            ->get(route('orders.edit', Crypt::encrypt($order->order_id)))
            ->assertOk()
            ->assertDontSee('Tạo vận đơn GHN')
            ->assertDontSee('name="order_shipping_code"', false)
            ->assertSee('Chỉ đơn hàng đã xác nhận mới gắn được vận đơn.');
    }

    public function test_don_da_huy_van_huy_duoc_van_don_da_tao(): void
    {
        $order = $this->makeOrder('giay-huy-sau');
        app(ShippingService::class)->book($order);
        $order->update(['order_status' => self::DA_HUY]);

        $this->actingAs($this->admin)
            ->post(route('order.cancel_shipment', $order->order_id))
            ->assertRedirect();

        $this->assertSame(['FAKE001'], $this->carrier->cancelled, 'Đơn huỷ rồi thì kiện hàng phải gọi về được');
    }

    public function test_huy_van_don(): void
    {
        $order = $this->makeOrder();
        app(ShippingService::class)->book($order);

        app(ShippingService::class)->cancelBooking($order->fresh());

        $this->assertSame(['FAKE001'], $this->carrier->cancelled);
        $this->assertSame('cancel', $order->fresh()->order_shipping_status);
    }

    public function test_chua_co_van_don_thi_khong_huy_duoc(): void
    {
        $order = $this->makeOrder();

        $this->expectException(ShippingUnavailable::class);

        app(ShippingService::class)->cancelBooking($order);
    }

    // ------------------------------------------------------- màn hình admin

    public function test_admin_bam_nut_tao_van_don(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin)
            ->post(route('order.book_shipment', $order->order_id))
            ->assertRedirect();

        $this->assertSame('FAKE001', $order->fresh()->order_shipping_code);
    }

    public function test_loi_tu_hang_van_chuyen_hien_ra_chu_khong_no(): void
    {
        $order = $this->makeOrder();
        $order->update(['order_shipping_code' => 'DA-CO-ROI']);

        $this->actingAs($this->admin)
            ->post(route('order.book_shipment', $order->order_id))
            ->assertRedirect()
            ->assertSessionHas('message', 'Đơn hàng này đã có vận đơn DA-CO-ROI.');
    }

    public function test_nut_tao_van_don_an_di_khi_co_bi_tat(): void
    {
        config(['services.ghn.create_orders' => false]);
        $order = $this->makeOrder();

        $this->actingAs($this->admin)
            ->get(route('orders.edit', Crypt::encrypt($order->order_id)))
            ->assertOk()
            ->assertDontSee('Tạo vận đơn GHN')
            ->assertSee('GHN_CREATE_ORDERS');
    }

    public function test_khach_thuong_khong_tao_duoc_van_don(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->customer)
            ->post(route('order.book_shipment', $order->order_id))
            ->assertRedirect();

        $this->assertNull($order->fresh()->order_shipping_code);
        $this->assertSame([], $this->carrier->booked);
    }
}
