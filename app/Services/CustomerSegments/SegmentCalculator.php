<?php

namespace App\Services\CustomerSegments;

use App\Enums\CustomerSegment;
use App\Enums\OrderStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Scores every customer with a completed order on recency, frequency and
 * money spent, and files them into a segment.
 *
 * PERCENT_RANK rather than NTILE: NTILE splits people evenly, so of two
 * customers with one order each, one could score higher than the other. Here
 * equal figures always score the same, and the lowest figure always scores
 * lowest.
 */
class SegmentCalculator
{
    private const SCORES = 3;

    // Ranking a handful of people says nothing: the only customer of a new
    // shop would count as both its best and its most lost.
    public const MIN_CUSTOMERS = 9;

    private const NEUTRAL_SCORE = 2;

    private const CHUNK = 500;

    /**
     * @return int how many customers were scored
     */
    public function rebuild(): int
    {
        $now = now();
        $customers = $this->rankedCustomers();
        $tooFew = $customers->count() < self::MIN_CUSTOMERS;

        $rows = $customers->map(function (object $customer) use ($now, $tooFew) {
            $recency = $tooFew ? self::NEUTRAL_SCORE : $this->score((float) $customer->recency_rank);
            $frequency = $tooFew ? self::NEUTRAL_SCORE : $this->score((float) $customer->frequency_rank);
            $monetary = $tooFew ? self::NEUTRAL_SCORE : $this->score((float) $customer->monetary_rank);

            return [
                'user_id' => (int) $customer->user_id,
                'segment' => CustomerSegment::fromScores($recency, $frequency, $monetary)->value,
                'recency_score' => $recency,
                'frequency_score' => $frequency,
                'monetary_score' => $monetary,
                'last_order_on' => $customer->last_order_on,
                'order_count' => (int) $customer->order_count,
                'total_spent' => (int) $customer->total_spent,
                'computed_at' => $now,
            ];
        });

        DB::transaction(function () use ($rows) {
            DB::table('customer_segments')->delete();

            foreach ($rows->chunk(self::CHUNK) as $chunk) {
                DB::table('customer_segments')->insert($chunk->values()->all());
            }
        });

        return $rows->count();
    }

    /**
     * Money spent is what the goods cost them, without the shipping.
     *
     * @return Collection<int, object>
     */
    private function rankedCustomers()
    {
        $totals = DB::table('order as o')
            ->where('o.order_status', OrderStatus::Completed->value)
            ->groupBy('o.user_id')
            ->selectRaw('o.user_id, DATE(MAX(COALESCE(o.order_completed_at, o.updated_at))) as last_order_on')
            ->selectRaw('COUNT(*) as order_count, SUM(o.order_total - o.order_delivery_fee) as total_spent');

        return DB::query()
            ->fromSub($totals, 't')
            ->selectRaw('t.user_id, t.last_order_on, t.order_count, t.total_spent')
            ->selectRaw('PERCENT_RANK() OVER (ORDER BY t.last_order_on) as recency_rank')
            ->selectRaw('PERCENT_RANK() OVER (ORDER BY t.order_count) as frequency_rank')
            ->selectRaw('PERCENT_RANK() OVER (ORDER BY t.total_spent) as monetary_rank')
            ->get();
    }

    private function score(float $rank): int
    {
        return min(self::SCORES, 1 + (int) floor($rank * self::SCORES));
    }
}
