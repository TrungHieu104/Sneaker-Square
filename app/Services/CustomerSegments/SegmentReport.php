<?php

namespace App\Services\CustomerSegments;

use App\Enums\CustomerSegment;
use App\Enums\OrderStatus;
use App\Models\CampaignModel;
use App\Models\CustomerSegmentModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What the admin reads about the segments, from the table the nightly run
 * fills.
 */
class SegmentReport
{
    private const MEMBERS_PER_PAGE = 20;

    private const TOTAL_SPENT = 'total_spent';

    private const RECENT_CAMPAIGNS = 10;

    // Orders that came to nothing say nothing for a campaign.
    private const UNCOUNTED_ORDERS = [OrderStatus::Cancelled, OrderStatus::Returned];

    /**
     * @return list<array{segment: CustomerSegment, customers: int, revenue: int, averageSpent: int, averageDaysSince: int}>
     */
    public function overview(): array
    {
        $rows = CustomerSegmentModel::get(['segment', self::TOTAL_SPENT, 'last_order_on'])->groupBy(fn (CustomerSegmentModel $row) => $row->segment->value);
        $today = now()->startOfDay();
        $groups = [];

        foreach (CustomerSegment::cases() as $segment) {
            $members = $rows->get($segment->value, collect());
            $customers = $members->count();

            $groups[] = [
                'segment' => $segment,
                'customers' => $customers,
                'revenue' => (int) $members->sum(self::TOTAL_SPENT),
                'averageSpent' => $customers > 0 ? (int) round($members->sum(self::TOTAL_SPENT) / $customers) : 0,
                'averageDaysSince' => $customers > 0
                    ? (int) round($members->avg(fn (CustomerSegmentModel $row) => $row->last_order_on->diffInDays($today)))
                    : 0,
            ];
        }

        return $groups;
    }

    /**
     * Where the top and bottom scores begin today, in figures an admin would
     * recognise. Null when nobody holds that score: every customer of a small
     * shop gets the neutral one, and equal figures can leave a level empty.
     *
     * @return array{recencyHigh: ?int, recencyLow: ?int, frequencyHigh: ?int, frequencyLow: ?int, monetaryHigh: ?int, monetaryLow: ?int}
     */
    public function levels(): array
    {
        $rows = CustomerSegmentModel::get();
        $today = now()->startOfDay();
        $daysSince = fn (CustomerSegmentModel $row) => (int) $row->last_order_on->diffInDays($today);

        return [
            'recencyHigh' => $rows->where('recency_score', CustomerSegment::TOP_SCORE)->max($daysSince),
            'recencyLow' => $rows->where('recency_score', CustomerSegment::LOW_SCORE)->min($daysSince),
            'frequencyHigh' => $rows->where('frequency_score', CustomerSegment::TOP_SCORE)->min('order_count'),
            'frequencyLow' => $rows->where('frequency_score', CustomerSegment::LOW_SCORE)->max('order_count'),
            'monetaryHigh' => $rows->where('monetary_score', CustomerSegment::TOP_SCORE)->min(self::TOTAL_SPENT),
            'monetaryLow' => $rows->where('monetary_score', CustomerSegment::LOW_SCORE)->max(self::TOTAL_SPENT),
        ];
    }

    /**
     * @param  Collection<int, int>  $userIds
     * @return Collection<int, CustomerSegmentModel>
     */
    public function figuresOf(Collection $userIds): Collection
    {
        return CustomerSegmentModel::with('user')
            ->whereIn('user_id', $userIds)
            ->orderByDesc(self::TOTAL_SPENT)
            ->get();
    }

    public function memberCount(CustomerSegment $segment): int
    {
        return CustomerSegmentModel::where('segment', $segment->value)->count();
    }

    /**
     * @return LengthAwarePaginator<int, CustomerSegmentModel>
     */
    public function members(CustomerSegment $segment): LengthAwarePaginator
    {
        return CustomerSegmentModel::with('user')
            ->where('segment', $segment->value)
            ->orderByDesc(self::TOTAL_SPENT)
            ->paginate(self::MEMBERS_PER_PAGE);
    }

    /**
     * An order counts for a campaign when it used a coupon the campaign gave
     * out; each coupon went to one customer, so nothing has to be guessed.
     *
     * @return Collection<int, CampaignModel>
     */
    public function campaigns(): Collection
    {
        $campaigns = CampaignModel::query()
            ->withCount([
                'recipients as sent',
                'recipients as opened' => fn ($recipients) => $recipients->whereNotNull('opened_at'),
                'recipients as clicked' => fn ($recipients) => $recipients->whereNotNull('clicked_at'),
            ])
            ->latest('id')
            ->limit(self::RECENT_CAMPAIGNS)
            ->get();

        $orders = DB::table('campaign_recipients as r')
            ->join('order as o', 'o.coupon_id', '=', 'r.coupon_id')
            ->whereIn('r.campaign_id', $campaigns->pluck('id'))
            ->whereNotIn('o.order_status', array_map(fn (OrderStatus $status) => $status->value, self::UNCOUNTED_ORDERS))
            ->groupBy('r.campaign_id')
            ->selectRaw('r.campaign_id, COUNT(*) as orders, SUM(o.order_total - o.order_delivery_fee) as revenue')
            ->get()
            ->keyBy('campaign_id');

        return $campaigns->each(function (CampaignModel $campaign) use ($orders) {
            $campaign->setAttribute('orders', (int) ($orders[$campaign->id]->orders ?? 0));
            $campaign->setAttribute('revenue', (int) ($orders[$campaign->id]->revenue ?? 0));
        });
    }
}
