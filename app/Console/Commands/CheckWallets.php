<?php

namespace App\Console\Commands;

use App\Models\WalletModel;
use App\Models\WalletTopupModel;
use App\Models\WalletTransactionModel;
use App\Services\Wallet\WalletSignature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Reads every wallet back and says whether it still adds up.
 *
 * The application cannot stop somebody with a database account from editing a
 * balance. What it can do is notice. Five questions are asked of each wallet,
 * and any one of them failing means the number shown to a customer is not the
 * number the shop's own records support.
 */
class CheckWallets extends Command
{
    protected $signature = 'wallet:doi-soat {--vi= : Chỉ kiểm một ví theo wallet_id}';

    protected $description = 'Đối soát số dư ví với sổ cái, chữ ký và các lần nạp tiền';

    /** @var array<int, array{int, string}> */
    private array $problems = [];

    public function handle(WalletSignature $signature): int
    {
        $wallets = WalletModel::query()
            ->when($this->option('vi'), fn ($q) => $q->where('wallet_id', (int) $this->option('vi')))
            ->orderBy('wallet_id')
            ->get();

        if ($wallets->isEmpty()) {
            $this->info('Chưa có ví nào để đối soát.');

            return self::SUCCESS;
        }

        foreach ($wallets as $wallet) {
            $this->checkSignature($signature, $wallet);
            $this->checkLedgerTotal($wallet);
            $this->checkChains($signature, $wallet);
            $this->checkTopupsAreReal($wallet);
        }

        return $this->report($signature, $wallets->count());
    }

    private function checkSignature(WalletSignature $signature, WalletModel $wallet): void
    {
        if ($wallet->balance_hash === null) {
            $this->flag($wallet, 'Chưa có chữ ký số dư (ví tạo trước khi bật ký). Chạy lại một giao dịch bất kỳ để ký.');

            return;
        }

        if (! $signature->balanceMatches($wallet)) {
            $this->flag($wallet, 'Chữ ký số dư KHÔNG khớp — số dư đã bị sửa ngoài ứng dụng.');
        }
    }

    private function checkLedgerTotal(WalletModel $wallet): void
    {
        $entries = $this->entries($wallet);

        $total = $entries->sum(
            fn (WalletTransactionModel $entry) => $entry->isCredit() ? $entry->amount : -$entry->amount
        );

        if ($total !== $wallet->balance) {
            $this->flag($wallet, sprintf(
                'Sổ cái cộng ra %s đ nhưng số dư ghi %s đ (lệch %s đ).',
                number_format($total, 0, ',', '.'),
                number_format($wallet->balance, 0, ',', '.'),
                number_format($wallet->balance - $total, 0, ',', '.'),
            ));
        }
    }

    /**
     * Walks the ledger oldest first, checking both chains at once: the running
     * balance against each row's `balance_after`, and each row's signature
     * against the row before it.
     */
    private function checkChains(WalletSignature $signature, WalletModel $wallet): void
    {
        $running = 0;
        $previousHash = null;

        foreach ($this->entries($wallet) as $entry) {
            $running += $entry->isCredit() ? $entry->amount : -$entry->amount;

            if ($running !== $entry->balance_after) {
                $this->flag($wallet, sprintf(
                    'Giao dịch #%d ghi số dư sau là %s đ, cộng dồn ra %s đ.',
                    $entry->transaction_id,
                    number_format($entry->balance_after, 0, ',', '.'),
                    number_format($running, 0, ',', '.'),
                ));
            }

            if ($entry->entry_hash === null) {
                $this->flag($wallet, 'Giao dịch #'.$entry->transaction_id.' chưa có chữ ký chuỗi.');
            } elseif (! $signature->entryMatches($entry, $previousHash)) {
                $this->flag($wallet, sprintf(
                    'Chuỗi chữ ký gãy tại giao dịch #%d — dòng này hoặc một dòng trước nó đã bị sửa, xoá hoặc chèn thêm.',
                    $entry->transaction_id,
                ));
            }

            $previousHash = $entry->entry_hash;
        }
    }

    /**
     * Money that came in from a gateway has to point at a top-up the shop
     * actually recorded. Somebody inventing a credit row has to invent the
     * top-up too — and the top-up's gateway reference cannot be invented,
     * because MoMo and VNPay hold the other copy.
     */
    private function checkTopupsAreReal(WalletModel $wallet): void
    {
        $credits = $this->entries($wallet)
            ->where('type', WalletTransactionModel::TYPE_TOPUP);

        foreach ($credits as $entry) {
            $topup = $entry->reference_id
                ? WalletTopupModel::find($entry->reference_id)
                : null;

            if (! $topup || $topup->wallet_id !== $wallet->wallet_id) {
                $this->flag($wallet, 'Giao dịch nạp #'.$entry->transaction_id.' không trỏ tới yêu cầu nạp nào của ví này.');

                continue;
            }

            if ($topup->status !== WalletTopupModel::PAID || $topup->amount !== $entry->amount) {
                $this->flag($wallet, sprintf(
                    'Giao dịch nạp #%d cộng %s đ nhưng yêu cầu nạp %s đang ở trạng thái "%s" với số tiền %s đ.',
                    $entry->transaction_id,
                    number_format($entry->amount, 0, ',', '.'),
                    $topup->topup_code,
                    $topup->status,
                    number_format($topup->amount, 0, ',', '.'),
                ));
            }
        }
    }

    /**
     * @return Collection<int, WalletTransactionModel>
     */
    private function entries(WalletModel $wallet)
    {
        return WalletTransactionModel::where('wallet_id', $wallet->wallet_id)
            ->orderBy('transaction_id')
            ->get();
    }

    private function flag(WalletModel $wallet, string $message): void
    {
        $this->problems[] = [(int) $wallet->wallet_id, $message];
    }

    private function report(WalletSignature $signature, int $checked): int
    {
        // Worth writing down somewhere outside the database — a file, an email,
        // another machine. Whoever rewrites the ledger cannot also rewrite a
        // copy they have no access to, so a changed anchor is proof on its own.
        $anchor = WalletTransactionModel::orderByDesc('transaction_id')->value('entry_hash');

        if ($this->problems === []) {
            $this->info('Đã đối soát '.$checked.' ví, không phát hiện sai lệch.');
            $this->line('Mốc chuỗi mới nhất: '.($anchor ?? 'chưa có giao dịch nào'));

            return self::SUCCESS;
        }

        $this->error('Phát hiện '.count($this->problems).' sai lệch trên '.$checked.' ví:');
        $this->table(['Ví', 'Vấn đề'], $this->problems);
        $this->line('Mốc chuỗi mới nhất: '.($anchor ?? 'chưa có giao dịch nào'));

        return self::FAILURE;
    }
}
