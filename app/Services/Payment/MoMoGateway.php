<?php

namespace App\Services\Payment;

use App\Models\OrderModel;
use Illuminate\Support\Facades\Http;

/**
 * MoMo, API version 2.
 *
 * Like VNPay, the request sent to MoMo was signed but the reply was not
 * checked. Both the browser redirect and the server-to-server IPN carry a
 * `signature` field, and both are verified here with the same rule.
 */
class MoMoGateway implements PaymentGateway
{
    /**
     * MoMo returns 0 for a completed payment and 1006 when the customer
     * declined on the confirmation screen.
     */
    private const SUCCESS_CODE = 0;

    private const CANCELLED_CODE = 1006;

    private const PENDING_CODE = 9000;

    public function name(): string
    {
        return 'payUrl';
    }

    public function handles(array $params): bool
    {
        return isset($params['orderId']) && isset($params['signature']);
    }

    public function checkoutUrl(OrderModel $order): string
    {
        $accessKey = (string) config('services.momo.access_key');
        $secretKey = (string) config('services.momo.secret_key');
        $partnerCode = (string) config('services.momo.partner_code');

        $payload = [
            'partnerCode' => $partnerCode,
            'partnerName' => 'Sneaker Square',
            'storeId' => 'SneakerSquare',
            'requestId' => (string) $order->order_code . '-' . time(),
            'amount' => (int) $order->order_total,
            'orderId' => (string) $order->order_code,
            'orderInfo' => 'Thanh toán hóa đơn ' . $order->order_code . ' qua MoMo',
            'redirectUrl' => route('process.checkout'),
            'ipnUrl' => route('payment.ipn'),
            'lang' => 'vi',
            'extraData' => '',
            'requestType' => 'payWithATM',
        ];

        $payload['signature'] = hash_hmac('sha256', $this->createHashData($accessKey, $payload), $secretKey);

        $response = Http::timeout(10)
            ->acceptJson()
            ->post((string) config('services.momo.endpoint'), $payload)
            ->json();

        if (! isset($response['payUrl'])) {
            throw new InvalidPaymentCallbackException(
                $this->describeCreateFailure($response)
            );
        }

        return (string) $response['payUrl'];
    }

    public function verify(array $params): PaymentCallback
    {
        $received = (string) ($params['signature'] ?? '');
        $expected = hash_hmac(
            'sha256',
            $this->returnHashData((string) config('services.momo.access_key'), $params),
            (string) config('services.momo.secret_key')
        );

        if ($received === '' || ! hash_equals($expected, $received)) {
            throw new InvalidPaymentCallbackException('Chữ ký MoMo không hợp lệ.');
        }

        $resultCode = (int) ($params['resultCode'] ?? -1);

        return new PaymentCallback(
            gateway: $this->name(),
            orderCode: (string) ($params['orderId'] ?? ''),
            amount: isset($params['amount']) ? (int) $params['amount'] : null,
            outcome: $this->outcomeFor($resultCode),
            reference: isset($params['transId']) ? (string) $params['transId'] : null,
            message: isset($params['message']) ? (string) $params['message'] : null,
        );
    }

    private function outcomeFor(int $resultCode): PaymentOutcome
    {
        return match ($resultCode) {
            self::SUCCESS_CODE => PaymentOutcome::Paid,
            self::CANCELLED_CODE => PaymentOutcome::Cancelled,
            self::PENDING_CODE => PaymentOutcome::Pending,
            default => PaymentOutcome::Failed,
        };
    }

    /**
     * The string MoMo signs when a payment is created. The field order is fixed
     * by their documentation and is not alphabetical by accident.
     *
     * @param  array<string, mixed>  $payload
     */
    private function createHashData(string $accessKey, array $payload): string
    {
        return 'accessKey=' . $accessKey
            . '&amount=' . $payload['amount']
            . '&extraData=' . $payload['extraData']
            . '&ipnUrl=' . $payload['ipnUrl']
            . '&orderId=' . $payload['orderId']
            . '&orderInfo=' . $payload['orderInfo']
            . '&partnerCode=' . $payload['partnerCode']
            . '&redirectUrl=' . $payload['redirectUrl']
            . '&requestId=' . $payload['requestId']
            . '&requestType=' . $payload['requestType'];
    }

    /**
     * The string MoMo signs on the way back. A different field list from the
     * outgoing one, again fixed by their documentation.
     *
     * @param  array<string, mixed>  $params
     */
    private function returnHashData(string $accessKey, array $params): string
    {
        $field = fn (string $key): string => (string) ($params[$key] ?? '');

        return 'accessKey=' . $accessKey
            . '&amount=' . $field('amount')
            . '&extraData=' . $field('extraData')
            . '&message=' . $field('message')
            . '&orderId=' . $field('orderId')
            . '&orderInfo=' . $field('orderInfo')
            . '&orderType=' . $field('orderType')
            . '&partnerCode=' . $field('partnerCode')
            . '&payType=' . $field('payType')
            . '&requestId=' . $field('requestId')
            . '&responseTime=' . $field('responseTime')
            . '&resultCode=' . $field('resultCode')
            . '&transId=' . $field('transId');
    }

    /**
     * @param  array<string, mixed>|null  $response
     */
    private function describeCreateFailure(?array $response): string
    {
        $message = $response['message'] ?? 'Không thể khởi tạo thanh toán MoMo.';

        if (isset($response['subErrors']) && is_array($response['subErrors'])) {
            $details = [];

            foreach ($response['subErrors'] as $subError) {
                $details[] = ($subError['field'] ?? '?') . ': ' . ($subError['message'] ?? '?');
            }

            if ($details !== []) {
                $message .= ' (' . implode(', ', $details) . ')';
            }
        }

        return $message;
    }
}
