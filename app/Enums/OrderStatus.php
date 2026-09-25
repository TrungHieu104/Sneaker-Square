<?php

namespace App\Enums;

/**
 * Where an order stands in its own lifecycle.
 *
 * The values name the goods' journey, not the money's: whether the customer
 * has paid lives in `order_payment_status`, and the carrier's own 22 statuses
 * live in `order_shipping_status`. This column keeps only the milestones the
 * shop acts on.
 */
enum OrderStatus: string
{
    case New = 'new';
    case CancelRequested = 'cancel_requested';
    case Confirmed = 'confirmed';
    case ReadyToShip = 'ready_to_ship';
    case Delivering = 'delivering';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Returning = 'returning';
    case Returned = 'returned';
    case PartiallyReturned = 'partially_returned';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Chờ xác nhận',
            self::CancelRequested => 'Khách xin huỷ',
            self::Confirmed => 'Đã xác nhận',
            self::ReadyToShip => 'Chờ lấy hàng',
            self::Delivering => 'Đang giao',
            self::Delivered => 'Đã giao',
            self::Completed => 'Thành công',
            self::Returning => 'Đang hoàn về',
            self::Returned => 'Hoàn về kho',
            self::PartiallyReturned => 'Hoàn một phần',
            self::Cancelled => 'Đã huỷ',
        };
    }

    /**
     * Colour for the plain-text status columns in the older admin tables.
     */
    public function color(): string
    {
        return match ($this) {
            self::New => 'darkblue',
            self::CancelRequested => 'crimson',
            self::Confirmed => 'green',
            self::ReadyToShip => 'teal',
            self::Delivering => 'steelblue',
            self::Delivered => 'darkcyan',
            self::Completed => 'chocolate',
            self::Returning, self::Returned, self::PartiallyReturned => 'orange',
            self::Cancelled => 'red',
        };
    }

    /**
     * The Sneat badge tone for the same status.
     */
    public function badge(): string
    {
        return match ($this) {
            self::New => 'info',
            self::CancelRequested => 'danger',
            self::Confirmed, self::Completed => 'success',
            self::ReadyToShip, self::Delivering => 'primary',
            self::Delivered => 'info',
            self::Returning, self::Returned, self::PartiallyReturned => 'warning',
            self::Cancelled => 'secondary',
        };
    }

    /**
     * The order is out of the shop's hands and on its way to the customer.
     */
    public function isOnTheRoad(): bool
    {
        return in_array($this, [self::Delivering, self::Delivered], true);
    }

    /**
     * The shop has taken the order on: it is past the inbox and the goods are
     * spoken for. A customer waiting on a cancellation is still in here — the
     * order was accepted, and may yet stay that way.
     */
    public function isAccepted(): bool
    {
        return ! in_array($this, [self::New, self::Cancelled], true);
    }

    /**
     * The goods went out and did not all stay out. A partial return counts:
     * the order has a parcel coming home, whatever the customer kept.
     */
    public function isComingBack(): bool
    {
        return in_array($this, [self::Returning, self::Returned, self::PartiallyReturned], true);
    }

    /**
     * Nothing more will happen to this order on its own.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Returned, self::PartiallyReturned, self::Cancelled], true);
    }

    /**
     * The same set isComingBack() asks about, for the lists that need it as a
     * where-in rather than a question about one order.
     *
     * @return array<int, self>
     */
    public static function comingBack(): array
    {
        return [self::Returning, self::Returned, self::PartiallyReturned];
    }

    /**
     * Statuses that still owe the shop work, for the admin's open-orders lists.
     *
     * @return array<int, self>
     */
    public static function open(): array
    {
        return [self::New, self::CancelRequested, self::Confirmed, self::ReadyToShip, self::Delivering, self::Delivered, self::Returning];
    }

    /**
     * The value each order carried before the column became a string, kept so
     * the migration and any old export can be read back.
     *
     * @return array<int, string>
     */
    public static function legacyMap(): array
    {
        return [
            0 => self::New->value,
            1 => self::Confirmed->value,
            2 => self::Cancelled->value,
            3 => self::Returned->value,
            10 => self::Completed->value,
        ];
    }
}
