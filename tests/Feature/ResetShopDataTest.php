<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Models\WalletTransactionModel;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The reset command a developer runs to clear their own testing.
 *
 * What matters here is not that rows disappear but that the wallet survives
 * losing some of its ledger: the balance is signed against the newest entry
 * and each entry against the one before it, so a careless delete would leave
 * every customer locked out of a wallet nobody can verify.
 */
class ResetShopDataTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->customer = $this->makeUser();
    }

    private function wallets(): WalletService
    {
        return app(WalletService::class);
    }

    private function makeOrder(): OrderModel
    {
        $product = $this->makeProduct(slug: 'giay-reset', stock: 10);

        return app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($product)],
            null,
            $this->makeAddress($this->customer),
            ['payment' => 'COD', 'note_customer' => null],
        );
    }

    /**
     * A wallet topped up by the customer, then spent on an order and partly
     * refunded — the shape the command has to unpick.
     */
    private function makeHistory(): void
    {
        $wallets = $this->wallets();
        $wallet = $wallets->for($this->customer);

        $wallets->credit($wallet, 5_000_000, WalletTransactionModel::TYPE_TOPUP, 'Nạp tiền', WalletService::REF_TOPUP, 1);
        $wallets->debit($wallet->fresh(), 2_000_000, WalletTransactionModel::TYPE_PAYMENT, 'Mua hàng', WalletService::REF_ORDER, 1);
        $wallets->credit($wallet->fresh(), 500_000, WalletTransactionModel::TYPE_REFUND, 'Hoàn tiền', WalletService::REF_RETURN_REFUND, 1);
    }

    public function test_chi_chay_duoc_o_moi_truong_local(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->artisan('shop:reset --force')
            ->expectsOutputToContain('chỉ chạy được ở môi trường local')
            ->assertExitCode(1);
    }

    public function test_tua_vi_ve_truoc_cac_don_hang(): void
    {
        app()->detectEnvironment(fn () => 'local');
        $this->makeHistory();

        $this->assertSame(3_500_000, $this->wallets()->for($this->customer)->balance);

        $this->artisan('shop:reset --force')->assertExitCode(0);

        // Chỉ còn khoản nạp của khách: tiền mua và tiền hoàn đều thuộc về đơn.
        $wallet = $this->wallets()->for($this->customer);
        $this->assertSame(5_000_000, $wallet->balance);
        $this->assertSame(1, WalletTransactionModel::where('wallet_id', $wallet->wallet_id)->count());
    }

    public function test_vi_con_doc_duoc_sau_khi_reset(): void
    {
        app()->detectEnvironment(fn () => 'local');
        $this->makeHistory();

        $this->artisan('shop:reset --force')->assertExitCode(0);

        // for() ném WalletTampered nếu chữ ký không khớp, nên gọi được là đủ.
        $wallet = $this->wallets()->for($this->customer);

        $this->assertSame(1, (int) $wallet->version);
        $this->assertNotNull($wallet->balance_hash);
    }

    public function test_khoan_nap_va_rut_cua_khach_khong_bi_dong_vao(): void
    {
        app()->detectEnvironment(fn () => 'local');
        $wallets = $this->wallets();
        $wallet = $wallets->for($this->customer);

        $wallets->credit($wallet, 1_000_000, WalletTransactionModel::TYPE_TOPUP, 'Nạp', WalletService::REF_TOPUP, 1);
        $wallets->debit($wallet->fresh(), 300_000, WalletTransactionModel::TYPE_WITHDRAW, 'Rút', WalletService::REF_WITHDRAWAL, 1);

        $this->artisan('shop:reset --force')->assertExitCode(0);

        $this->assertSame(700_000, $wallets->for($this->customer)->balance);
        $this->assertSame(2, WalletTransactionModel::count());
    }

    public function test_xoa_sach_don_hang_va_bang_lien_quan(): void
    {
        app()->detectEnvironment(fn () => 'local');
        $this->makeOrder();

        $this->assertSame(1, OrderModel::count());

        $this->artisan('shop:reset --force')->assertExitCode(0);

        $this->assertSame(0, OrderModel::count());
        $this->assertSame(0, DB::table('order_details')->count());
        $this->assertSame(0, DB::table('order_status_logs')->count());
        $this->assertSame(0, DB::table('statistical')->count());
    }

    public function test_giu_nguyen_san_pham_va_nguoi_dung(): void
    {
        app()->detectEnvironment(fn () => 'local');
        $this->makeOrder();
        $soSanPham = DB::table('products')->count();

        $this->artisan('shop:reset --force')->assertExitCode(0);

        $this->assertSame($soSanPham, DB::table('products')->count());
        $this->assertNotNull(UserModel::find($this->customer->user_id));
    }

    public function test_co_the_giu_lai_vi(): void
    {
        app()->detectEnvironment(fn () => 'local');
        $this->makeHistory();

        $this->artisan('shop:reset --force --keep-wallets')->assertExitCode(0);

        $this->assertSame(3_500_000, $this->wallets()->for($this->customer)->balance);
        $this->assertSame(3, WalletTransactionModel::count());
    }
}
