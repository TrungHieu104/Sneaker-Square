<?php

namespace App\Services\Analytics;

use App\Models\UserEventModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

/**
 * Folds one day of user_events into the daily tables the behaviour
 * report reads, so the report never scans the raw log.
 *
 * A day is always rebuilt whole: its rows are deleted and written again in one
 * transaction, which makes a second run over the same day a no-op rather than
 * a double count.
 */
class EventAggregator
{
    // Events that name a product; the variant table only holds these.
    private const PRODUCT_EVENTS = [
        UserEventModel::VIEW_PRODUCT,
        UserEventModel::SELECT_VARIANT,
        UserEventModel::STOCK_OUT_HIT,
        UserEventModel::ADD_CART,
        UserEventModel::REMOVE_CART,
        UserEventModel::WISHLIST,
        UserEventModel::PURCHASE,
        UserEventModel::REVIEW,
    ];

    private const KEYWORD_LENGTH = 191;

    public function aggregate(Carbon $day): void
    {
        $from = $day->copy()->startOfDay();
        $to = $day->copy()->endOfDay();
        $date = $from->toDateString();

        DB::transaction(function () use ($from, $to, $date) {
            DB::table('event_daily_totals')->where('day', $date)->delete();
            DB::table('event_daily_variants')->where('day', $date)->delete();
            DB::table('search_daily')->where('day', $date)->delete();
            DB::table('recommendation_daily')->where('day', $date)->delete();

            $this->totals($from, $to, $date);
            $this->variants($from, $to, $date);
            $this->searches($from, $to, $date);
            $this->recommendations($from, $to, $date);
        });
    }

    private function totals(Carbon $from, Carbon $to, string $date): void
    {
        DB::table('event_daily_totals')->insertUsing(
            ['day', 'event_type', 'events', 'sessions'],
            DB::table('user_events')
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('? as day, event_type, COUNT(*) as events, COUNT(DISTINCT session_id) as sessions', [$date])
                ->groupBy('event_type'),
        );
    }

    private function variants(Carbon $from, Carbon $to, string $date): void
    {
        // A cart or purchase row may stand for several pairs; everything else
        // is one look at one product.
        $quantity = DB::getQueryGrammar()->wrap('meta->quantity');

        DB::table('event_daily_variants')->insertUsing(
            ['day', 'event_type', 'pro_id', 'size_id', 'color_id', 'events', 'quantity'],
            DB::table('user_events')
                ->whereBetween('created_at', [$from, $to])
                ->whereIn('event_type', self::PRODUCT_EVENTS)
                ->whereNotNull('pro_id')
                ->selectRaw("? as day, event_type, pro_id, size_id, color_id, COUNT(*) as events, SUM(COALESCE($quantity, 1)) as quantity", [$date])
                ->groupBy('event_type', 'pro_id', 'size_id', 'color_id'),
        );
    }

    /**
     * Keywords are grouped case-blind in PHP, since SQLite's lower() leaves
     * Vietnamese capitals alone.
     */
    private function searches(Carbon $from, Carbon $to, string $date): void
    {
        $groups = [];

        UserEventModel::query()
            ->where('event_type', UserEventModel::SEARCH)
            ->whereBetween('created_at', [$from, $to])
            ->select(['event_id', 'meta'])
            ->lazyById(1000, 'event_id')
            ->each(function (UserEventModel $event) use (&$groups, $date) {
                $keyword = mb_substr(mb_strtolower(trim((string) ($event->meta['keyword'] ?? ''))), 0, self::KEYWORD_LENGTH);

                if ($keyword === '') {
                    return;
                }

                $groups[$keyword] ??= ['day' => $date, 'keyword' => $keyword, 'searches' => 0, 'misses' => 0];
                $groups[$keyword]['searches']++;
                $groups[$keyword]['misses'] += (int) (($event->meta['results'] ?? null) === 0);
            });

        foreach (array_chunk(array_values($groups), 500) as $chunk) {
            DB::table('search_daily')->insert($chunk);
        }
    }

    /**
     * How often each recommendation block was offered, clicked, and followed
     * by the clicked product going into the cart or being bought in the same
     * visit on the same day.
     */
    private function recommendations(Carbon $from, Carbon $to, string $date): void
    {
        $rows = [];
        $row = function (string $source) use (&$rows, $date): string {
            $rows[$source] ??= ['day' => $date, 'source' => $source, 'shown' => 0, 'clicks' => 0, 'carts' => 0, 'purchases' => 0];

            return $source;
        };

        $this->dayOf(UserEventModel::RECOMMEND, $from, $to)
            ->each(function (UserEventModel $event) use (&$rows, $row) {
                $rows[$row((string) ($event->meta['source'] ?? ''))]['shown'] += count($event->meta['pro_ids'] ?? []);
            });

        $clicks = [];
        $this->dayOf(UserEventModel::VIEW_PRODUCT, $from, $to)
            ->filter(fn (UserEventModel $event) => isset($event->meta['source']))
            ->each(function (UserEventModel $event) use (&$clicks) {
                // One click per product and block in a visit, the first one.
                $clicks[$event->session_id.'|'.$event->pro_id.'|'.$event->meta['source']] ??= $event;
            });

        $followUps = $this->followUps(array_unique(array_map(fn (UserEventModel $click) => $click->session_id, $clicks)), $from, $to);

        foreach ($clicks as $click) {
            $key = $row((string) $click->meta['source']);
            $rows[$key]['clicks']++;
            $after = $followUps[$click->session_id.'|'.$click->pro_id] ?? [];
            $rows[$key]['carts'] += (int) $this->happenedAfter($after[UserEventModel::ADD_CART] ?? [], $click->event_id);
            $rows[$key]['purchases'] += (int) $this->happenedAfter($after[UserEventModel::PURCHASE] ?? [], $click->event_id);
        }

        if ($rows !== []) {
            DB::table('recommendation_daily')->insert(array_values($rows));
        }
    }

    /**
     * @return LazyCollection<int, UserEventModel>
     */
    private function dayOf(string $type, Carbon $from, Carbon $to): LazyCollection
    {
        return UserEventModel::query()
            ->where('event_type', $type)
            ->whereBetween('created_at', [$from, $to])
            ->select(['event_id', 'session_id', 'pro_id', 'meta'])
            ->lazyById(1000, 'event_id');
    }

    /**
     * Cart and purchase event ids of the given visits, by "session|product".
     *
     * @param  array<int, string>  $sessions
     * @return array<string, array<string, list<int>>>
     */
    private function followUps(array $sessions, Carbon $from, Carbon $to): array
    {
        $found = [];

        foreach (array_chunk($sessions, 500) as $chunk) {
            UserEventModel::query()
                ->whereIn('event_type', [UserEventModel::ADD_CART, UserEventModel::PURCHASE])
                ->whereIn('session_id', $chunk)
                ->whereBetween('created_at', [$from, $to])
                ->get(['event_id', 'session_id', 'pro_id', 'event_type'])
                ->each(function (UserEventModel $event) use (&$found) {
                    $found[$event->session_id.'|'.$event->pro_id][$event->event_type][] = $event->event_id;
                });
        }

        return $found;
    }

    /**
     * @param  list<int>  $eventIds
     */
    private function happenedAfter(array $eventIds, int $clickId): bool
    {
        foreach ($eventIds as $id) {
            if ($id > $clickId) {
                return true;
            }
        }

        return false;
    }
}
