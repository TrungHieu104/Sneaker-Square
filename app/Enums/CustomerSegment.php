<?php

namespace App\Enums;

/**
 * The groups RFM sorts customers into. Internal to the shop: a customer is
 * never shown which group they are in, only what it earns them.
 */
enum CustomerSegment: string
{
    case Loyal = 'loyal';
    case Promising = 'promising';
    case Regular = 'regular';
    case AtRisk = 'at_risk';
    case Lost = 'lost';

    public const TOP_SCORE = 3;

    public const LOW_SCORE = 1;

    /**
     * Only the named patterns earn a group of their own; every other mix of
     * scores says nothing a shop could act on.
     */
    public static function fromScores(int $recency, int $frequency, int $monetary): self
    {
        return match (true) {
            $recency === self::TOP_SCORE && $frequency === self::TOP_SCORE && $monetary === self::TOP_SCORE => self::Loyal,
            $recency === self::TOP_SCORE && $frequency === self::LOW_SCORE => self::Promising,
            $recency === self::LOW_SCORE && $frequency === self::TOP_SCORE && $monetary === self::TOP_SCORE => self::AtRisk,
            $recency === self::LOW_SCORE && $frequency === self::LOW_SCORE && $monetary === self::LOW_SCORE => self::Lost,
            default => self::Regular,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Loyal => 'Thân thiết',
            self::Promising => 'Tiềm năng',
            self::Regular => 'Khách thường',
            self::AtRisk => 'Sắp rời bỏ',
            self::Lost => 'Đã rời bỏ',
        };
    }

    /**
     * A lost customer gets one last offer and is then left alone: more discounts
     * for someone who has stopped buying cost more than they bring back.
     */
    public function isOfferedOnce(): bool
    {
        return $this === self::Lost;
    }

    /**
     * Who is in the group, in the words of someone who has never heard of RFM.
     */
    public function criteria(): string
    {
        return match ($this) {
            self::Loyal => 'Mới mua gần đây, nhiều đơn và chi nhiều tiền',
            self::Promising => 'Mới mua gần đây nhưng còn ít đơn',
            self::Regular => 'Các khách còn lại',
            self::AtRisk => 'Từng mua nhiều đơn và chi nhiều tiền, nhưng lâu rồi chưa quay lại',
            self::Lost => 'Lâu rồi chưa quay lại, ít đơn và chi ít tiền',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Loyal => 'bg-label-primary',
            self::Promising => 'bg-label-success',
            self::Regular => 'bg-label-info',
            self::AtRisk => 'bg-label-warning',
            self::Lost => 'bg-label-secondary',
        };
    }

    public function chartColour(): string
    {
        return match ($this) {
            self::Loyal => '#696cff',
            self::Promising => '#71dd37',
            self::Regular => '#03c3ec',
            self::AtRisk => '#ffab00',
            self::Lost => '#8592a3',
        };
    }
}
