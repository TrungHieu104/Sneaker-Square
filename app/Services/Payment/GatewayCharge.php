<?php

namespace App\Services\Payment;

use App\Models\OrderModel;
use App\Models\WalletTopupModel;

/**
 * What a gateway is being asked to collect.
 *
 * The gateways used to take an order, which was true while an order was the
 * only thing a customer could pay for. Topping a wallet up is the second, and
 * it has no order behind it — so what they take now is the amount, the code
 * the gateway will echo back, and the line the customer reads on their bank app.
 */
final class GatewayCharge
{
    public function __construct(
        public readonly string $code,
        public readonly int $amount,
        public readonly string $description,
    ) {}

    public static function forOrder(OrderModel $order): self
    {
        return new self(
            code: (string) $order->order_code,
            amount: (int) $order->order_total,
            description: 'Thanh toán hóa đơn '.$order->order_code,
        );
    }

    public static function forTopup(WalletTopupModel $topup): self
    {
        return new self(
            code: (string) $topup->topup_code,
            amount: (int) $topup->amount,
            description: 'Nạp tiền vào SPay '.$topup->topup_code,
        );
    }
}
