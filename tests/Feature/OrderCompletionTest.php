<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Enums\OrderStatus;
use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\ShippingCarrier;
use App\Services\ShippingService;
use App\Services\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * An order is complete when the customer has the goods: they say so, or the
 * configured number of days passes after GHN reports the parcel delivered.
 * A parcel GHN brings back instead goes back on the shelf.
 */
class OrderCompletionTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const SECRET = 'bi-mat-webhook';

    private const MA_VAN_DON = 'LGIAO01';

    private UserModel $customer;

    private ProductModel $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->app->instance(ShippingCarrier::class, new FakeCarrier);
        config(['services.ghn.webhook_token' => self::SECRET]);

        $this->customer = $this->makeUser();
    }

    /**
     * An order the shop has confirmed and handed to GHN under MA_VAN_DON.
     */
    private function makeShippedOrder(int $quantity = 1): OrderModel
    {
        $this->product = $this->makeProduct(slug: 'giay-giao-hang', stock: 10);
        $address = $this->makeAddress($this->customer);

        $order = app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($this->product, $quantity)],
            null,
            $address,
            ['payment' => 'COD', 'note_customer' => null],
        );

        $order->order_status = OrderStatus::ReadyToShip;
        $order->order_shipping_code = self::MA_VAN_DON;
        $order->save();

        return $order->fresh();
    }

    private function ghn(string $status, string $time = '2026-09-20T03:00:00.000Z', string $code = self::MA_VAN_DON)
    {
        return $this->postJson(route('webhook.ghn', ['token' => self::SECRET]), [
            'OrderCode' => $code,
            'ClientOrderCode' => '',
            'Status' => $status,
            'Time' => $time,
            'Warehouse' => 'Bưu Cục 38E Cây Keo-Q.Thủ Đức-HCM',
            'Description' => 'Cập nhật trạng thái',
        ])->assertOk();
    }

    private function confirmReceived(OrderModel $order, ?UserModel $as = null)
    {
        return $this->actingAs($as ?? $this->customer)
            ->from(route('orderBill.checkout', $order->order_code))
            ->post(route('success.order'), ['order_code' => $order->order_code]);
    }

    private function stock(): int
    {
        return (int) ProductQuantityModel::where('pro_id', $this->product->pro_id)->value('quantity');
    }

    /**
     * What the order should add to the report: the lines as sold.
     *
     * @return array{int, int}
     */
    private function expectedRevenue(OrderModel $order): array
    {
        $lines = OrderDetailModel::where('order_id', $order->order_id)->get();

        return [
            (int) $lines->sum(fn ($l) => $l->price * $l->quantity),
            (int) $lines->sum(fn ($l) => ($l->price - $l->capital_price) * $l->quantity),
        ];
    }

    /**
     * An order the shop carried out itself, with no GHN parcel behind it.
     */
    private function makeHandedOverOrder(): OrderModel
    {
        $order = $this->makeShippedOrder();
        $order->forceFill([
            'order_shipping_code' => null,
            'order_delivery_status' => 1,
            'order_status' => OrderStatus::Delivering,
        ])->save();

        return $order->fresh();
    }

    private function makeOrderAdmin(): UserModel
    {
        $admin = $this->makeUser(email: 'donhang@example.test', username: 'quanlydon', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web'));

        return $admin;
    }

    // -------------------------------------------- GHN hoặc giao thủ công

    public function test_don_da_co_van_don_ghn_thi_khong_ban_giao_thu_cong_duoc(): void
    {
        $order = $this->makeShippedOrder();

        $this->actingAs($this->makeOrderAdmin())
            ->put(route('order.update', $order->order_id), ['note' => '', 'action' => 'handover']);

        $this->assertSame(0, (int) $order->fresh()->order_delivery_status);
    }

    public function test_don_da_ban_giao_thu_cong_thi_khong_tao_van_don_ghn_duoc(): void
    {
        $order = $this->makeHandedOverOrder();

        $this->actingAs($this->makeOrderAdmin())
            ->post(route('order.book_shipment', $order->order_id))
            ->assertSessionHas('message', 'Đơn này đã bàn giao cho đơn vị vận chuyển của shop, không dùng vận đơn GHN được!');

        $this->assertNull($order->fresh()->order_shipping_code);
    }

    public function test_don_da_ban_giao_thu_cong_thi_khong_gan_ma_van_don_duoc(): void
    {
        $order = $this->makeHandedOverOrder();

        $this->actingAs($this->makeOrderAdmin())
            ->patch(route('order.shipping_code', $order->order_id), ['order_shipping_code' => 'LTAY01']);

        $this->assertNull($order->fresh()->order_shipping_code);
    }

    public function test_trang_don_an_nut_ban_giao_khi_da_co_van_don_ghn(): void
    {
        $order = $this->makeShippedOrder();

        $this->actingAs($this->makeOrderAdmin())
            ->get(route('orders.edit', encrypt($order->order_id)))
            ->assertOk()
            ->assertDontSee('Bàn giao vận chuyển')
            ->assertSee('In hóa đơn');
    }

    public function test_trang_don_an_nut_tao_van_don_khi_da_ban_giao_thu_cong(): void
    {
        $order = $this->makeHandedOverOrder();

        $this->actingAs($this->makeOrderAdmin())
            ->get(route('orders.edit', encrypt($order->order_id)))
            ->assertOk()
            ->assertDontSee('Tạo vận đơn GHN')
            ->assertSee('Đã bàn giao cho đơn vị vận chuyển của shop')
            ->assertSee('Huỷ bàn giao');
    }

    public function test_huy_van_don_ghn_thi_ban_giao_thu_cong_lai_duoc(): void
    {
        $order = $this->makeShippedOrder();
        app(ShippingService::class)->cancelBooking($order);

        $this->actingAs($this->makeOrderAdmin())
            ->put(route('order.update', $order->order_id), ['note' => '', 'action' => 'handover']);

        $this->assertTrue($order->fresh()->isHandedOverManually());
    }

    public function test_huy_ban_giao_tra_don_ve_chua_chon_cach_giao(): void
    {
        $order = $this->makeHandedOverOrder();

        $this->actingAs($this->makeOrderAdmin())
            ->post(route('order.undo_handover', $order->order_id))
            ->assertSessionHas('message', 'Đã huỷ bàn giao. Chọn lại cách giao cho đơn này.');

        $fresh = $order->fresh();
        $this->assertFalse($fresh->isHandedOverManually());
        $this->assertFalse($fresh->isAwaitingReceipt());
    }

    public function test_huy_ban_giao_xong_thi_tao_van_don_ghn_lai_duoc(): void
    {
        $order = $this->makeHandedOverOrder();
        $admin = $this->makeOrderAdmin();

        $this->actingAs($admin)->post(route('order.undo_handover', $order->order_id));
        $this->actingAs($admin)->post(route('order.book_shipment', $order->order_id));

        $this->assertNotNull($order->fresh()->order_shipping_code);
    }

    public function test_khach_da_xac_nhan_roi_thi_khong_huy_ban_giao_duoc(): void
    {
        $order = $this->makeHandedOverOrder();
        $order->forceFill(['order_status' => OrderStatus::Completed])->save();

        $this->actingAs($this->makeOrderAdmin())
            ->post(route('order.undo_handover', $order->order_id))
            ->assertSessionHas('message', 'Chỉ đơn đang giao thủ công và khách chưa xác nhận mới huỷ bàn giao được!');

        $this->assertSame(1, (int) $order->fresh()->order_delivery_status);
    }

    public function test_don_di_qua_ghn_thi_khong_huy_ban_giao_duoc(): void
    {
        $order = $this->makeShippedOrder();

        $this->actingAs($this->makeOrderAdmin())
            ->post(route('order.undo_handover', $order->order_id))
            ->assertSessionHas('message', 'Chỉ đơn đang giao thủ công và khách chưa xác nhận mới huỷ bàn giao được!');

        $this->assertSame(self::MA_VAN_DON, $order->fresh()->order_shipping_code);
    }

    // ------------------------------------------------ khách xác nhận đã nhận

    public function test_chua_giao_thi_khach_chua_xac_nhan_duoc(): void
    {
        $order = $this->makeShippedOrder();
        $this->ghn('delivering');

        $this->confirmReceived($order)->assertSessionHas('iconMessage', 'error');

        $this->assertSame(OrderStatus::Delivering, $order->fresh()->order_status);
        $this->assertSame(0, DB::table('statistical')->count());
    }

    public function test_khach_xac_nhan_thi_don_thanh_cong_va_cong_doanh_thu(): void
    {
        $order = $this->makeShippedOrder(quantity: 2);
        $this->ghn('delivered');

        $this->confirmReceived($order)->assertSessionHas('iconMessage', 'success');

        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::Completed, $fresh->order_status);
        $this->assertNotNull($fresh->order_completed_at);

        [$sales, $profit] = $this->expectedRevenue($order);
        $row = DB::table('statistical')->first();
        $this->assertSame($sales, (int) $row->sales);
        $this->assertSame($profit, (int) $row->profit);
    }

    public function test_xac_nhan_hai_lan_chi_cong_doanh_thu_mot_lan(): void
    {
        $order = $this->makeShippedOrder();
        $this->ghn('delivered');

        $this->confirmReceived($order);
        $this->confirmReceived($order)->assertSessionHas('iconMessage', 'error');

        [$sales] = $this->expectedRevenue($order);
        $this->assertSame($sales, (int) DB::table('statistical')->value('sales'));
    }

    public function test_khong_xac_nhan_duoc_don_cua_nguoi_khac(): void
    {
        $order = $this->makeShippedOrder();
        $this->ghn('delivered');
        $stranger = $this->makeUser(email: 'nguoila@example.test', username: 'nguoila');

        $this->confirmReceived($order, $stranger)->assertNotFound();

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->order_status);
    }

    public function test_don_giao_khong_qua_ghn_van_xac_nhan_duoc_sau_khi_ban_giao(): void
    {
        $order = $this->makeHandedOverOrder();

        $this->confirmReceived($order)->assertSessionHas('iconMessage', 'success');

        $this->assertSame(OrderStatus::Completed, $order->fresh()->order_status);
    }

    public function test_ban_giao_van_chuyen_khong_con_cong_doanh_thu(): void
    {
        $order = $this->makeShippedOrder();

        $this->actingAs($this->makeOrderAdmin())->put(route('order.update', $order->order_id), [
            'note' => '',
            'status' => 1,
            'deli' => 1,
            'cou_val' => 0,
        ]);

        $this->assertSame(0, DB::table('statistical')->count());
    }

    public function test_nut_da_nhan_hang_chi_bam_duoc_khi_ghn_bao_da_giao(): void
    {
        $order = $this->makeShippedOrder();
        $trang = route('orderBill.checkout', $order->order_code);
        $formXacNhan = 'action="'.route('success.order').'"';

        $this->ghn('delivering');
        $this->actingAs($this->customer)->get($trang)->assertOk()->assertDontSee($formXacNhan, false);

        $this->ghn('delivered', '2026-09-20T05:00:00.000Z');
        $this->actingAs($this->customer)->get($trang)->assertOk()->assertSee($formXacNhan, false);
    }

    // --------------------------------------------------- tự động hoàn thành

    public function test_ghn_bao_da_giao_thi_ghi_moc_thoi_gian_mot_lan(): void
    {
        $order = $this->makeShippedOrder();

        $this->ghn('delivered', '2026-09-20T03:00:00.000Z');
        $moc = $order->fresh()->order_delivered_at;
        $this->assertNotNull($moc);

        // GHN resends with a later stamp; the waiting period must not restart.
        $this->ghn('delivered', '2026-09-21T03:00:00.000Z');
        $this->assertTrue($moc->equalTo($order->fresh()->order_delivered_at));
    }

    public function test_so_ngay_mac_dinh_la_7(): void
    {
        $this->assertSame(7, app(ShopSettings::class)->autoCompleteDays());
    }

    public function test_tu_hoan_thanh_don_giao_qua_so_ngay_cau_hinh(): void
    {
        app(ShopSettings::class)->setAutoCompleteDays(3);
        $order = $this->makeShippedOrder();
        $this->ghn('delivered', now()->subDays(4)->toIso8601String());

        $this->artisan('orders:auto-complete')->assertSuccessful();

        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::Completed, $fresh->order_status);
        $this->assertSame(1, DB::table('statistical')->count());
    }

    public function test_chua_du_so_ngay_thi_chua_tu_hoan_thanh(): void
    {
        app(ShopSettings::class)->setAutoCompleteDays(3);
        $order = $this->makeShippedOrder();
        $this->ghn('delivered', now()->subDays(2)->toIso8601String());

        $this->artisan('orders:auto-complete')->assertSuccessful();

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->order_status);
    }

    public function test_doi_so_ngay_ap_dung_cho_ca_don_dang_cho(): void
    {
        $order = $this->makeShippedOrder();
        $this->ghn('delivered', now()->subDays(4)->toIso8601String());

        $this->artisan('orders:auto-complete');
        $this->assertSame(OrderStatus::Delivered, $order->fresh()->order_status, '4 ngày < 7 ngày mặc định');

        app(ShopSettings::class)->setAutoCompleteDays(3);
        $this->artisan('orders:auto-complete');
        $this->assertSame(OrderStatus::Completed, $order->fresh()->order_status);
    }

    public function test_tu_hoan_thanh_bo_qua_don_chua_giao(): void
    {
        Carbon::setTestNow(now()->addDays(60));
        $order = $this->makeShippedOrder();
        $this->ghn('delivering', now()->subDays(30)->toIso8601String());

        $this->artisan('orders:auto-complete');

        $this->assertSame(OrderStatus::Delivering, $order->fresh()->order_status);
        Carbon::setTestNow();
    }

    public function test_tu_hoan_thanh_khong_dung_vao_don_khach_da_xac_nhan(): void
    {
        app(ShopSettings::class)->setAutoCompleteDays(1);
        $order = $this->makeShippedOrder();
        $this->ghn('delivered', now()->subDays(2)->toIso8601String());
        $this->confirmReceived($order);

        $this->artisan('orders:auto-complete');

        [$sales] = $this->expectedRevenue($order);
        $this->assertSame($sales, (int) DB::table('statistical')->value('sales'));
    }

    // ------------------------------------------------------ cấu hình ở admin

    public function test_admin_luu_so_ngay_tu_hoan_thanh(): void
    {
        $this->actingAs($this->makeOrderAdmin())
            ->put(route('setting.update'), ['auto_complete_days' => 5, 'return_days' => 7])
            ->assertRedirect(route('setting.edit'));

        $this->assertSame(5, app(ShopSettings::class)->autoCompleteDays());
    }

    public function test_trang_cau_hinh_hien_so_ngay_dang_dung(): void
    {
        app(ShopSettings::class)->setAutoCompleteDays(12);

        $this->actingAs($this->makeOrderAdmin())
            ->get(route('setting.edit'))
            ->assertOk()
            ->assertSee('name="auto_complete_days"', false)
            ->assertSee('value="12"', false);
    }

    public function test_trang_cau_hinh_chia_tab_don_hang_va_chung(): void
    {
        $this->actingAs($this->makeOrderAdmin())
            ->get(route('setting.edit'))
            ->assertSee('data-bs-target="#tab-don-hang"', false)
            ->assertSee('data-bs-target="#tab-chung"', false);
    }

    public function test_menu_giao_hang_tro_toi_trang_quan_ly_cua_ghn(): void
    {
        config(['services.ghn.dashboard' => 'https://5sao.ghn.dev']);

        $this->actingAs($this->makeOrderAdmin())
            ->get(route('setting.edit'))
            ->assertSee('Giao hàng')
            ->assertSee('href="https://5sao.ghn.dev" class="menu-link" target="_blank"', false);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function soNgayKhongHopLe(): array
    {
        return [
            'bằng 0' => [0],
            'quá 30' => [31],
            'không phải số' => ['bay'],
            'bỏ trống' => [''],
        ];
    }

    #[DataProvider('soNgayKhongHopLe')]
    public function test_so_ngay_khong_hop_le_bi_tu_choi(mixed $value): void
    {
        $this->actingAs($this->makeOrderAdmin())
            ->put(route('setting.update'), ['auto_complete_days' => $value, 'return_days' => 7])
            ->assertSessionHasErrors('auto_complete_days');

        $this->assertSame(7, app(ShopSettings::class)->autoCompleteDays());
    }

    public function test_khong_co_quyen_don_hang_thi_khong_vao_duoc_cau_hinh(): void
    {
        $staff = $this->makeUser(email: 'nhanvien@example.test', username: 'nhanvien', role: 1);

        $this->actingAs($staff)->get(route('setting.edit'))->assertForbidden();
    }

    // -------------------------------------------------------- chuyển hoàn

    public function test_hang_hoan_ve_thi_cong_lai_ton_kho_va_chuyen_hoan_hang(): void
    {
        $order = $this->makeShippedOrder(quantity: 2);
        $conLai = $this->stock();

        $this->ghn('delivery_fail', '2026-09-20T03:00:00.000Z');
        $this->ghn('returning', '2026-09-21T03:00:00.000Z');
        $this->assertSame($conLai, $this->stock(), 'Chưa về kho thì chưa cộng');

        $this->ghn('returned', '2026-09-22T03:00:00.000Z');

        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::Returned, $fresh->order_status);
        $this->assertSame(0, (int) $fresh->order_delivery_status);
        $this->assertSame($conLai + 2, $this->stock());
        $this->assertSame(0, DB::table('statistical')->count());
    }

    public function test_ghn_gui_lai_tin_hoan_ve_khong_cong_kho_hai_lan(): void
    {
        $this->makeShippedOrder(quantity: 2);
        $conLai = $this->stock();

        $this->ghn('returned', '2026-09-22T03:00:00.000Z');
        $this->ghn('returned', '2026-09-22T03:00:00.000Z');
        $this->ghn('returned', '2026-09-22T04:00:00.000Z');

        $this->assertSame($conLai + 2, $this->stock());
    }

    public function test_admin_thay_canh_bao_khi_giao_that_bai(): void
    {
        $order = $this->makeShippedOrder();
        $this->ghn('delivery_fail');

        $this->actingAs($this->makeOrderAdmin())
            ->get(route('orders.edit', encrypt($order->order_id)))
            ->assertOk()
            ->assertSee('Giao hàng không thành công')
            ->assertSee('Gọi cho khách');
    }

    public function test_khach_thay_don_dang_hoan_hang(): void
    {
        $order = $this->makeShippedOrder();
        $this->ghn('returning');

        $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->assertSee('Đang hoàn hàng');
    }

    public function test_don_hoan_ve_khong_hien_la_khach_yeu_cau_tra_hang(): void
    {
        $order = $this->makeShippedOrder();
        $this->ghn('returned');

        $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->assertSee('đơn đã được hoàn về cửa hàng')
            ->assertDontSee('Đã gửi yêu cầu trả hàng');
    }

    // ------------------------------------------------- huỷ vận đơn, hoàn tiền

    public function test_huy_van_don_tu_admin_tra_co_ban_giao_ve_0(): void
    {
        $order = $this->makeShippedOrder();
        $order->order_delivery_status = 1;
        $order->save();

        app(ShippingService::class)->cancelBooking($order->fresh());

        $this->assertSame(0, (int) $order->fresh()->order_delivery_status);
    }

    public function test_huy_van_don_tren_trang_ghn_tra_co_ban_giao_ve_0(): void
    {
        $order = $this->makeShippedOrder();
        $order->order_delivery_status = 1;
        $order->save();

        $this->ghn('cancel');

        $this->assertSame(0, (int) $order->fresh()->order_delivery_status);
    }

    public function test_xac_nhan_hoan_tien_tru_doanh_thu_da_ghi_mot_lan(): void
    {
        $order = $this->makeShippedOrder();
        $this->ghn('delivered');
        $this->confirmReceived($order);
        $this->assertGreaterThan(0, (int) DB::table('statistical')->value('sales'));

        // The customer asks for a refund; the shop confirms it, then again.
        $order->fresh()->forceFill(['order_status' => OrderStatus::Returned])->save();
        $admin = $this->makeOrderAdmin();
        foreach ([1, 2] as $lan) {
            $this->actingAs($admin)->put(route('order.update', $order->order_id), ['note' => '', 'action' => 'refund']);
        }

        $row = DB::table('statistical')->first();
        $this->assertSame(0, (int) $row->sales);
        $this->assertSame(0, (int) $row->profit);
        $this->assertSame(0, (int) $order->fresh()->order_revenue_counted);
    }
}
