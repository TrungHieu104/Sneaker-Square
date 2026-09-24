<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\OrderStatusLogModel;
use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\ShippingCarrier;
use App\Services\ShippingService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The order's own lifecycle: who may move it where, and what each move leaves
 * behind in the history.
 */
class OrderStatusFlowTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const SECRET = 'bi-mat-webhook';

    private UserModel $customer;

    private ProductModel $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->app->instance(ShippingCarrier::class, new FakeCarrier);
        config(['services.ghn.webhook_token' => self::SECRET, 'services.ghn.create_orders' => true]);

        $this->customer = $this->makeUser();
    }

    private function makeOrder(): OrderModel
    {
        $this->product = $this->makeProduct(slug: 'giay-vong-doi', stock: 10);

        return app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($this->product)],
            null,
            $this->makeAddress($this->customer),
            ['payment' => 'COD', 'note_customer' => null],
        );
    }

    private function makeConfirmedOrder(): OrderModel
    {
        $order = $this->makeOrder();
        $order->moveTo(OrderStatus::Confirmed, OrderStatusLogModel::ACTOR_ADMIN);

        return $order->fresh();
    }

    private function makeOrderAdmin(): UserModel
    {
        $admin = $this->makeUser(email: 'donhang@example.test', username: 'quanlydon', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web'));

        return $admin;
    }

    private function ghn(string $code, string $status, string $time = '2026-09-24T03:00:00.000Z')
    {
        return $this->postJson(route('webhook.ghn', ['token' => self::SECRET]), [
            'OrderCode' => $code,
            'ClientOrderCode' => '',
            'Status' => $status,
            'Time' => $time,
            'Warehouse' => 'Bưu cục test',
            'Description' => 'Cập nhật trạng thái',
        ])->assertOk();
    }

    private function askToCancel(OrderModel $order, string $reason = 'Đặt nhầm size')
    {
        return $this->actingAs($this->customer)
            ->from(route('orderBill.checkout', $order->order_code))
            ->patch(route('cancelOrder', $order->order_code), ['inputCancelOrder' => $reason]);
    }

    private function stock(): int
    {
        return (int) ProductQuantityModel::where('pro_id', $this->product->pro_id)->value('quantity');
    }

    // ------------------------------------------------------- đường đi vận đơn

    public function test_tao_van_don_dua_don_sang_cho_lay_hang(): void
    {
        $order = $this->makeConfirmedOrder();

        app(ShippingService::class)->book($order);

        $this->assertSame(OrderStatus::ReadyToShip, $order->fresh()->order_status);
    }

    public function test_huy_van_don_dua_don_ve_da_xac_nhan(): void
    {
        $order = $this->makeConfirmedOrder();
        app(ShippingService::class)->book($order);

        app(ShippingService::class)->cancelBooking($order->fresh());

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->order_status);
    }

    public function test_ghn_lay_hang_thi_don_sang_dang_giao(): void
    {
        $order = $this->makeConfirmedOrder();
        $booking = app(ShippingService::class)->book($order);

        $this->ghn($booking->code, 'picked');

        $this->assertSame(OrderStatus::Delivering, $order->fresh()->order_status);
    }

    /**
     * A failed attempt is not a status of its own: GHN tries again, and only
     * turning the parcel round makes it the shop's problem.
     */
    public function test_giao_that_bai_van_la_dang_giao(): void
    {
        $order = $this->makeConfirmedOrder();
        $booking = app(ShippingService::class)->book($order);
        $this->ghn($booking->code, 'picked');

        $this->ghn($booking->code, 'delivery_fail', '2026-09-24T04:00:00.000Z');

        $this->assertSame(OrderStatus::Delivering, $order->fresh()->order_status);
        $this->assertSame('delivery_fail', $order->fresh()->order_shipping_status);
    }

    public function test_hang_quay_dau_thi_don_sang_dang_hoan_ve(): void
    {
        $order = $this->makeConfirmedOrder();
        $booking = app(ShippingService::class)->book($order);
        $this->ghn($booking->code, 'picked');

        $this->ghn($booking->code, 'return', '2026-09-24T05:00:00.000Z');

        $this->assertSame(OrderStatus::Returning, $order->fresh()->order_status);
    }

    /**
     * An order the customer already has must not be dragged back onto the road
     * by a callback that arrives late.
     */
    public function test_callback_muon_khong_keo_don_da_xong_tro_lai(): void
    {
        $order = $this->makeConfirmedOrder();
        $booking = app(ShippingService::class)->book($order);
        $this->ghn($booking->code, 'delivered');
        $this->actingAs($this->customer)->post(route('success.order'), ['order_code' => $order->order_code]);

        $this->ghn($booking->code, 'delivering', '2026-09-24T06:00:00.000Z');

        $this->assertSame(OrderStatus::Completed, $order->fresh()->order_status);
    }

    // ------------------------------------------------------- khách xin huỷ

    public function test_don_moi_thi_khach_huy_thang(): void
    {
        $order = $this->makeOrder();

        $this->askToCancel($order);

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->order_status);
        $this->assertSame(10, $this->stock(), 'Huỷ thì tồn kho phải về chỗ cũ');
    }

    public function test_don_da_xac_nhan_thi_khach_chi_xin_huy(): void
    {
        $order = $this->makeConfirmedOrder();

        $this->askToCancel($order);

        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::CancelRequested, $fresh->order_status);
        $this->assertSame('Đặt nhầm size', $fresh->order_cancel_reason);
        $this->assertSame(9, $this->stock(), 'Chưa duyệt thì hàng vẫn giữ chỗ');
    }

    public function test_don_da_roi_kho_thi_khong_xin_huy_duoc(): void
    {
        $order = $this->makeConfirmedOrder();
        $booking = app(ShippingService::class)->book($order);
        $this->ghn($booking->code, 'picked');

        $this->askToCancel($order);

        $this->assertSame(OrderStatus::Delivering, $order->fresh()->order_status);
    }

    public function test_shop_duyet_huy_thi_don_huy_va_hoan_kho(): void
    {
        $order = $this->makeConfirmedOrder();
        $this->askToCancel($order);

        $this->actingAs($this->makeOrderAdmin())->post(route('order.approve_cancel', $order->order_id));

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->order_status);
        $this->assertSame(10, $this->stock());
    }

    public function test_duyet_huy_don_da_dat_van_don_thi_huy_luon_van_don(): void
    {
        $order = $this->makeConfirmedOrder();
        $booking = app(ShippingService::class)->book($order);
        $this->askToCancel($order->fresh());

        $this->actingAs($this->makeOrderAdmin())->post(route('order.approve_cancel', $order->order_id));

        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::Cancelled, $fresh->order_status);
        $this->assertNull($fresh->order_shipping_code);
        $this->assertSame([$booking->code], app(ShippingCarrier::class)->cancelled);
    }

    public function test_tu_choi_huy_thi_don_ve_dung_cho_cu(): void
    {
        $order = $this->makeConfirmedOrder();
        app(ShippingService::class)->book($order);
        $this->askToCancel($order->fresh());

        $this->actingAs($this->makeOrderAdmin())->post(route('order.reject_cancel', $order->order_id), [
            'cancel_reject_reason' => 'Đơn đã đóng gói, shipper đang tới lấy',
        ]);

        $this->assertSame(OrderStatus::ReadyToShip, $order->fresh()->order_status, 'Vận đơn còn thì về chờ lấy hàng');
    }

    public function test_tu_choi_huy_don_chua_dat_van_don_thi_ve_da_xac_nhan(): void
    {
        $order = $this->makeConfirmedOrder();
        $this->askToCancel($order);

        $this->actingAs($this->makeOrderAdmin())->post(route('order.reject_cancel', $order->order_id), [
            'cancel_reject_reason' => 'Hàng đã lấy khỏi kho',
        ]);

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->order_status);
    }

    public function test_tu_choi_huy_phai_co_ly_do(): void
    {
        $order = $this->makeConfirmedOrder();
        $this->askToCancel($order);

        $this->actingAs($this->makeOrderAdmin())
            ->post(route('order.reject_cancel', $order->order_id), ['cancel_reject_reason' => ''])
            ->assertSessionHasErrors('cancel_reject_reason');

        $this->assertSame(OrderStatus::CancelRequested, $order->fresh()->order_status);
    }

    public function test_admin_thay_panel_xin_huy(): void
    {
        $order = $this->makeConfirmedOrder();
        $this->askToCancel($order);

        $this->actingAs($this->makeOrderAdmin())
            ->get(route('orders.edit', encrypt($order->order_id)))
            ->assertOk()
            ->assertSee('Khách xin huỷ đơn')
            ->assertSee('Đặt nhầm size')
            ->assertSee('Duyệt huỷ đơn');
    }

    // ------------------------------------------------------- lịch sử trạng thái

    public function test_moi_lan_doi_trang_thai_deu_ghi_lai_lich_su(): void
    {
        $order = $this->makeConfirmedOrder();
        $booking = app(ShippingService::class)->book($order);
        $this->ghn($booking->code, 'picked');

        $logs = OrderStatusLogModel::where('order_id', $order->order_id)->orderBy('log_id')->get();

        $this->assertSame(
            [OrderStatus::Confirmed, OrderStatus::ReadyToShip, OrderStatus::Delivering],
            $logs->pluck('to_status')->all(),
        );
        $this->assertSame(OrderStatus::ReadyToShip, $logs->last()->from_status);
        $this->assertSame(OrderStatusLogModel::ACTOR_CARRIER, $logs->last()->actor);
    }

    public function test_lich_su_ghi_ai_bam_nut(): void
    {
        $order = $this->makeConfirmedOrder();
        $this->askToCancel($order);

        $log = OrderStatusLogModel::where('to_status', OrderStatus::CancelRequested)->firstOrFail();

        $this->assertSame(OrderStatusLogModel::ACTOR_CUSTOMER, $log->actor);
        $this->assertSame($this->customer->user_id, (int) $log->user_id);
        $this->assertSame('Đặt nhầm size', $log->note);
    }

    public function test_gui_lai_cung_mot_trang_thai_khong_ghi_them_dong_nao(): void
    {
        $order = $this->makeConfirmedOrder();
        $booking = app(ShippingService::class)->book($order);

        $this->ghn($booking->code, 'ready_to_pick');
        $this->ghn($booking->code, 'picking', '2026-09-24T03:30:00.000Z');

        $this->assertSame(
            1,
            OrderStatusLogModel::where('order_id', $order->order_id)->where('to_status', OrderStatus::ReadyToShip)->count(),
            'Hai chặng GHN cùng nghĩa với shop chỉ là một dòng lịch sử',
        );
    }

    // ------------------------------------------------------- tiền hoàn về ví

    #[DataProvider('donDaTraTien')]
    public function test_don_da_thanh_toan_bi_huy_thi_hoan_ngay_vao_vi(string $payment): void
    {
        $order = $this->makeOrder();
        $order->forceFill(['order_payment' => $payment, 'order_payment_status' => 1])->save();

        $this->askToCancel($order->fresh());

        $vi = app(WalletService::class)->for((int) $order->user_id);

        $this->assertSame((int) $order->order_total, $vi->balance);
        $this->assertFalse($order->fresh()->order_refund_required);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function donDaTraTien(): array
    {
        return ['VNPay' => ['redirect'], 'MoMo' => ['payUrl']];
    }

    public function test_don_chua_tra_tien_thi_khong_hoan_gi(): void
    {
        $order = $this->makeOrder();

        $this->askToCancel($order);

        $this->assertFalse($order->fresh()->order_refund_required);
        $this->assertSame(0, app(WalletService::class)->for((int) $order->user_id)->balance);
    }
}
