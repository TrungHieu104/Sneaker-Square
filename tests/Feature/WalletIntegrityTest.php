<?php

namespace Tests\Feature;

use App\Models\UserModel;
use App\Models\WalletModel;
use App\Models\WalletTransactionModel;
use App\Services\Wallet\WalletService;
use App\Services\Wallet\WalletTampered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * What happens when somebody edits the money straight in the database.
 *
 * Every test here goes through the query builder rather than the models, on
 * purpose: that is what a person with a MySQL account and phpMyAdmin does, and
 * none of the application's rules apply to them. The question is not whether
 * they can write the number — they can — but whether the shop finds out.
 */
class WalletIntegrityTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->customer = $this->makeUser();

        config(['services.wallet.signing_key' => 'khoa-ky-vi-de-test']);
    }

    private function wallets(): WalletService
    {
        return app(WalletService::class);
    }

    private function fund(int $amount, string $type = WalletTransactionModel::TYPE_ADJUSTMENT): WalletModel
    {
        $wallet = $this->wallets()->for($this->customer);
        $this->wallets()->credit($wallet, $amount, $type, 'Nạp cho bài kiểm thử');

        return $wallet->fresh();
    }

    /**
     * The one thing the application cannot do: stop the write.
     */
    private function tamper(WalletModel $wallet, array $values): void
    {
        DB::table('wallets')->where('wallet_id', $wallet->wallet_id)->update($values);
    }

    private function reconcile(): int
    {
        return $this->artisan('wallet:doi-soat')->run();
    }

    // ------------------------------------------------- sửa số dư trong bảng ví

    public function test_sua_so_du_bang_tay_thi_vi_bi_khoa_khong_doc_duoc(): void
    {
        $wallet = $this->fund(100_000);

        $this->tamper($wallet, ['balance' => 99_000_000]);

        $this->expectException(WalletTampered::class);
        $this->wallets()->for($this->customer);
    }

    public function test_sua_so_du_bang_tay_thi_doi_soat_bao_sai(): void
    {
        $wallet = $this->fund(100_000);

        $this->tamper($wallet, ['balance' => 99_000_000]);

        $this->artisan('wallet:doi-soat')
            ->expectsOutputToContain('Phát hiện')
            ->assertExitCode(1);
    }

    public function test_sua_so_du_thi_khong_tieu_duoc(): void
    {
        $wallet = $this->fund(100_000);

        $this->tamper($wallet, ['balance' => 99_000_000]);

        $this->expectException(WalletTampered::class);
        $this->wallets()->debit($wallet->fresh(), 50_000_000, WalletTransactionModel::TYPE_PAYMENT, 'Tiêu tiền khống');
    }

    public function test_chep_lai_toan_bo_dong_vi_cu_van_bi_bat(): void
    {
        // The whole row as it stood after the first credit: balance, version
        // and the signature that went with them.
        $wallet = $this->fund(100_000);
        $cu = DB::table('wallets')->where('wallet_id', $wallet->wallet_id)->first();

        $this->wallets()->debit($wallet->fresh(), 60_000, WalletTransactionModel::TYPE_PAYMENT, 'Mua hàng');

        $this->tamper($wallet, [
            'balance' => $cu->balance,
            'version' => $cu->version,
            'balance_hash' => $cu->balance_hash,
        ]);

        $this->expectException(WalletTampered::class);
        $this->wallets()->for($this->customer);
    }

    public function test_xoa_chu_ky_de_ne_kiem_tra_thi_doi_soat_van_bao(): void
    {
        $wallet = $this->fund(100_000);

        $this->tamper($wallet, ['balance' => 99_000_000, 'balance_hash' => null]);

        $this->artisan('wallet:doi-soat')
            ->expectsOutputToContain('Phát hiện')
            ->assertExitCode(1);
    }

    // ----------------------------------------------------- sửa sổ cái

    public function test_sua_mot_dong_so_cai_thi_gay_chuoi(): void
    {
        $wallet = $this->fund(100_000);
        $this->wallets()->credit($wallet->fresh(), 50_000, WalletTransactionModel::TYPE_ADJUSTMENT, 'Lần hai');
        $this->wallets()->credit($wallet->fresh(), 20_000, WalletTransactionModel::TYPE_ADJUSTMENT, 'Lần ba');

        $dongGiua = WalletTransactionModel::orderBy('transaction_id')->skip(1)->first();
        DB::table('wallet_transactions')
            ->where('transaction_id', $dongGiua->transaction_id)
            ->update(['amount' => 5_000_000]);

        $this->artisan('wallet:doi-soat')
            ->expectsOutputToContain('Phát hiện')
            ->assertExitCode(1);
    }

    public function test_xoa_mot_dong_so_cai_o_giua_thi_gay_chuoi(): void
    {
        $wallet = $this->fund(100_000);
        $this->wallets()->credit($wallet->fresh(), 50_000, WalletTransactionModel::TYPE_ADJUSTMENT, 'Lần hai');
        $this->wallets()->credit($wallet->fresh(), 20_000, WalletTransactionModel::TYPE_ADJUSTMENT, 'Lần ba');

        $dongGiua = WalletTransactionModel::orderBy('transaction_id')->skip(1)->first();
        DB::table('wallet_transactions')->where('transaction_id', $dongGiua->transaction_id)->delete();

        $this->assertSame(1, $this->reconcile());
    }

    public function test_chen_them_mot_dong_nap_khong_co_that(): void
    {
        $wallet = $this->fund(100_000);

        DB::table('wallet_transactions')->insert([
            'wallet_id' => $wallet->wallet_id,
            'type' => WalletTransactionModel::TYPE_TOPUP,
            'direction' => WalletTransactionModel::IN,
            'amount' => 9_000_000,
            'balance_after' => 9_100_000,
            'description' => 'Nạp khống',
            'created_at' => now(),
        ]);
        $this->tamper($wallet, ['balance' => 9_100_000]);

        $this->assertSame(1, $this->reconcile());
    }

    public function test_dong_nap_phai_tro_toi_yeu_cau_nap_da_thanh_toan(): void
    {
        $wallet = $this->fund(100_000, WalletTransactionModel::TYPE_TOPUP);

        // Signed correctly, balance correct — the only thing wrong is that no
        // gateway ever paid for it.
        $this->assertSame(1, $this->reconcile());
    }

    // ------------------------------------------------------------ ví sạch

    public function test_vi_binh_thuong_thi_doi_soat_khong_bao_gi(): void
    {
        $wallet = $this->fund(100_000);
        $this->wallets()->debit($wallet->fresh(), 30_000, WalletTransactionModel::TYPE_PAYMENT, 'Mua hàng');

        $this->artisan('wallet:doi-soat')
            ->expectsOutputToContain('không phát hiện sai lệch')
            ->assertExitCode(0);
    }

    public function test_chua_co_vi_nao_thi_khong_coi_la_loi(): void
    {
        $this->artisan('wallet:doi-soat')->assertExitCode(0);
    }

    public function test_doi_soat_in_ra_moc_chuoi_de_luu_ra_ngoai(): void
    {
        $wallet = $this->fund(100_000);
        $moc = WalletTransactionModel::orderByDesc('transaction_id')->value('entry_hash');

        $this->artisan('wallet:doi-soat')
            ->expectsOutputToContain($moc)
            ->assertExitCode(0);
    }

    public function test_khach_mo_trang_vi_bi_sua_thi_thay_thong_bao_chu_khong_loi_500(): void
    {
        $wallet = $this->fund(100_000);
        $this->tamper($wallet, ['balance' => 99_000_000]);

        $this->actingAs($this->customer)
            ->from(route('user.wallet'))
            ->get(route('user.wallet'))
            ->assertRedirect(route('user.wallet'))
            ->assertSessionHas('message', fn (string $message) => str_contains($message, 'tạm khoá để đối soát'));
    }

    public function test_doi_khoa_ky_thi_moi_chu_ky_cu_deu_khong_hop_le(): void
    {
        $this->fund(100_000);

        config(['services.wallet.signing_key' => 'khoa-khac-hoan-toan']);

        $this->assertSame(1, $this->reconcile());
    }
}
