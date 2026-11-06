<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Enums\CustomerSegment;
use App\Enums\OrderStatus;
use App\Models\CouponModel;
use App\Models\OrderCashbackModel;
use App\Models\OrderModel;
use App\Models\OrderReturnModel;
use App\Models\UserModel;
use App\Models\WalletTransactionModel;
use App\Services\CustomerSegments\Cashback;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\ShippingCarrier;
use App\Services\ShopSettings;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SegmentFixtures;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Money paid back into the wallet for what a customer's segment earns, and
 * never before the order can no longer be returned.
 */
class CashbackTest extends TestCase
{
    use RefreshDatabase;
    use SegmentFixtures;
    use ShopFixtures;

    private const RETURN_DAYS = 7;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->app->instance(ShippingCarrier::class, new FakeCarrier);
        app(ShopSettings::class)->setReturnDays(self::RETURN_DAYS);
        app(ShopSettings::class)->setSegmentCashbackEnabled(true);
    }

    private function quote(UserModel $user, int $goods): ?array
    {
        return app(Cashback::class)->quote((int) $user->user_id, $goods);
    }

    private function balance(UserModel $user): int
    {
        return (int) app(WalletService::class)->for($user)->balance;
    }

    public function test_hoan_theo_phan_tram_cua_nhom_va_cham_tran(): void
    {
        $vip = $this->customer();
        $this->inSegment($vip, CustomerSegment::Loyal);

        $this->assertSame(50_000, $this->quote($vip, 1_000_000)['amount'], '5% của 1 triệu chưa tới trần');
        $this->assertSame(100_000, $this->quote($vip, 3_818_000)['amount'], '5% là 190.900đ, trần 100.000đ');
    }

    public function test_tat_gioi_han_thi_hoan_du_phan_tram(): void
    {
        $vip = $this->customer();
        $this->inSegment($vip, CustomerSegment::Loyal);
        app(ShopSettings::class)->setSegmentOffers(['loyal' => ['discount' => 5, 'cashback' => 5, 'cap' => null]]);

        $quote = $this->quote($vip, 3_818_000);

        $this->assertSame(190_900, $quote['amount']);
        $this->assertNull($quote['cap']);
    }

    public function test_nhom_khong_hoan_va_khach_chua_xep_nhom_thi_khong_co_gi(): void
    {
        $regular = $this->customer();
        $this->inSegment($regular, CustomerSegment::Regular);

        $this->assertNull($this->quote($regular, 1_000_000));
        $this->assertNull($this->quote($this->customer(), 1_000_000));
    }

    private function placeOrder(UserModel $user, ?CouponModel $coupon = null): OrderModel
    {
        return app(PlaceOrderAction::class)->execute(
            $user,
            [$this->cartLine($this->makeProduct(price: 1_000_000, slug: 'giay-'.$user->user_id), 3)],
            $coupon,
            $this->makeAddress($user),
            ['payment' => 'COD', 'note_customer' => null],
        );
    }

    public function test_dat_don_chot_muc_hoan_tren_tien_hang_sau_ma_giam(): void
    {
        $promising = $this->customer();
        $this->inSegment($promising, CustomerSegment::Promising);
        $coupon = CouponModel::create([
            'coupon_name' => 'Giảm', 'coupon_code' => 'GIAM10', 'coupon_value' => 10, 'coupon_quantity' => 1, 'coupon_used' => 0,
            'coupon_condition' => 0, 'coupon_start' => now()->toDateString(), 'coupon_end' => now()->addDay()->toDateString(),
        ]);

        $order = $this->placeOrder($promising, $coupon);

        $cashback = OrderCashbackModel::findOrFail($order->order_id);
        $this->assertSame(50_000, (int) $cashback->amount, '2% của 2,7 triệu là 54.000đ, trần 50.000đ');
        $this->assertSame(CustomerSegment::Promising, $cashback->segment);
        $this->assertNull($cashback->paid_at);
        $this->assertSame(0, $this->balance($promising), 'Đặt đơn chưa cộng tiền');
    }

    public function test_doi_nhom_hay_doi_cau_hinh_sau_khi_dat_khong_doi_muc_da_chot(): void
    {
        $vip = $this->customer();
        $this->inSegment($vip, CustomerSegment::Loyal);
        $order = $this->placeOrder($vip);

        app(ShopSettings::class)->setSegmentOffers(['loyal' => ['discount' => 5, 'cashback' => 1, 'cap' => null]]);
        $vip->segment()->delete();

        $this->assertSame(100_000, (int) OrderCashbackModel::findOrFail($order->order_id)->amount);
    }

    public function test_khach_khong_co_uu_dai_thi_don_khong_co_dong_hoan(): void
    {
        $this->assertNull(OrderCashbackModel::find($this->placeOrder($this->customer())->order_id));
    }

    private function promisedOrder(UserModel $user, int $completedDaysAgo, OrderStatus $status = OrderStatus::Completed, int $amount = 100_000): OrderModel
    {
        $order = $this->completedOrder($user, daysAgo: $completedDaysAgo, status: $status);
        OrderCashbackModel::create([
            'order_id' => $order->order_id, 'segment' => CustomerSegment::Loyal->value, 'percent' => 5, 'cap' => 100_000, 'amount' => $amount,
        ]);

        return $order;
    }

    public function test_het_han_tra_hang_thi_cong_vao_vi(): void
    {
        $customer = $this->customer();
        $order = $this->promisedOrder($customer, self::RETURN_DAYS + 1);

        $this->artisan('orders:pay-cashback')->assertSuccessful();

        $this->assertSame(100_000, $this->balance($customer));
        $this->assertNotNull(OrderCashbackModel::findOrFail($order->order_id)->paid_at);
        $entry = WalletTransactionModel::where('type', WalletTransactionModel::TYPE_CASHBACK)->firstOrFail();
        $this->assertSame('Hoàn tiền ưu đãi', $entry->typeLabel());
        $this->assertSame('Đơn '.$order->order_code, $entry->description);
    }

    public function test_con_han_tra_hang_thi_chua_cong(): void
    {
        $customer = $this->customer();
        $this->promisedOrder($customer, self::RETURN_DAYS - 1);

        $this->artisan('orders:pay-cashback')->assertSuccessful();

        $this->assertSame(0, $this->balance($customer));
    }

    public function test_chay_lai_khong_cong_hai_lan(): void
    {
        $customer = $this->customer();
        $order = $this->promisedOrder($customer, self::RETURN_DAYS + 1);

        $this->artisan('orders:pay-cashback');
        OrderCashbackModel::whereKey($order->order_id)->update(['paid_at' => null]);
        $this->artisan('orders:pay-cashback');

        $this->assertSame(100_000, $this->balance($customer));
    }

    public function test_don_tra_hang_toan_bo_hoac_mot_phan_thi_khong_hoan(): void
    {
        $customer = $this->customer();
        $this->promisedOrder($customer, self::RETURN_DAYS + 1, OrderStatus::Returned);
        $this->promisedOrder($customer, self::RETURN_DAYS + 1, OrderStatus::PartiallyReturned);
        $this->promisedOrder($customer, self::RETURN_DAYS + 1, OrderStatus::Cancelled);

        $this->artisan('orders:pay-cashback')->assertSuccessful();

        $this->assertSame(0, $this->balance($customer));
    }

    private function returnRequest(OrderModel $order, string $status): OrderReturnModel
    {
        return OrderReturnModel::create(['order_id' => $order->order_id, 'status' => $status, 'reason' => 'loi_san_pham']);
    }

    public function test_yeu_cau_tra_dang_xu_ly_thi_giu_lai_den_khi_co_ket_qua(): void
    {
        $customer = $this->customer();
        $order = $this->promisedOrder($customer, self::RETURN_DAYS + 1);
        $request = $this->returnRequest($order, OrderReturnModel::REQUESTED);

        $this->artisan('orders:pay-cashback');
        $this->assertSame(0, $this->balance($customer));

        $request->update(['status' => OrderReturnModel::REJECTED]);
        $this->artisan('orders:pay-cashback');

        $this->assertSame(100_000, $this->balance($customer), 'Shop từ chối trả hàng thì đơn vẫn được hoàn');
    }

    public function test_trang_thanh_toan_bao_muc_hoan_ma_khong_lo_quy_tac_noi_bo(): void
    {
        $vip = $this->customer();
        $this->inSegment($vip, CustomerSegment::Loyal);
        $product = $this->makeProduct(price: 3_000_000, slug: 'giay-dat');

        $this->actingAs($vip)->withSession(['cart' => [$this->cartLine($product, 1)]])->get(route('product.checkout'))
            ->assertOk()
            ->assertSee('Hoàn vào ví SPay (5%, tối đa 100.000đ)', false)
            ->assertSee('+ 100.000 đ', false)
            ->assertSee('trong vòng 8 ngày sau khi đơn hoàn tất')
            ->assertDontSee('hết hạn trả hàng')
            ->assertDontSee('Thân thiết');
    }

    public function test_trang_thanh_toan_khong_hien_dong_hoan_khi_khong_duoc_hoan(): void
    {
        $product = $this->makeProduct(slug: 'giay-thuong');

        $this->actingAs($this->customer())->withSession(['cart' => [$this->cartLine($product, 1)]])->get(route('product.checkout'))
            ->assertOk()
            ->assertDontSee('Hoàn vào ví SPay');
    }

    private function settingsAdmin(): UserModel
    {
        $admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web'));

        return $admin;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function offerForm(): array
    {
        $offers = [];

        foreach (ShopSettings::SEGMENT_OFFER_DEFAULTS as $segment => $offer) {
            $offers[$segment] = ['discount' => $offer['discount'], 'cashback' => $offer['cashback'], 'cap' => $offer['cap']];
        }

        return $offers;
    }

    public function test_admin_doi_muc_hoan_va_tat_bat_tran_trong_cau_hinh(): void
    {
        $form = $this->offerForm();
        $form['loyal'] = ['discount' => 8, 'cashback' => 6, 'cap_enabled' => '1', 'cap' => 150_000];
        $form['promising'] = ['discount' => 12, 'cashback' => 3];

        $this->actingAs($this->settingsAdmin())->put(route('setting.update_segments'), ['offers' => $form])
            ->assertRedirect(route('setting.edit').'#tab-phan-khuc');

        $settings = app(ShopSettings::class);
        $this->assertSame(['discount' => 8, 'cashback' => 6, 'cap' => 150_000], $settings->segmentOffer(CustomerSegment::Loyal));
        $this->assertSame(['discount' => 12, 'cashback' => 3, 'cap' => null], $settings->segmentOffer(CustomerSegment::Promising));
    }

    public function test_bat_tran_ma_khong_nhap_so_tien_thi_bi_tu_choi(): void
    {
        $form = $this->offerForm();
        $form['loyal'] = ['discount' => 5, 'cashback' => 5, 'cap_enabled' => '1'];

        $this->actingAs($this->settingsAdmin())->from(route('setting.edit'))->put(route('setting.update_segments'), ['offers' => $form])
            ->assertSessionHasErrors('offers.loyal.cap');
    }

    public function test_muc_hoan_vuot_gioi_han_thi_bi_tu_choi(): void
    {
        $form = $this->offerForm();
        $form['loyal']['cashback'] = 50;

        $this->actingAs($this->settingsAdmin())->from(route('setting.edit'))->put(route('setting.update_segments'), ['offers' => $form])
            ->assertSessionHasErrors('offers.loyal.cashback');
    }

    public function test_trang_cau_hinh_hien_tab_phan_khuc_voi_tran_mac_dinh(): void
    {
        $this->actingAs($this->settingsAdmin())->get(route('setting.edit'))
            ->assertOk()
            ->assertSee('Ưu đãi theo phân khúc')
            ->assertSee('value="100000"', false)
            ->assertSee('value="50000"', false);
    }
}
