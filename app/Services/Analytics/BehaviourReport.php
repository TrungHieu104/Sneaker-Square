<?php

namespace App\Services\Analytics;

use App\Models\CartReminderModel;
use App\Models\UserEventModel as Event;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The figures on the behaviour page, read from the daily tables that
 * EventAggregator fills.
 */
class BehaviourReport
{
    public const FUNNEL = [
        Event::PAGE_VIEW => 'Phiên truy cập',
        Event::VIEW_PRODUCT => 'Xem sản phẩm',
        Event::ADD_CART => 'Thêm vào giỏ',
        Event::CHECKOUT_START => 'Bắt đầu thanh toán',
        Event::PLACE_ORDER => 'Đặt hàng',
        Event::PURCHASE => 'Mua thành công',
    ];

    public const RESTOCK_NOW = 'Nhập ngay';

    public const NEVER_STOCKED = 'Chưa có biến thể';

    public const RUNNING_LOW = 'Sắp thiếu';

    // Below this many views a product's conversion rate is noise.
    private const MIN_VIEWS = 10;

    private const LIMIT = 20;

    private const VIEWS = 'views';

    // An order this soon after a reminder is put down to the reminder.
    public const ATTRIBUTION_DAYS = 7;

    public function __construct(
        private readonly Carbon $from,
        private readonly Carbon $to,
    ) {}

    public static function lastDays(int $days): self
    {
        return new self(Carbon::today()->subDays($days), Carbon::yesterday());
    }

    public function from(): Carbon
    {
        return $this->from;
    }

    public function to(): Carbon
    {
        return $this->to;
    }

    /**
     * Sessions that reached each step. Summed per day, so a shopper who comes
     * back on another day counts once for each day.
     *
     * @return list<array{type: string, label: string, sessions: int, fromPrevious: ?float, fromTop: ?float}>
     */
    public function funnel(): array
    {
        $sessions = $this->scoped('event_daily_totals')
            ->whereIn('event_type', array_keys(self::FUNNEL))
            ->groupBy('event_type')
            ->selectRaw('event_type, SUM(sessions) as sessions')
            ->pluck('sessions', 'event_type');

        $steps = [];
        $top = null;
        $previous = null;

        foreach (self::FUNNEL as $type => $label) {
            $count = (int) ($sessions[$type] ?? 0);
            $top ??= $count;

            $steps[] = [
                'type' => $type,
                'label' => $label,
                'sessions' => $count,
                'fromPrevious' => $previous === null ? null : $this->rate($count, $previous),
                'fromTop' => $this->rate($count, $top),
            ];
            $previous = $count;
        }

        return $steps;
    }

    /**
     * The step that loses the most sessions, counted in sessions rather than
     * percent: a 100% drop from one cart is not where the shop leaks.
     *
     * @param  list<array{type: string, label: string, sessions: int, fromPrevious: ?float, fromTop: ?float}>  $funnel
     * @return array{from: string, to: string, sessions: int, rate: float}|null
     */
    public static function biggestDrop(array $funnel): ?array
    {
        $drop = null;

        for ($i = 1; $i < count($funnel); $i++) {
            $lost = $funnel[$i - 1]['sessions'] - $funnel[$i]['sessions'];

            if ($lost > 0 && $lost > ($drop['sessions'] ?? 0)) {
                $drop = [
                    'from' => $funnel[$i - 1]['label'],
                    'to' => $funnel[$i]['label'],
                    'sessions' => $lost,
                    'rate' => round($lost / $funnel[$i - 1]['sessions'] * 100, 1),
                ];
            }
        }

        return $drop;
    }

