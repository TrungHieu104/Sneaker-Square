<?php

namespace App\Services\Shipping;

/**
 * GHN's status vocabulary, in Vietnamese.
 *
 * A code that is not on this list is still recorded and still shown — the
 * carrier is free to add one, and dropping an event because it is unfamiliar
 * would leave a hole in the customer's timeline.
 */
final class GhnStatus
{
    /** @var array<string, string> */
    private const LABELS = [
        'ready_to_pick' => 'Chờ lấy hàng',
        'picking' => 'Đang lấy hàng',
        'money_collect_picking' => 'Đang thu tiền người gửi',
        'picked' => 'Đã lấy hàng',
        'cancel' => 'Đơn vận chuyển đã huỷ',
        'storing' => 'Đang nằm ở kho',
        'transporting' => 'Đang trung chuyển',
        'sorting' => 'Đang phân loại',
        'delivering' => 'Đang giao hàng',
        'money_collect_delivering' => 'Đang thu tiền người nhận',
        'delivered' => 'Giao hàng thành công',
        'delivery_fail' => 'Giao hàng không thành công',
        'waiting_to_return' => 'Chờ giao lại',
        'return' => 'Chuyển hoàn',
        'return_transporting' => 'Đang trung chuyển hàng hoàn',
        'return_sorting' => 'Đang phân loại hàng hoàn',
        'returning' => 'Đang hoàn hàng',
        'return_fail' => 'Hoàn hàng không thành công',
        'returned' => 'Đã hoàn hàng',
        'exception' => 'Đơn hàng ngoại lệ',
        'damage' => 'Hàng bị hư hỏng',
        'lost' => 'Hàng bị thất lạc',
    ];

    /**
     * Statuses after which nothing else is coming.
     */
    private const FINAL = ['delivered', 'returned', 'cancel', 'lost'];

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? $status;
    }

    public static function isFinal(string $status): bool
    {
        return in_array($status, self::FINAL, true);
    }

    public static function isFailure(string $status): bool
    {
        return in_array($status, ['delivery_fail', 'return_fail', 'exception', 'damage', 'lost', 'cancel'], true);
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::LABELS;
    }
}
