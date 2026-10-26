<?php

namespace App\Services\Analytics;

use App\Models\ProductModel;
use App\Models\UserEventModel as Event;
use App\Services\ShoeSizeMemory;
use App\Services\UserEventLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * What to show a shopper next to the product they are looking at.
 *
 * Recommendations are content-based: a product is suggested for what it has in
 * common with this one and with what this shopper has shown interest in, never
 * for what other shoppers bought alongside it. The shop has too few orders for
 * "bought together" to say anything.
 */
class Recommender
{
    public const SIMILAR = 'similar';

    public const RECENT = 'recent';

    // How a click on a recommendation names its block in the product URL.
    public const URL_SOURCES = ['goi-y' => self::SIMILAR, 'da-xem' => self::RECENT];

    public const LABELS = [self::SIMILAR => 'Gợi ý tương tự', self::RECENT => 'Đã xem gần đây'];

    private const LIMIT = 5;

    // A price within this many percent of the product's own counts as the same range.
    private const PRICE_BAND = 25;

    // Same category alone is enough; price, colour and size have to add up to it.
    private const MIN_SCORE = 3;

    private const CATEGORY_SCORE = 3;

    private const PRICE_SCORE = 2;

    private const INTEREST_DAYS = 90;

    private const INTEREST_COLORS = 3;

    // More than two labels wrap and make one tile taller than its neighbours.
    private const SHOWN_REASONS = 2;

    public function __construct(
        private readonly UserEventLog $events,
        private readonly ShoeSizeMemory $sizes,
    ) {}

    /**
     * @return Collection<int, ProductModel>
     */
    public function recentlyViewed(ProductModel $current): Collection
    {
        $ids = Event::query()->mine($this->events->visitorId(), $this->userId())
            ->where('event_type', Event::VIEW_PRODUCT)
            ->where('pro_id', '!=', $current->pro_id)
            ->groupBy('pro_id')
            ->selectRaw('pro_id, MAX(event_id) as last_seen')
            ->orderByDesc('last_seen')
            ->limit(self::LIMIT)
            ->pluck('pro_id')
            ->map(fn ($id) => (int) $id);

        if ($ids->isEmpty()) {
            return collect();
        }

        $products = $this->visible()->whereIn('pro_id', $ids)->get()->keyBy('pro_id');

        return $ids->map(fn (int $id) => $products->get($id))->filter()->values();
    }

    /**
     * @return Collection<int, array{product: ProductModel, reasons: list<string>}>
     */
    public function similarTo(ProductModel $current): Collection
    {
        $price = $current->priceRange()['min'];
        $colors = $this->interestingColors() ?: $current->getQuantities->pluck('color_id')->filter()->unique()->all();
        $size = $this->sizes->remembered()['size'];
        $sizeIds = $size === null ? [] : DB::table('size')->where('size', (string) $size)->pluck('size_id')->all();

        return $this->visible()
            ->with('getQuantities')
            ->where('pro_id', '!=', $current->pro_id)
            ->orderByDesc('pro_views')
            ->get()
            ->map(function (ProductModel $candidate) use ($current, $price, $colors, $size, $sizeIds) {
                [$score, $reasons] = $this->score($candidate, $current, $price, $colors, $size, $sizeIds);

                return ['product' => $candidate, 'reasons' => $reasons, 'score' => $score];
            })
            ->filter(fn (array $match) => $match['score'] >= self::MIN_SCORE)
            // Stable, so equal scores keep the most-viewed-first order of the query.
            ->sortByDesc('score')
            ->take(self::LIMIT)
            ->map(fn (array $match) => ['product' => $match['product'], 'reasons' => array_slice($match['reasons'], 0, self::SHOWN_REASONS)])
            ->values();
    }

    /**
     * @param  array<int, int|string>  $colors
     * @param  array<int, int|string>  $sizeIds
     * @return array{0: int, 1: list<string>}
     */
    private function score(ProductModel $candidate, ProductModel $current, int $price, array $colors, ?int $size, array $sizeIds): array
    {
        $score = 0;
        $reasons = [];
        $inStock = $candidate->getQuantities->where('quantity', '>', 0);

        if ($candidate->cate_id === $current->cate_id) {
            $score += self::CATEGORY_SCORE;
            $reasons[] = 'Cùng danh mục';
        }

        if ($price > 0 && abs($candidate->priceRange()['min'] - $price) * 100 <= $price * self::PRICE_BAND) {
            $score += self::PRICE_SCORE;
            $reasons[] = 'Cùng tầm giá';
        }

        if ($colors !== [] && $inStock->whereIn('color_id', $colors)->isNotEmpty()) {
            $score++;
            $reasons[] = 'Có màu bạn quan tâm';
        }

        if ($sizeIds !== [] && $inStock->whereIn('size_id', $sizeIds)->isNotEmpty()) {
            $score++;
            $reasons[] = 'Còn size '.$size;
        }

        return [$score, $reasons];
    }

    /**
     * The colours this shopper keeps picking, most picked first.
     *
     * @return array<int, int>
     */
    private function interestingColors(): array
    {
        return Event::query()->mine($this->events->visitorId(), $this->userId())
            ->whereIn('event_type', [Event::SELECT_VARIANT, Event::ADD_CART, Event::PURCHASE])
            ->whereNotNull('color_id')
            ->where('created_at', '>=', now()->subDays(self::INTEREST_DAYS))
            ->groupBy('color_id')
            ->selectRaw('color_id, COUNT(*) as picks')
            ->orderByDesc('picks')
            ->limit(self::INTEREST_COLORS)
            ->pluck('color_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function userId(): ?int
    {
        $id = Auth::id();

        return $id === null ? null : (int) $id;
    }

    /**
     * @return Builder<ProductModel>
     */
    private function visible(): Builder
    {
        return ProductModel::withRatingSummary()->withPriceRange()
            ->where('pro_hidden', 1)
            ->whereDate('pro_date', '<=', now()->toDateString());
    }
}
