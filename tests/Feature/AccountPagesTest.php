<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Models\WalletTransactionModel;
use App\Services\Shipping\GhnStatus;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Every page behind "Tài khoản của tôi" has to render.
 *
 * These five share one layout and one sidebar, and nothing else in the suite
 * opened them — a Blade left unparseable by a bulk edit shipped green. The
 * assertions are deliberately thin: the point is that the view compiles and
 * the sidebar comes with it.
 */
class AccountPagesTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedLookupTables();
        $this->customer = $this->makeUser();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function trangTaiKhoan(): array
    {
        return [
            'Thông tin' => ['thong-tin-tai-khoan.index'],
            'Mật khẩu' => ['user.update_pass'],
            'Địa chỉ giao hàng' => ['user.delivery'],
            'Đơn hàng' => ['user.order'],
            'Ví của tôi' => ['user.wallet'],
        ];
    }

    #[DataProvider('trangTaiKhoan')]
    public function test_trang_tai_khoan_mo_duoc(string $route): void
    {
        $this->actingAs($this->customer)
            ->get(route($route))
            ->assertOk()
            ->assertSee('Ví của tôi')
            ->assertSee($this->customer->email);
    }

    public function test_trang_don_hang_mo_duoc_khi_co_don_da_hoan_thanh(): void
    {
        $product = $this->makeProduct(slug: 'giay-tai-khoan');

        $order = app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($product, 1)],
            null,
            $this->makeAddress($this->customer),
            ['payment' => 'cod', 'note_customer' => null],
        );

        // The pane that draws "Đánh giá sản phẩm" only runs for a completed
        // order, which is exactly why a broken branch there stayed hidden.
        $order->forceFill([
            'order_status' => OrderStatus::Completed,
            'order_completed_at' => now(),
            'order_delivery_status' => 1,
        ])->save();

        $this->actingAs($this->customer)
            ->get(route('user.order'))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertSee('Đánh giá sản phẩm');
    }

    public function test_don_moi_tra_bang_vi_van_co_nut_xem_chi_tiet(): void
    {
        $product = $this->makeProduct(slug: 'giay-tra-bang-vi');
        app(WalletService::class)->credit(
            app(WalletService::class)->for($this->customer),
            2_000_000,
            WalletTransactionModel::TYPE_ADJUSTMENT,
            'Nạp sẵn cho bài kiểm thử',
        );

        $order = app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($product, 1)],
            null,
            $this->makeAddress($this->customer),
            ['payment' => 'wallet', 'note_customer' => null],
        );

        $html = $this->actingAs($this->customer)->get(route('user.order'))->assertOk()->getContent();

        // Scoped to the "Tất cả" pane: the other tabs print these links for
        // every order they hold, and would hide the hole this covers. That
        // pane branched on the payment method, so a wallet order landed in a
        // card with no buttons under it at all.
        $tatCa = substr(
            $html,
            (int) strpos($html, 'id="v-pills-all"'),
            (int) strpos($html, 'id="v-pills-wait-payment"') - (int) strpos($html, 'id="v-pills-all"'),
        );

        $this->assertStringContainsString(route('orderBill.checkout', $order->order_code), $tatCa);
        $this->assertStringContainsString(url('/in-don-hang/'.$order->order_code), $tatCa);
    }

    public function test_the_don_hang_in_dung_trang_thai_that_cua_don(): void
    {
        $product = $this->makeProduct(slug: 'giay-trang-thai');

        $order = app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($product, 1)],
            null,
            $this->makeAddress($this->customer),
            ['payment' => 'cod', 'note_customer' => null],
        );

        // The "Đang giao" tab holds five statuses but printed that one word for
        // all of them, so a confirmed order told the customer it was on a van.
        $order->forceFill(['order_status' => OrderStatus::Confirmed])->save();

        $this->actingAs($this->customer)
            ->get(route('user.order'))
            ->assertOk()
            ->assertSee(OrderStatus::Confirmed->label())
            ->assertDontSee('*Trạng thái đơn hàng*');
    }

    public function test_don_dang_van_chuyen_thi_the_in_trang_thai_cua_hang_van_chuyen(): void
    {
        $order = $this->makeOrderForStatusLine();
        $order->forceFill([
            'order_status' => OrderStatus::Delivering,
            'order_shipping_code' => 'GHN123456',
            'order_shipping_status' => 'transporting',
        ])->save();

        $this->actingAs($this->customer)
            ->get(route('user.order'))
            ->assertOk()
            ->assertSee(GhnStatus::label('transporting'));
    }

    public function test_don_chua_giao_thi_khong_in_dong_van_chuyen(): void
    {
        $order = $this->makeOrderForStatusLine();
        $order->forceFill(['order_status' => OrderStatus::New])->save();

        $this->actingAs($this->customer)
            ->get(route('user.order'))
            ->assertOk()
            ->assertSee(OrderStatus::New->label())
            ->assertDontSee('nkmfr2');
    }

    public function test_don_da_giao_khong_giuc_khach_bam_xac_nhan(): void
    {
        $order = $this->makeOrderForStatusLine();
        $order->forceFill([
            'order_status' => OrderStatus::Delivered,
            'order_delivered_at' => now(),
        ])->save();

        $this->actingAs($this->customer)
            ->get(route('user.order'))
            ->assertOk()
            ->assertSee(OrderStatus::Delivered->label())
            // Confirming receipt only brings the auto-completion forward, so the
            // status must not read like the order is stuck waiting on it.
            ->assertDontSee('chờ khách xác nhận');
    }

    private function makeOrderForStatusLine(): OrderModel
    {
        $product = $this->makeProduct(slug: 'giay-van-chuyen');

        return app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($product, 1)],
            null,
            $this->makeAddress($this->customer),
            ['payment' => 'cod', 'note_customer' => null],
        );
    }

    public function test_khach_chua_dang_nhap_bi_day_ve_trang_dang_nhap(): void
    {
        $this->get(route('user.wallet'))->assertRedirect();
    }
}
