<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\OrderStatusLogModel;
use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use App\Models\WalletTransactionModel;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\ShippingCarrier;
use App\Services\ShopSettings;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The ways an order ends that are not the happy path: the shop calling it
 * off, a cancel request overtaken by the carrier, a parcel GHN lost, a
 * parcel that came back undelivered, and a parcel the shop carried itself.
 */
class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const SECRET = 'bi-mat-webhook';

    private const MA_VAN_DON = 'LVONGDOI01';

    private UserModel $customer;

    private ProductModel $product;

    private FakeCarrier $carrier;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedLookupTables();
        $this->carrier = new FakeCarrier;
        $this->app->instance(ShippingCarrier::class, $this->carrier);
        config(['services.ghn.webhook_token' => self::SECRET]);

        $this->customer = $this->makeUser();
        $this->product = $this->makeProduct(slug: 'giay-vong-doi', stock: 10);
    }

    // ---------------------------------------------------------- fixtures

    private function placeOrder(string $payment = 'cod'): OrderModel
    {
        if ($payment === 'wallet') {
            $wallets = app(WalletService::class);
            $wallets->credit($wallets->for($this->customer), 10_000_000, WalletTransactionModel::TYPE_ADJUSTMENT, 'Nạp sẵn cho bài kiểm thử');
        }

        return app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($this->product, 1)],
            null,
            $this->makeAddress($this->customer),
            ['payment' => $payment, 'note_customer' => null],
        )->fresh();
    }

    private function booked(OrderModel $order): OrderModel
    {
        $order->forceFill([
            'order_status' => OrderStatus::ReadyToShip,
            'order_shipping_code' => self::MA_VAN_DON,
        ])->save();

        return $order->fresh();
    }

    private function ghn(string $status, string $time = '2026-09-20T03:00:00.000Z'): void
    {
        $this->postJson(route('webhook.ghn', ['token' => self::SECRET]), [
            'OrderCode' => self::MA_VAN_DON,
            'ClientOrderCode' => '',
            'Status' => $status,
            'Time' => $time,
            'Warehouse' => 'Bưu Cục 38E Cây Keo-Q.Thủ Đức-HCM',
            'Description' => 'Cập nhật trạng thái',
        ])->assertOk();
    }

    private function admin(): UserModel
    {
        $admin = $this->makeUser(email: 'quanly@example.test', username: 'quanlydon', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web'));

        return $admin;
    }

    private function stock(): int
    {
        return (int) ProductQuantityModel::where('pro_id', $this->product->pro_id)->value('quantity');
    }

    private function walletBalance(): int
    {
        return app(WalletService::class)->for($this->customer)->balance;
    }

    private function shopCancels(OrderModel $order, ?string $reason = 'Hết size 42 trong kho')
    {
        return $this->actingAs($this->admin())
            ->post(route('order.cancel_by_shop', $order->order_id), ['shop_cancel_reason' => $reason]);
    }

    // -------------------------------------------------- the shop cancels

    public function test_cua_hang_huy_don_moi_thi_tra_kho_va_luu_ly_do(): void
    {
        $order = $this->placeOrder();
        $this->assertSame(9, $this->stock());

        $this->shopCancels($order)->assertSessionHasNoErrors();

        $fresh = $order->fresh();
        $this->assertTrue($fresh->hasStatus(OrderStatus::Cancelled));
        $this->assertSame('Hết size 42 trong kho', $fresh->order_cancel_reason);
        $this->assertSame(10, $this->stock());
        $this->assertSame(
            OrderStatusLogModel::ACTOR_ADMIN,
            OrderStatusLogModel::where('order_id', $order->order_id)->latest('log_id')->value('actor'),
        );
    }

    public function test_cua_hang_huy_don_da_tra_bang_vi_thi_hoan_tien_vao_vi(): void
    {
        $order = $this->placeOrder('wallet');
        $sauKhiTra = $this->walletBalance();

        $this->shopCancels($order);

        $this->assertTrue($order->fresh()->hasStatus(OrderStatus::Cancelled));
        $this->assertSame($sauKhiTra + (int) $order->order_total, $this->walletBalance());
    }

    public function test_cua_hang_huy_don_da_co_van_don_thi_huy_luon_van_don_ghn(): void
    {
        $order = $this->booked($this->placeOrder());

        $this->shopCancels($order);

        $this->assertTrue($order->fresh()->hasStatus(OrderStatus::Cancelled));
        $this->assertContains(self::MA_VAN_DON, $this->carrier->cancelled);
    }

    public function test_cua_hang_khong_huy_duoc_don_dang_giao(): void
    {
        $order = $this->booked($this->placeOrder());
        $this->ghn('picked');

        $this->shopCancels($order);

        $this->assertTrue($order->fresh()->hasStatus(OrderStatus::Delivering));
        $this->assertSame(9, $this->stock());
    }

    public function test_cua_hang_huy_don_phai_ghi_ly_do(): void
    {
        $order = $this->placeOrder();

        $this->shopCancels($order, '')->assertSessionHasErrors('shop_cancel_reason');

        $this->assertTrue($order->fresh()->hasStatus(OrderStatus::New));
    }

    public function test_khach_thay_ly_do_cua_hang_huy_don(): void
    {
        $order = $this->placeOrder();
        $this->shopCancels($order);

        $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->assertSee('Hết size 42 trong kho');
    }

    // ------------------------------- a cancel request the carrier overtook

    public function test_ghn_lay_hang_khi_yeu_cau_huy_chua_duyet_thi_yeu_cau_tu_bi_tu_choi(): void
    {
        $order = $this->booked($this->placeOrder());
        $order->forceFill(['order_status' => OrderStatus::CancelRequested, 'order_cancel_reason' => 'Đặt nhầm size'])->save();

        $this->ghn('picked');

        $fresh = $order->fresh();
        $this->assertTrue($fresh->hasStatus(OrderStatus::Delivering));
        $this->assertStringContainsString('đã lấy hàng', (string) $fresh->order_cancel_reason);
    }

    public function test_ghn_giao_xong_khi_yeu_cau_huy_chua_duyet_thi_don_van_toi_da_giao(): void
    {
        $order = $this->booked($this->placeOrder());
        $order->forceFill(['order_status' => OrderStatus::CancelRequested])->save();

        $this->ghn('delivered');

        $this->assertTrue($order->fresh()->hasStatus(OrderStatus::Delivered));
    }

    // ------------------------------------------------- a parcel GHN lost

    public function test_ghn_lam_that_lac_don_da_tra_tien_thi_huy_va_hoan_tien(): void
    {
        $order = $this->booked($this->placeOrder('wallet'));
        $sauKhiTra = $this->walletBalance();
        $this->ghn('picked', '2026-09-20T03:00:00.000Z');

        $this->ghn('lost', '2026-09-21T03:00:00.000Z');

        $fresh = $order->fresh();
        $this->assertTrue($fresh->hasStatus(OrderStatus::Cancelled));
        $this->assertSame($sauKhiTra + (int) $order->order_total, $this->walletBalance());
        // The pair is gone with the parcel; it is not back on the shelf.
        $this->assertSame(9, $this->stock());
    }

    public function test_ghn_lam_that_lac_don_cod_thi_huy_khong_hoan_gi(): void
    {
        $order = $this->booked($this->placeOrder());
        $this->ghn('picked', '2026-09-20T03:00:00.000Z');

        $this->ghn('lost', '2026-09-21T03:00:00.000Z');
        $this->ghn('lost', '2026-09-21T04:00:00.000Z');

        $this->assertTrue($order->fresh()->hasStatus(OrderStatus::Cancelled));
        $this->assertSame(0, $this->walletBalance());
        $this->assertSame(9, $this->stock());
    }

    // ------------------------------------ a parcel that came back undelivered

    public function test_don_giao_that_bai_ve_kho_khong_con_buoc_xac_nhan_hoan_tien(): void
    {
        $order = $this->booked($this->placeOrder('wallet'));
        $sauKhiTra = $this->walletBalance();
        $this->ghn('return', '2026-09-20T03:00:00.000Z');
        $this->ghn('returned', '2026-09-21T03:00:00.000Z');

        // Already refunded when GHN brought it back.
        $this->assertSame($sauKhiTra + (int) $order->order_total, $this->walletBalance());

        $admin = $this->admin();
        $this->actingAs($admin)
            ->get(route('orders.edit', encrypt($order->order_id)))
            ->assertOk()
            ->assertDontSee('Xác nhận hoàn tiền');

        $this->actingAs($admin)->put(route('order.update', $order->order_id), ['note' => '', 'action' => 'refund']);

        $this->assertTrue($order->fresh()->hasStatus(OrderStatus::Returned));
    }

    // --------------------------------------- the customer cancels, message

    public function test_khach_huy_don_chua_tra_tien_khong_duoc_hua_hoan_tien(): void
    {
        $order = $this->placeOrder();

        $this->actingAs($this->customer)
            ->from(route('orderBill.checkout', $order->order_code))
            ->patch(route('cancelOrder', $order->order_code), ['inputCancelOrder' => 'Đổi ý'])
            ->assertSessionMissing('text');
    }

    public function test_khach_huy_don_da_tra_thi_bao_tien_da_ve_spay(): void
    {
        $order = $this->placeOrder('wallet');

        $this->actingAs($this->customer)
            ->from(route('orderBill.checkout', $order->order_code))
            ->patch(route('cancelOrder', $order->order_code), ['inputCancelOrder' => 'Đổi ý'])
            ->assertSessionHas('text', fn ($text) => str_contains($text, 'SPay') && ! str_contains($text, '24h'));
    }

    // ------------------------------------ a parcel the shop carried itself

    public function test_cua_hang_bao_da_giao_don_tu_giao_thi_don_tu_hoan_thanh_sau_han(): void
    {
        $order = $this->placeOrder();
        $order->forceFill(['order_status' => OrderStatus::Delivering, 'order_delivery_status' => 1])->save();

        $this->actingAs($this->admin())
            ->put(route('order.update', $order->order_id), ['note' => '', 'action' => 'delivered']);

        $fresh = $order->fresh();
        $this->assertTrue($fresh->hasStatus(OrderStatus::Delivered));
        $this->assertNotNull($fresh->order_delivered_at);

        Carbon::setTestNow(now()->addDays(app(ShopSettings::class)->autoCompleteDays() + 1));
        $this->artisan('orders:auto-complete')->assertSuccessful();

        $this->assertTrue($order->fresh()->hasStatus(OrderStatus::Completed));
    }

    public function test_don_ghn_khong_bao_da_giao_thu_cong_duoc(): void
    {
        $order = $this->booked($this->placeOrder());
        $this->ghn('picked');

        $this->actingAs($this->admin())
            ->put(route('order.update', $order->order_id), ['note' => '', 'action' => 'delivered']);

        $this->assertTrue($order->fresh()->hasStatus(OrderStatus::Delivering));
    }
}