    /**
     * What shoppers looked for and the shop does not sell.
     *
     * @return Collection<int, object{keyword: string, misses: int, searches: int}>
     */
    public function missedSearches(): Collection
    {
        /** @var Collection<int, object{keyword: string, misses: int, searches: int}> */
        return $this->scoped('search_daily')
            ->groupBy('keyword')
            ->selectRaw('keyword, SUM(misses) as misses, SUM(searches) as searches')
            ->havingRaw('SUM(misses) > 0')
            ->orderByDesc('misses')
            ->orderBy('keyword')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (object $row) => (object) [
                'keyword' => (string) $row->keyword,
                'misses' => (int) $row->misses,
                'searches' => (int) $row->searches,
            ]);
    }

    /**
     * Products many people open and few buy, worst conversion first: the
     * place to look for a wrong price, a poor photo or a thin description.
     *
     * @return Collection<int, object{pro_id: int, pro_name: string, pro_img: string, views: int, carts: int, purchases: int, conversion: float}>
     */
    public function viewedNotBought(): Collection
    {
        $rows = $this->scoped('event_daily_variants as v')
            ->join('products as p', 'p.pro_id', '=', 'v.pro_id')
            ->whereNull('p.deleted_at')
            ->groupBy('v.pro_id', 'p.pro_name', 'p.pro_img')
            ->selectRaw('v.pro_id, p.pro_name, p.pro_img')
            ->selectRaw('SUM(CASE WHEN v.event_type = ? THEN v.events ELSE 0 END) as views', [Event::VIEW_PRODUCT])
            ->selectRaw('SUM(CASE WHEN v.event_type = ? THEN v.events ELSE 0 END) as carts', [Event::ADD_CART])
            ->selectRaw('SUM(CASE WHEN v.event_type = ? THEN v.events ELSE 0 END) as purchases', [Event::PURCHASE])
            ->havingRaw('SUM(CASE WHEN v.event_type = ? THEN v.events ELSE 0 END) >= ?', [Event::VIEW_PRODUCT, self::MIN_VIEWS])
            ->get();

        return $rows
            ->map(fn (object $row) => (object) [
                'pro_id' => (int) $row->pro_id,
                'pro_name' => (string) $row->pro_name,
                'pro_img' => (string) $row->pro_img,
                self::VIEWS => (int) $row->views,
                'carts' => (int) $row->carts,
                'purchases' => (int) $row->purchases,
                'conversion' => (float) $this->rate((int) $row->purchases, (int) $row->views),
            ])
            ->sortBy([['conversion', 'asc'], [self::VIEWS, 'desc']])
            ->take(self::LIMIT)
            ->values();
    }

    /**
     * Size and colour pairs that shoppers asked for, set against what is on
     * the shelf: a pair people keep reaching for while it is out of stock is
     * the strongest reason to reorder.
     *
     * @return Collection<int, object{pro_name: string, size: ?string, color: ?string, selects: int, carts: int, hits: int, sold: int, stock: ?int, advice: string}>
     */
    public function restock(): Collection
    {
        $stock = DB::table('products_quantity')
            ->groupBy('pro_id', 'size_id', 'color_id')
            ->selectRaw('pro_id, size_id, color_id, SUM(quantity) as on_hand');

        $rows = $this->scoped('event_daily_variants as v')
            ->join('products as p', 'p.pro_id', '=', 'v.pro_id')
            ->leftJoin('size as s', 's.size_id', '=', 'v.size_id')
            ->leftJoin('color as c', 'c.color_id', '=', 'v.color_id')
            ->leftJoinSub($stock, 'q', function ($join) {
                $join->on('q.pro_id', '=', 'v.pro_id')
                    ->on('q.size_id', '=', 'v.size_id')
                    ->on('q.color_id', '=', 'v.color_id');
            })
            ->whereNull('p.deleted_at')
            ->whereNotNull('v.size_id')
            ->whereNotNull('v.color_id')
            ->whereIn('v.event_type', [Event::SELECT_VARIANT, Event::ADD_CART, Event::STOCK_OUT_HIT, Event::PURCHASE])
            ->groupBy('v.pro_id', 'v.size_id', 'v.color_id', 'p.pro_name', 's.size', 'c.color_vn', 'q.on_hand')
            ->selectRaw('p.pro_name, s.size, c.color_vn as color, q.on_hand as stock')
            ->selectRaw('SUM(CASE WHEN v.event_type = ? THEN v.events ELSE 0 END) as selects', [Event::SELECT_VARIANT])
            ->selectRaw('SUM(CASE WHEN v.event_type = ? THEN v.quantity ELSE 0 END) as carts', [Event::ADD_CART])
            ->selectRaw('SUM(CASE WHEN v.event_type = ? THEN v.events ELSE 0 END) as hits', [Event::STOCK_OUT_HIT])
            ->selectRaw('SUM(CASE WHEN v.event_type = ? THEN v.quantity ELSE 0 END) as sold', [Event::PURCHASE])
            ->get();

        return $rows
            ->map(function (object $row) {
                $stock = $row->stock === null ? null : (int) $row->stock;
                $hits = (int) $row->hits;
                $sold = (int) $row->sold;

                return (object) [
                    'pro_name' => (string) $row->pro_name,
                    'size' => $row->size === null ? null : (string) $row->size,
                    'color' => $row->color === null ? null : (string) $row->color,
                    'selects' => (int) $row->selects,
                    'carts' => (int) $row->carts,
                    'hits' => $hits,
                    'sold' => $sold,
                    'stock' => $stock,
                    'advice' => $this->advice($stock, $hits, $sold),
                ];
            })
            ->filter(fn (object $row) => $row->advice !== '')
            ->sortBy([['hits', 'desc'], ['carts', 'desc'], ['sold', 'desc']])
            ->take(self::LIMIT)
            ->values();
    }

    /**
     * Each recommendation block: how often it was offered, clicked, and how
     * often the click ended in the cart or in a purchase.
     *
     * @return list<array{source: string, label: string, shown: int, clicks: int, ctr: ?float, carts: int, cartRate: ?float, purchases: int, purchaseRate: ?float}>
     */
    public function recommendations(): array
    {
        $totals = $this->scoped('recommendation_daily')
            ->groupBy('source')
            ->selectRaw('source, SUM(shown) as shown, SUM(clicks) as clicks, SUM(carts) as carts, SUM(purchases) as purchases')
            ->get()
            ->keyBy('source');

        $blocks = [];

        foreach (Recommender::LABELS as $source => $label) {
            $row = $totals->get($source);
            $shown = (int) ($row->shown ?? 0);
            $clicks = (int) ($row->clicks ?? 0);
            $carts = (int) ($row->carts ?? 0);
            $purchases = (int) ($row->purchases ?? 0);

            $blocks[] = [
                'source' => $source,
                'label' => $label,
                'shown' => $shown,
                'clicks' => $clicks,
                'ctr' => $this->rate($clicks, $shown),
                'carts' => $carts,
                'cartRate' => $this->rate($carts, $clicks),
                'purchases' => $purchases,
                'purchaseRate' => $this->rate($purchases, $clicks),
            ];
        }

        return $blocks;
    }

    /**
     * Each reminder step: sent, opened, clicked, and the carts it won back.
     *
     * A cart counts as won back when, within ATTRIBUTION_DAYS of clicking the
     * email's button, the account bought something that was in the reminded
     * cart. Orders that came some other way are not claimed, so the figure is
     * a floor.
     *
     * @return list<array{step: int, sent: int, opened: int, openRate: ?float, clicked: int, clickRate: ?float, orders: int, orderRate: ?float, value: int}>
     */
    public function cartReminders(): array
    {
        $reminders = CartReminderModel::query()
            ->whereBetween('sent_at', [$this->from->copy()->startOfDay(), $this->to->copy()->endOfDay()])
            ->orderBy('clicked_at')
            ->get(['id', 'user_id', 'step', 'lines', 'sent_at', 'opened_at', 'clicked_at']);

        $recovered = $this->recoveredOrders($reminders->whereNotNull('clicked_at'));
        $steps = [];

        foreach ([CartReminderModel::FIRST, CartReminderModel::SECOND, CartReminderModel::OFFER] as $step) {
            $ofStep = $reminders->where('step', $step);
            $sent = $ofStep->count();
            $opened = $ofStep->whereNotNull('opened_at')->count();
            $clicked = $ofStep->whereNotNull('clicked_at')->count();
            $orders = $ofStep->sum(fn (CartReminderModel $reminder) => count($recovered[$reminder->id] ?? []));

            $steps[] = [
                'step' => $step,
                'sent' => $sent,
                'opened' => $opened,
                'openRate' => $this->rate($opened, $sent),
                'clicked' => $clicked,
                'clickRate' => $this->rate($clicked, $sent),
                'orders' => $orders,
                'orderRate' => $this->rate($orders, $sent),
                'value' => $ofStep->sum(fn (CartReminderModel $reminder) => array_sum($recovered[$reminder->id] ?? [])),
            ];
        }

        return $steps;
    }

    /**
     * Purchases of reminded products after a click, grouped into orders and
     * credited to the latest clicked reminder before each one.
     *
     * @param  Collection<int, CartReminderModel>  $clicked
     * @return array<int, array<string, int>> reminder id => order => value of the reminded products in it
     */
    private function recoveredOrders(Collection $clicked): array
    {
        if ($clicked->isEmpty()) {
            return [];
        }

        $byUser = $clicked->groupBy('user_id');
        $purchases = Event::query()
            ->where('event_type', Event::PURCHASE)
            ->whereIn('user_id', $byUser->keys())
            ->whereBetween('created_at', [$clicked->min('clicked_at'), $clicked->max('clicked_at')->copy()->addDays(self::ATTRIBUTION_DAYS)])
            ->get(['user_id', 'session_id', 'order_id', 'pro_id', 'meta', 'created_at']);
        $recovered = [];

        foreach ($purchases as $line) {
            $reminder = $byUser->get($line->user_id)
                ?->filter(fn (CartReminderModel $sent) => $sent->clicked_at->lte($line->created_at)
                    && $sent->clicked_at->gte($line->created_at->copy()->subDays(self::ATTRIBUTION_DAYS)))
                ->last();

            if ($reminder === null || ! in_array($line->pro_id, array_column($reminder->lines, 'pro_id'))) {
                continue;
            }

            // Sample data buys without an order behind it; its visit stands in.
            $order = (string) ($line->order_id ?? $line->session_id);
            $recovered[$reminder->id][$order] = ($recovered[$reminder->id][$order] ?? 0)
                + (int) ($line->meta['quantity'] ?? 1) * (int) ($line->meta['price'] ?? 0);
        }

        return $recovered;
    }

    /**
     * Out of stock and still wanted, never stocked at all, or selling faster
     * than what is left would last for another period of the same length.
     */
    private function advice(?int $stock, int $hits, int $sold): string
    {
        return match (true) {
            $stock === null && $hits > 0 => self::NEVER_STOCKED,
            $stock !== null && $stock <= 0 && $hits > 0 => self::RESTOCK_NOW,
            $stock !== null && $stock > 0 && $sold > 0 && $stock <= $sold => self::RUNNING_LOW,
            default => '',
        };
    }

    private function scoped(string $table): Builder
    {
        $column = str_contains($table, ' as ') ? explode(' as ', $table)[1].'.' : '';

        return DB::table($table)->whereBetween($column.'day', [$this->from->toDateString(), $this->to->toDateString()]);
    }

    private function rate(int $part, int $whole): ?float
    {
        return $whole === 0 ? null : round($part * 100 / $whole, 1);
    }
}
