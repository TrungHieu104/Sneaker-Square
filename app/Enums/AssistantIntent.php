<?php

namespace App\Enums;

/**
 * What a customer's question to the support assistant is about, decided by
 * plain rules before any model is asked.
 */
enum AssistantIntent: string
{
    case Order = 'order';
    case Size = 'size';
    case Product = 'product';
    case Policy = 'policy';
    case Handoff = 'handoff';

    public function label(): string
    {
        return match ($this) {
            self::Order => 'Đơn hàng',
            self::Size => 'Size',
            self::Product => 'Sản phẩm',
            self::Policy => 'Chính sách',
            self::Handoff => 'Gặp nhân viên',
        };
    }

    /**
     * The Sneat badge tone for the same intent.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Order => 'warning',
            self::Size, self::Product => 'primary',
            self::Policy, self::Handoff => 'secondary',
        };
    }
}
