<?php

namespace Tests\Feature;

use App\Actions\CancelOrderAction;
use App\Actions\PlaceOrderAction;
use App\Models\OrderModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use App\Models\WalletTopupModel;
use App\Models\WalletTransactionModel;
use App\Models\WalletWithdrawalModel;
use App\Services\Wallet\InsufficientBalance;
use App\Services\Wallet\WalletService;
use App\Services\Wallet\WalletTopups;
use App\Services\Wallet\WalletWithdrawals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The customer's wallet: money in from a gateway, out on an order, back on a
 * refund, and out again to a bank account.
 *
 * The balance is a cached total, so most of these ask the same question twice
 * over — what the wallet says, and what the ledger behind it says.
 */
class WalletTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const MOMO_SECRET = 'momo-test-secret';

    private const MOMO_ACCESS_KEY = 'momo-access-key';

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedLookupTables();
        $this->customer = $this->makeUser();

        config([
            'services.momo.secret_key' => self::MOMO_SECRET,
            'services.momo.access_key' => self::MOMO_ACCESS_KEY,
            'services.momo.endpoint' => 'https://momo.test/create',
        ]);
    }

    // ---------------------------------------------------------- fixtures

    private function wallet(?UserModel $user = null)
    {
        return app(WalletService::class)->for($user ?? $this->customer);
    }

    private function topUp(int $amount): WalletTopupModel
    {
        Http::fake(['*' => Http::response(['payUrl' => 'https://momo.test/pay/abc'])]);

        return app(WalletTopups::class)->start($this->customer, $amount, 'payUrl');
    }

    /**
     * Money in the wallet without walking the gateway every time.
     */
    private function fund(int $amount): void
    {
        app(WalletService::class)->credit(
            $this->wallet(),
            $amount,
            WalletTransactionModel::TYPE_ADJUSTMENT,
            'Nạp sẵn cho bài kiểm thử',
        );
    }

    private function placeOrder(string $payment = 'wallet', int $price = 500_000, int $quantity = 1): OrderModel
    {
        $product = $this->makeProduct(price: $price, stock: 10, slug: 'giay-vi');

        return app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($product, $quantity)],
            null,
            $this->makeAddress($this->customer),
            ['payment' => $payment, 'note_customer' => null],
        );
    }

    /**
     * A MoMo reply for a top-up code, signed the way MoMo signs one.
     *
     * @return array<string, string>
     */
    private function momoCallback(string $code, int $amount, int $resultCode = 0): array
    {
        $params = [
            'partnerCode' => 'MOMOTEST',
            'orderId' => $code,
            'requestId' => $code.'-1',
            'amount' => (string) $amount,
            'orderInfo' => 'Nap tien vao vi',
            'orderType' => 'momo_wallet',
            'transId' => '2547844248',
            'resultCode' => (string) $resultCode,
            'message' => 'Successful.',
            'payType' => 'qr',
            'responseTime' => '1712345678901',
            'extraData' => '',
        ];

        $raw = 'accessKey='.self::MOMO_ACCESS_KEY
            .'&amount='.$params['amount']
            .'&extraData='.$params['extraData']
            .'&message='.$params['message']
            .'&orderId='.$params['orderId']
            .'&orderInfo='.$params['orderInfo']
            .'&orderType='.$params['orderType']
            .'&partnerCode='.$params['partnerCode']
            .'&payType='.$params['payType']
            .'&requestId='.$params['requestId']
            .'&responseTime='.$params['responseTime']
            .'&resultCode='.$params['resultCode']
            .'&transId='.$params['transId'];

        $params['signature'] = hash_hmac('sha256', $raw, self::MOMO_SECRET);

        return $params;
    }

    // ------------------------------------------------------------ nạp tiền

    public function test_nap_tien_thanh_cong_thi_cong_vao_so_du(): void
    {
        $topup = $this->topUp(200_000);

        $this->postJson(route('payment.ipn'), $this->momoCallback($topup->topup_code, 200_000))
            ->assertOk();

        $this->assertSame(200_000, $this->wallet()->balance);
        $this->assertSame(WalletTopupModel::PAID, $topup->fresh()->status);
    }

    public function test_momo_bao_hai_lan_chi_cong_tien_mot_lan(): void
    {
        $topup = $this->topUp(200_000);
        $callback = $this->momoCallback($topup->topup_code, 200_000);

        $this->postJson(route('payment.ipn'), $callback)->assertOk();
        $this->postJson(route('payment.ipn'), $callback)->assertOk();

        $this->assertSame(200_000, $this->wallet()->balance);
        $this->assertSame(1, WalletTransactionModel::where('type', WalletTransactionModel::TYPE_TOPUP)->count());
    }

    public function test_so_tien_bao_ve_khac_so_tien_yeu_cau_thi_khong_cong(): void
    {
        $topup = $this->topUp(200_000);

        $this->postJson(route('payment.ipn'), $this->momoCallback($topup->topup_code, 5_000_000))
            ->assertStatus(400);

        $this->assertSame(0, $this->wallet()->balance);
        $this->assertSame(WalletTopupModel::PENDING, $topup->fresh()->status);
    }

    public function test_khach_huy_o_cong_thanh_toan_thi_yeu_cau_nap_that_bai(): void
    {
        $topup = $this->topUp(200_000);

        $this->postJson(route('payment.ipn'), $this->momoCallback($topup->topup_code, 200_000, 1006))
            ->assertOk();

        $this->assertSame(0, $this->wallet()->balance);
        $this->assertSame(WalletTopupModel::FAILED, $topup->fresh()->status);
    }

    public function test_ma_nap_tien_khong_lan_sang_don_hang(): void
    {
        $topup = $this->topUp(200_000);

        $this->assertStringStartsWith(WalletTopupModel::PREFIX, $topup->topup_code);
        $this->assertFalse(OrderModel::where('order_code', $topup->topup_code)->exists());
    }

    // ------------------------------------------------- thanh toán bằng ví

    public function test_dat_hang_bang_vi_tru_so_du_va_don_duoc_tinh_da_tra_tien(): void
    {
        $this->fund(1_000_000);

        $order = $this->placeOrder(price: 500_000);

        $this->assertSame(1, (int) $order->order_payment_status);
        $this->assertSame(1_000_000 - (int) $order->order_total, $this->wallet()->balance);
    }

    public function test_vi_khong_du_thi_khong_dat_duoc_don_va_khong_tru_kho(): void
    {
        $this->fund(10_000);

        $this->expectException(InsufficientBalance::class);

        try {
            $this->placeOrder(price: 500_000);
        } finally {
            $this->assertSame(0, OrderModel::count());
            $this->assertSame(10, (int) ProductQuantityModel::where('pro_id', 1)->value('quantity'));
            $this->assertSame(10_000, $this->wallet()->balance);
        }
    }

    public function test_don_tra_bang_vi_bi_huy_thi_tien_ve_lai_vi(): void
    {
        $this->fund(1_000_000);
        $order = $this->placeOrder(price: 500_000);

        app(CancelOrderAction::class)->execute($order, allowPaid: true);

        $this->assertSame(1_000_000, $this->wallet()->balance);
    }

    public function test_huy_hai_lan_khong_hoan_tien_hai_lan(): void
    {
        $this->fund(1_000_000);
        $order = $this->placeOrder(price: 500_000);

        app(CancelOrderAction::class)->execute($order, allowPaid: true);
        app(CancelOrderAction::class)->execute($order->fresh(), allowPaid: true);

        $this->assertSame(1_000_000, $this->wallet()->balance);
    }

    public function test_trang_thanh_toan_hien_so_du_vi(): void
    {
        $this->fund(1_234_000);
        $product = $this->makeProduct(slug: 'giay-checkout');
        $this->makeAddress($this->customer);

        $this->actingAs($this->customer)
            ->withSession(['cart' => [$this->cartLine($product)]])
            ->get(route('product.checkout'))
            ->assertOk()
            ->assertSee('SPay')
            ->assertSee('1.234.000');
    }

    // ------------------------------------------------------------ rút tiền

    public function test_gui_yeu_cau_rut_tien_thi_tru_so_du_ngay(): void
    {
        $this->fund(1_000_000);

        $this->actingAs($this->customer)
            ->post(route('wallet.withdraw'), [
                'amount' => 300_000,
                'bank_name' => 'Vietcombank',
                'bank_account' => '0123456789',
                'account_holder' => 'NGUYEN VAN A',
            ])->assertRedirect();

        $this->assertSame(700_000, $this->wallet()->balance);
        $this->assertSame(WalletWithdrawalModel::REQUESTED, WalletWithdrawalModel::first()->status);
    }

    public function test_khong_rut_duoc_qua_so_du(): void
    {
        $this->fund(100_000);

        $this->expectException(InsufficientBalance::class);

        try {
            app(WalletWithdrawals::class)->request($this->customer, [
                'amount' => 900_000,
                'bank_name' => 'Vietcombank',
                'bank_account' => '0123456789',
                'account_holder' => 'NGUYEN VAN A',
            ]);
        } finally {
            $this->assertSame(100_000, $this->wallet()->balance);
        }
    }

    public function test_shop_tu_choi_thi_tien_quay_lai_vi(): void
    {
        $this->fund(1_000_000);
        $yeuCau = app(WalletWithdrawals::class)->request($this->customer, [
            'amount' => 300_000,
            'bank_name' => 'Vietcombank',
            'bank_account' => '0123456789',
            'account_holder' => 'NGUYEN VAN A',
        ]);

        $this->actingAs($this->makeWalletAdmin())
            ->post(route('wallet_admin.withdrawal_reject', $yeuCau->withdrawal_id), ['reason' => 'Sai tên chủ tài khoản'])
            ->assertRedirect();

        $this->assertSame(1_000_000, $this->wallet()->balance);
        $this->assertSame(WalletWithdrawalModel::REJECTED, $yeuCau->fresh()->status);
    }

    public function test_shop_da_chuyen_thi_tien_khong_quay_lai_vi(): void
    {
        $this->fund(1_000_000);
        $yeuCau = app(WalletWithdrawals::class)->request($this->customer, [
            'amount' => 300_000,
            'bank_name' => 'Vietcombank',
            'bank_account' => '0123456789',
            'account_holder' => 'NGUYEN VAN A',
        ]);

        $admin = $this->makeWalletAdmin();
        $this->actingAs($admin)->post(route('wallet_admin.withdrawal_paid', $yeuCau->withdrawal_id), ['note' => 'FT2609'])->assertRedirect();
        $this->actingAs($admin)->post(route('wallet_admin.withdrawal_reject', $yeuCau->withdrawal_id), ['reason' => 'Bấm nhầm'])->assertRedirect();

        $this->assertSame(700_000, $this->wallet()->balance);
        $this->assertSame(WalletWithdrawalModel::PAID, $yeuCau->fresh()->status);
    }

    // ------------------------------------------------------------- sổ ví

    public function test_so_du_luon_khop_voi_so_giao_dich(): void
    {
        $this->fund(1_000_000);
        $order = $this->placeOrder(price: 300_000);
        app(CancelOrderAction::class)->execute($order, allowPaid: true);

        $wallet = $this->wallet();
        $tong = WalletTransactionModel::where('wallet_id', $wallet->wallet_id)
            ->get()
            ->sum(fn (WalletTransactionModel $entry) => $entry->isCredit() ? $entry->amount : -$entry->amount);

        $this->assertSame($wallet->balance, $tong);
        $this->assertSame(
            $wallet->balance,
            (int) WalletTransactionModel::where('wallet_id', $wallet->wallet_id)->orderByDesc('transaction_id')->value('balance_after'),
        );
    }

    public function test_nhap_sai_so_tien_thi_van_giu_cong_da_chon(): void
    {
        $this->actingAs($this->customer)
            ->from(route('user.wallet'))
            ->post(route('wallet.topup'), ['amount' => 1000, 'gateway' => 'redirect'])
            ->assertRedirect(route('user.wallet'));

        $html = $this->actingAs($this->customer)->get(route('user.wallet'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/id="topup-vnpay"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="topup-momo"[^>]*checked/', $html);
    }

    public function test_khach_xem_duoc_lich_su_vi_cua_minh(): void
    {
        $this->fund(250_000);

        $this->actingAs($this->customer)
            ->get(route('user.wallet'))
            ->assertOk()
            ->assertSee('250.000')
            ->assertSee('Điều chỉnh từ cửa hàng');
    }

    private function makeWalletAdmin(): UserModel
    {
        $admin = $this->makeUser(email: 'vi@example.test', username: 'quanlyvi', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web'));

        return $admin;
    }
}
