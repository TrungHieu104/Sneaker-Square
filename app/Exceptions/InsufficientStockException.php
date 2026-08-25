<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a variant no longer has enough stock at the moment it is decremented.
 *
 * This happens when somebody else buys the remaining units between the customer
 * viewing their cart and pressing checkout. The surrounding transaction rolls
 * back, so no order is created and no stock is deducted.
 */
class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly string $productName,
        public readonly ?string $size = null,
        public readonly ?string $color = null,
    ) {
        $variant = array_filter([$color, $size]);

        parent::__construct(
            $variant
                ? sprintf('Sản phẩm "%s" (%s) không còn đủ số lượng trong kho.', $productName, implode(', ', $variant))
                : sprintf('Sản phẩm "%s" không còn đủ số lượng trong kho.', $productName)
        );
    }

    /**
     * The message shown to the customer.
     */
    public function userMessage(): string
    {
        return $this->getMessage() . ' Vui lòng giảm số lượng hoặc chọn sản phẩm khác.';
    }
}
