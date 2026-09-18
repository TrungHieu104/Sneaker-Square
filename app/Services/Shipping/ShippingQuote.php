<?php

namespace App\Services\Shipping;

use Carbon\CarbonImmutable;

/**
 * What one parcel costs to send, and when it is expected to arrive.
 *
 * `$estimated` is null when the carrier answered the price but not the date,
 * which happens on routes GHN has no leadtime for. A missing date is not a
 * reason to block the order.
 */
final class ShippingQuote
{
    public function __construct(
        public readonly int $fee,
        public readonly ?CarbonImmutable $estimated = null,
        public readonly ?int $serviceId = null,
    ) {}

    /**
     * The date as the checkout page says it: "Thứ Tư, 16/09".
     */
    public function estimatedText(): ?string
    {
        if (! $this->estimated) {
            return null;
        }

        $weekdays = [
            'Sunday' => 'Chủ Nhật', 'Monday' => 'Thứ Hai', 'Tuesday' => 'Thứ Ba',
            'Wednesday' => 'Thứ Tư', 'Thursday' => 'Thứ Năm', 'Friday' => 'Thứ Sáu',
            'Saturday' => 'Thứ Bảy',
        ];

        return $weekdays[$this->estimated->format('l')].', '.$this->estimated->format('d/m');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'fee' => $this->fee,
            'fee_text' => number_format($this->fee, 0, ',', '.').' VNĐ',
            'estimated' => $this->estimated?->toDateString(),
            'estimated_text' => $this->estimatedText(),
            'service_id' => $this->serviceId,
        ];
    }
}
