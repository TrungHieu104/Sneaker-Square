<?php

namespace App\Services;

/**
 * Turns a measured foot length into the EU size to suggest.
 *
 * EU sizes count in Paris points: a size N shoe is built on a last N × 2/3 cm
 * long. The last has to be longer than the foot by the room the toes need when
 * walking, so the suggestion is the smallest size whose last covers the foot
 * plus that room.
 */
class FootSize
{
    public const MIN_MM = 180;

    public const MAX_MM = 330;

    public const TOE_ROOM_MM = 15;

    public const MIN_SIZE = 35;

    public const MAX_SIZE = 49;

    public function sizeFor(int $footLengthMm): int
    {
        // One Paris point is 20/3 mm; integer maths keeps 245 mm from landing
        // a size up through a rounding error.
        return intdiv(($footLengthMm + self::TOE_ROOM_MM) * 3 + 19, 20);
    }

    /**
     * The longest foot each size fits, so the page that measures in the
     * browser can pick the size without a second copy of the formula.
     *
     * @return array<int, int> size => longest foot in millimetres
     */
    public function chart(): array
    {
        $chart = [];
        for ($mm = self::MIN_MM; $mm <= self::MAX_MM; $mm++) {
            $size = $this->sizeFor($mm);
            if ($size >= self::MIN_SIZE && $size <= self::MAX_SIZE) {
                $chart[$size] = $mm;
            }
        }

        return $chart;
    }
}
