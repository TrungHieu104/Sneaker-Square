<?php

namespace App\Services\Payment;

/**
 * VNPay, version 2.1.0.
 *
 * The outgoing URL was already signed before this class existed. What was
 * missing is the other half: the return URL was trusted without checking
 * `vnp_SecureHash`, so appending `?vnp_TxnRef=<any order code>` to it was
 * enough to mark somebody else's order as paid.
 */
class VnPayGateway implements PaymentGateway
{
    /**
     * Codes VNPay uses for "the customer backed out" rather than "it failed".
     */
    private const CANCELLED_CODE = '24';

    private const SUCCESS_CODE = '00';

    public function name(): string
    {
        return 'redirect';
    }

    public function handles(array $params): bool
    {
        return isset($params['vnp_TxnRef']);
    }

    public function checkoutUrl(GatewayCharge $charge): string
    {
        $input = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => (string) config('services.vnpay.tmn_code'),
            'vnp_Amount' => $charge->amount * 100,
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => now('Asia/Ho_Chi_Minh')->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => request()->ip() ?? '127.0.0.1',
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => $charge->description . ' qua VNPay',
            'vnp_OrderType' => 'billpayment',
            'vnp_ReturnUrl' => route('process.checkout'),
            'vnp_TxnRef' => $charge->code,
            'vnp_BankCode' => 'NCB',
        ];

        ksort($input);

        $query = http_build_query($input);
        $secureHash = hash_hmac('sha512', $this->hashData($input), (string) config('services.vnpay.hash_secret'));

        return config('services.vnpay.url') . '?' . $query . '&vnp_SecureHash=' . $secureHash;
    }

    public function verify(array $params): PaymentCallback
    {
        $received = (string) ($params['vnp_SecureHash'] ?? '');

        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);
        ksort($params);

        $expected = hash_hmac('sha512', $this->hashData($params), (string) config('services.vnpay.hash_secret'));

        // hash_equals compares in constant time, so a wrong signature cannot be
        // guessed one character at a time by measuring how long we take to say no.
        if ($received === '' || ! hash_equals($expected, $received)) {
            throw new InvalidPaymentCallbackException('Chữ ký VNPay không hợp lệ.');
        }

        $responseCode = (string) ($params['vnp_ResponseCode'] ?? '');
        $transactionStatus = (string) ($params['vnp_TransactionStatus'] ?? $responseCode);

        return new PaymentCallback(
            gateway: $this->name(),
            orderCode: (string) ($params['vnp_TxnRef'] ?? ''),
            // VNPay works in the smallest currency unit, so the amount comes back
            // multiplied by 100.
            amount: isset($params['vnp_Amount']) ? (int) ($params['vnp_Amount'] / 100) : null,
            outcome: $this->outcomeFor($responseCode, $transactionStatus),
            reference: isset($params['vnp_TransactionNo']) ? (string) $params['vnp_TransactionNo'] : null,
            message: $responseCode,
        );
    }

    private function outcomeFor(string $responseCode, string $transactionStatus): PaymentOutcome
    {
        if ($responseCode === self::SUCCESS_CODE && $transactionStatus === self::SUCCESS_CODE) {
            return PaymentOutcome::Paid;
        }

        if ($responseCode === self::CANCELLED_CODE) {
            return PaymentOutcome::Cancelled;
        }

        return PaymentOutcome::Failed;
    }

    /**
     * The exact string VNPay signs: sorted `key=value` pairs, both URL-encoded.
     *
     * @param  array<string, mixed>  $params
     */
    private function hashData(array $params): string
    {
        $pairs = [];

        foreach ($params as $key => $value) {
            $pairs[] = urlencode((string) $key) . '=' . urlencode((string) $value);
        }

        return implode('&', $pairs);
    }
}
