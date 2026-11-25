<?php

namespace App\Enums;

/**
 * What a question the assistant could not answer is about, as Claude reads
 * it. The values are the labels staff see, and the choices Claude is given.
 */
enum AssistantTopic: string
{
    case Product = 'Sản phẩm';
    case Size = 'Size';
    case Order = 'Đơn hàng';
    case Delivery = 'Giao hàng';
    case Payment = 'Thanh toán';
    case Returns = 'Đổi trả';
    case Store = 'Cửa hàng';
    case Other = 'Khác';

    /**
     * The Sneat badge tone.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Product, self::Size => 'primary',
            self::Order, self::Delivery => 'warning',
            self::Payment, self::Returns => 'info',
            self::Store, self::Other => 'secondary',
        };
    }
}
