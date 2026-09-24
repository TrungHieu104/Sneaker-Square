<?php

namespace App\Services\Wallet;

use App\Models\UserModel;
use App\Models\WalletTopupModel;
use App\Models\WalletTransactionModel;
use App\Services\Payment\GatewayCharge;
use App\Services\Payment\InvalidPaymentCallbackException;
use App\Services\Payment\PaymentCallback;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Money coming into a wallet from a payment gateway.
 *
 * A top-up is recorded before the customer leaves for the gateway, because the
 * callback names a code and nothing else: with no row waiting for it there is
 * no way to tell whose wallet the money belongs in, or how much was asked for.
 */
class WalletTopups
{
    public function __construct(
        private WalletService $wallets,
        private PaymentGatewayManager $gateways,
    ) {}

    /**
     * @throws InvalidPaymentCallbackException when the gateway will not open a session
     */
    public function start(UserModel $user, int $amount, string $gatewayName): WalletTopupModel
    {
        $gateway = $this->gateways->byName($gatewayName);

        if (! $gateway) {
            throw new InvalidPaymentCallbackException('Phương thức nạp tiền không hợp lệ.');
        }

        $topup = WalletTopupModel::create([
            'wallet_id' => $this->wallets->for($user)->wallet_id,
            'topup_code' => WalletTopupModel::generateCode(),
            'amount' => $amount,
            'gateway' => $gatewayName,
            'status' => WalletTopupModel::PENDING,
        ]);

        $topup->checkout_url = $gateway->checkoutUrl(GatewayCharge::forTopup($topup));
        $topup->save();

        return $topup;
    }

    /**
     * Credits the wallet for a verified callback.
     *
     * @return bool whether this call is the one that credited the money
     *
     * @throws InvalidPaymentCallbackException when the code is unknown or the
     *                                         amount is not the one we asked for
     */
    public function settle(PaymentCallback $callback): bool
    {
        return (bool) DB::transaction(function () use ($callback) {
            $topup = WalletTopupModel::where('topup_code', $callback->orderCode)->lockForUpdate()->first();

            if (! $topup) {
                throw new InvalidPaymentCallbackException('Không tìm thấy yêu cầu nạp tiền '.$callback->orderCode.'.');
            }

            if ($callback->amount !== null && $callback->amount !== $topup->amount) {
                throw new InvalidPaymentCallbackException('Số tiền nạp không khớp với yêu cầu '.$topup->topup_code.'.');
            }

            // The gateway sends both a redirect and an IPN for the same money.
            if ($topup->status !== WalletTopupModel::PENDING) {
                return false;
            }

            $topup->status = WalletTopupModel::PAID;
            $topup->gateway_reference = $callback->reference;
            $topup->paid_at = Carbon::now();
            $topup->save();

            $this->wallets->credit(
                $topup->wallet,
                $topup->amount,
                WalletTransactionModel::TYPE_TOPUP,
                'Nạp tiền qua '.$this->gatewayLabel($topup->gateway),
                WalletService::REF_TOPUP,
                (int) $topup->topup_id,
            );

            return true;
        });
    }

    /**
     * The customer backed out, or the bank refused. Nothing was taken, so
     * there is nothing to give back — only the record to close.
     */
    public function markFailed(string $code): void
    {
        DB::transaction(function () use ($code) {
            $topup = WalletTopupModel::where('topup_code', $code)->lockForUpdate()->first();

            if ($topup && $topup->status === WalletTopupModel::PENDING) {
                $topup->status = WalletTopupModel::FAILED;
                $topup->save();
            }
        });
    }

    public function gatewayLabel(string $gateway): string
    {
        return match ($gateway) {
            'payUrl' => 'MoMo',
            'redirect' => 'VNPay',
            default => $gateway,
        };
    }
}
