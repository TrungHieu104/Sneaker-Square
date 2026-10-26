<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ProductModel;
use App\Models\UserEventModel;
use App\Services\Analytics\Recommender;
use App\Services\UserEventLog;
use Illuminate\Http\Response;

/**
 * The product page's two personal blocks, fetched after the page loads: they
 * belong to one shopper, and the page itself is meant to be shareable cache.
 *
 * Every block shown is recorded, so a click on it can be weighed against how
 * often it was offered.
 */
class RecommendationController extends Controller
{
    public function __construct(
        private readonly Recommender $recommender,
        private readonly UserEventLog $events,
    ) {}

    public function show(string $proSlug): Response
    {
        $product = ProductModel::withPriceRange()
            ->with('getQuantities')
            ->where('pro_slug', $proSlug)
            ->where('pro_hidden', 1)
            ->firstOrFail();

        $similar = $this->recommender->similarTo($product);
        $recent = $this->recommender->recentlyViewed($product);

        $this->recordShown($product, Recommender::SIMILAR, $similar->pluck('product.pro_id')->all());
        $this->recordShown($product, Recommender::RECENT, $recent->pluck('pro_id')->all());

        return response()->view('frontend.pages.product.partials.recommendations', [
            'similar' => $similar,
            'recent' => $recent,
        ]);
    }

    /**
     * @param  array<int, mixed>  $productIds
     */
    private function recordShown(ProductModel $product, string $source, array $productIds): void
    {
        if ($productIds === []) {
            return;
        }

        $this->events->record(UserEventModel::RECOMMEND, [
            'pro_id' => $product->pro_id,
            'meta' => ['source' => $source, 'pro_ids' => array_map('intval', $productIds)],
        ]);
    }
}
