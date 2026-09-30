<?php

namespace App\Console\Commands;

use App\Models\WalletModel;
use App\Models\WalletTransactionModel;
use App\Services\Wallet\WalletSignature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Clears the orders a developer piled up while testing, and rewinds the
 * wallets to what they held before those orders existed.
 *
 * Deliberately not a `migrate:fresh`: products, users, coupons and settings
 * are the fixture worth keeping, and reseeding them costs an afternoon of
 * clicking. Only the transactional side goes.
 *
 * The wallet is the reason this is a command rather than a handful of DELETEs.
 * Its balance is signed against the ledger's newest row, and each ledger row
 * is signed against the one before it, so removing entries in the middle of a
 * chain leaves every later row unverifiable and locks the owner out of the
 * shop entirely. What happens here instead: order and refund entries are
 * dropped, the survivors are replayed in order to recompute each running
 * balance, and the whole chain is re-signed from the front.
 */
class ResetShopData extends Command
{
    protected $signature = 'shop:reset
                            {--force : Skip the confirmation prompt}
                            {--keep-wallets : Leave wallets and their ledger untouched}';

    protected $description = 'Xoá dữ liệu đơn hàng và tua lại ví về trước các đơn đó (chỉ chạy ở local)';

    /**
     * Wiped outright: every row is about one order's journey.
     *
     * @var array<int, string>
     */
    private const ORDER_TABLES = [
        'payment_attempts',
        'order_return_items',
        'order_returns',
        'order_status_logs',
        'shipment_events',
        'order_details',
        'order',
        'statistical',
    ];

    /**
     * Ledger entries that only exist because of an order. A top-up or a
     * withdrawal is the customer's own money moving and is left alone, so the
     * balance rewinds to what it was before any testing, not to zero.
     *
     * @var array<int, string>
     */
    private const ORDER_REFERENCES = [
        'order',
        'order_refund',
        'return_refund',
        'payment_attempt',
    ];

    public function handle(WalletSignature $signature): int
    {
        if (! app()->environment('local')) {
            $this->error('Lệnh này chỉ chạy được ở môi trường local. APP_ENV hiện tại: '.app()->environment());

            return self::FAILURE;
        }

        $this->table(['Bảng', 'Số dòng sẽ xoá'], $this->preview());

        if (! $this->option('force') && ! $this->confirm('Xoá những dữ liệu trên?', false)) {
            $this->line('Đã huỷ, không đụng gì vào dữ liệu.');

            return self::SUCCESS;
        }

        // No wrapping transaction: TRUNCATE commits implicitly on MySQL, so a
        // transaction opened around it has nothing left to roll back and
        // throws on commit. The wallet replay below opens its own.
        $this->wipeOrders();

        if (! $this->option('keep-wallets')) {
            DB::transaction(fn () => $this->rewindWallets($signature));
        }

        $this->clearReturnImages();

        $this->newLine();
        $this->info('Xong. Sản phẩm, người dùng, mã giảm giá và cấu hình được giữ nguyên.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{0: string, 1: int}>
     */
    private function preview(): array
    {
        $rows = [];

        foreach (self::ORDER_TABLES as $table) {
            $rows[] = [$table, DB::table($table)->count()];
        }

        if (! $this->option('keep-wallets')) {
            $rows[] = [
                'wallet_transactions (đơn hàng)',
                WalletTransactionModel::whereIn('reference_type', self::ORDER_REFERENCES)->count(),
            ];
        }

        return $rows;
    }

    private function wipeOrders(): void
    {
        // The tables reference each other, and truncate cannot be ordered
        // around that on MySQL. Schema::withoutForeignKeyConstraints speaks
        // whatever dialect the connection uses, unlike a raw SET statement.
        // Each truncate stands alone and cannot be undone, which is why the
        // command shows the counts and asks before reaching here.
        Schema::withoutForeignKeyConstraints(function () {
            foreach (self::ORDER_TABLES as $table) {
                DB::table($table)->truncate();
            }
        });

        $this->line('Đã xoá: '.implode(', ', self::ORDER_TABLES));
    }

    /**
     * Replays what is left of each ledger so the running balances and the
     * hash chain agree with each other again.
     */
    private function rewindWallets(WalletSignature $signature): void
    {
        $removed = WalletTransactionModel::whereIn('reference_type', self::ORDER_REFERENCES)->delete();
        $this->line('Đã xoá '.$removed.' giao dịch ví liên quan đến đơn hàng.');

        foreach (WalletModel::all() as $wallet) {
            $entries = WalletTransactionModel::where('wallet_id', $wallet->wallet_id)
                ->orderBy('created_at')
                ->orderBy('transaction_id')
                ->get();

            $balance = 0;
            $previous = null;

            foreach ($entries as $entry) {
                $balance += $entry->direction === WalletTransactionModel::IN
                    ? (int) $entry->amount
                    : -(int) $entry->amount;

                $entry->balance_after = $balance;
                $entry->entry_hash = $signature->entryHash($entry, $previous);
                $entry->save();

                $previous = $entry->entry_hash;
            }

            // `version` counts how many times the balance moved, and the
            // replayed ledger is now the whole of that history.
            $wallet->balance = $balance;
            $wallet->version = $entries->count();
            $wallet->balance_hash = $signature->balanceHash($wallet, $previous);
            $wallet->save();

            $this->line(sprintf(
                'Ví #%d (user %d): %s đ qua %d giao dịch còn lại.',
                $wallet->wallet_id,
                $wallet->user_id,
                number_format($balance, 0, ',', '.'),
                $entries->count(),
            ));
        }
    }

    /**
     * The photos customers attached to return requests whose rows have just
     * gone. Failing here must not undo the reset, so it runs outside the
     * transaction and only reports.
     */
    private function clearReturnImages(): void
    {
        $disk = Storage::disk('public');

        if (! $disk->exists('returns')) {
            return;
        }

        $disk->deleteDirectory('returns')
            ? $this->line('Đã xoá ảnh trả hàng trong storage/app/public/returns.')
            : $this->warn('Không xoá được thư mục ảnh trả hàng, bạn xoá tay giúp.');
    }
}
