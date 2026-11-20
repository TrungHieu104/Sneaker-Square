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

    public const ASIAN_STEP_MM = 5;

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
        return array_map(fn (array $range) => $range['to'], $this->ranges());
    }

    /**
     * The foot lengths each size fits, shortest to longest, for the printed
     * size chart, with the Japanese and Korean label for it.
     *
     * Those labels are the foot length itself in 5 mm steps (26.5 cm, 265 mm),
     * so a size gets the longest step that still falls inside its range: every
     * size is 6 or 7 mm wide, so there always is one.
     *
     * @return array<int, array{from: int, to: int, asian: int}> size => foot length in millimetres
     */
    public function ranges(): array
    {
        $ranges = [];
        for ($mm = self::MIN_MM; $mm <= self::MAX_MM; $mm++) {
            $size = $this->sizeFor($mm);
            if ($size >= self::MIN_SIZE && $size <= self::MAX_SIZE) {
                $ranges[$size] ??= ['from' => $mm, 'to' => $mm];
                $ranges[$size]['to'] = $mm;
            }
        }

        return array_map(fn (array $range) => $range + [
            'asian' => intdiv($range['to'], self::ASIAN_STEP_MM) * self::ASIAN_STEP_MM,
        ], $ranges);
    }
}
