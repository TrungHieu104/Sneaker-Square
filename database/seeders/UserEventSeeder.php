<?php

namespace Database\Seeders;

use App\Models\UserEventModel as Event;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Simulated shopping sessions, for the analytics to have something to show
 * before the shop has real visitors. Every row is flagged is_simulated, and any
 * figure built on them has to say so.
 *
 * The sessions walk the real catalogue: products are picked in proportion to
 * their view counts, each shopper has one foot size, and a pair the shop never
 * stocked turns into a stock_out_hit just as it would on the page. Nothing here
 * writes an order or touches stock.
 */
class UserEventSeeder extends Seeder
{
    private const SESSIONS = 2000;

    private const DAYS = 30;

    // Fixed so the demo numbers do not move between two runs.
    private const RANDOM_SEED = 20261013;

    private const LOGGED_IN_RATE = 35;

    private const SEARCH_RATE = 35;

    private const MISSED_SEARCH_RATE = 25;

    private const FILTER_RATE = 25;

    private const SELECT_RATE = 60;

    private const ADD_CART_RATE = 15;

    private const REMOVE_CART_RATE = 15;

    private const WISHLIST_RATE = 6;

    private const CHECKOUT_RATE = 50;

    private const PURCHASE_RATE = 55;

    private const FOOT_SIZE_MEAN = 41.0;

    private const FOOT_SIZE_SPREAD = 1.8;

    private const CHUNK = 500;

    // Searched for, and not sold here.
    private const MISSED_KEYWORDS = [
        'yeezy 350', 'jordan 1 low', 'crocs', 'dép quai ngang', 'giày cao gót',
        'vans old skool', 'birkenstock', 'onitsuka tiger', 'giày da nam', 'balenciaga',
    ];

    private const SORTS = ['gia-tang', 'gia-giam', 'moi-nhat', 'a-z'];

    private const REFERRERS = ['', '', '', 'https://www.google.com/', 'https://www.facebook.com/', 'https://www.tiktok.com/'];

    /** @var Collection<int, object> */
    private Collection $products;

    /** @var array<int, array<string, int>> pro_id => "color-size" => quantity */
    private array $stock = [];

    /** @var array<int, int> size_id => EU size */
    private array $sizes = [];

    /** @var array<int, int> */
    private array $customers = [];

    private int $totalViews = 0;

    /** @var array<int, array<string, mixed>> */
    private array $rows = [];

    /** @var array<string, mixed> the session being written */
    private array $session = [];

    private Carbon $clock;

    public function run(): void
    {
        mt_srand(self::RANDOM_SEED);
        $this->loadCatalogue();

        DB::table('user_events')->where('is_simulated', true)->delete();

        if ($this->products->isEmpty()) {
            return;
        }

        for ($i = 0; $i < self::SESSIONS; $i++) {
            $this->simulateSession();

            if (count($this->rows) >= self::CHUNK) {
                $this->flush();
            }
        }

        $this->flush();
    }

    private function loadCatalogue(): void
    {
        $this->products = DB::table('products')
            ->where('pro_hidden', 1)
            ->whereNull('deleted_at')
            ->get(['pro_id', 'pro_slug', 'pro_name', 'pro_price', 'pro_views']);

        foreach (DB::table('products_quantity')->get(['pro_id', 'color_id', 'size_id', 'quantity']) as $variant) {
            $this->stock[$variant->pro_id][$variant->color_id.'-'.$variant->size_id] = (int) $variant->quantity;
        }

        $this->sizes = DB::table('size')->pluck('size', 'size_id')->map(fn ($size) => (int) $size)->all();
        $this->totalViews = (int) $this->products->sum(fn ($product) => $product->pro_views + 1);
        $this->customers = DB::table('users')->where('user_role', 0)->pluck('user_id')->all();
    }

    private function simulateSession(): void
    {
        $this->clock = $this->sessionStart();
        $this->session = [
            'id' => $this->randomId(),
            'user_id' => $this->customers !== [] && $this->chance(self::LOGGED_IN_RATE) ? $this->pick($this->customers) : null,
            'device' => $this->weighted(['mobile' => 62, 'desktop' => 32, 'tablet' => 6]),
            'ip' => '113.'.mt_rand(160, 191).'.'.mt_rand(0, 255).'.'.mt_rand(1, 254),
            'foot' => $this->footSize(),
            'cart' => [],
            'url' => null,
        ];

        $this->pageView(route('home.page'), 'home.page', $this->pick(self::REFERRERS) ?: null);
        $this->browse();

        for ($n = $this->weighted([1 => 45, 2 => 30, 3 => 15, 4 => 10]); $n > 0; $n--) {
            $this->viewProduct($this->popularProduct());
        }

        $this->leaveCart();
    }

    private function browse(): void
    {
        if ($this->chance(self::SEARCH_RATE)) {
            $missed = $this->chance(self::MISSED_SEARCH_RATE);
            $keyword = $missed ? $this->pick(self::MISSED_KEYWORDS) : $this->keywordFrom($this->popularProduct()->pro_name);

            $this->pageView(route('search.frontend', ['keyword' => $keyword]), 'search.frontend');
            $this->event(Event::SEARCH, ['meta' => ['keyword' => $keyword, 'results' => $missed ? 0 : mt_rand(1, 12)]]);
        }

        if ($this->chance(self::FILTER_RATE)) {
            $sort = $this->pick(self::SORTS);

            $this->pageView(route('product.page', ['sort' => $sort]), 'product.page');
            $this->event(Event::FILTER, ['meta' => ['sort' => $sort, 'category' => null]]);
        }
    }

    private function viewProduct(object $product): void
    {
        $this->pageView(route('product.detail', $product->pro_slug), 'product.detail');
        $this->event(Event::VIEW_PRODUCT, ['pro_id' => $product->pro_id]);

        if ($this->session['user_id'] && $this->chance(self::WISHLIST_RATE)) {
            $this->event(Event::WISHLIST, ['pro_id' => $product->pro_id, 'meta' => ['action' => 'add']]);
        }

        if (! $this->chance(self::SELECT_RATE)) {
            return;
        }

        $variant = $this->variantFor($product);

        if ($variant === null) {
            return;
        }

        [$colorId, $sizeId, $inStock] = $variant;
        $pair = ['pro_id' => $product->pro_id, 'color_id' => $colorId, 'size_id' => $sizeId];

        if (! $inStock) {
            $this->event(Event::STOCK_OUT_HIT, $pair);

            return;
        }

        $this->event(Event::SELECT_VARIANT, $pair);

        if ($this->chance(self::ADD_CART_RATE)) {
            $quantity = $this->weighted([1 => 85, 2 => 15]);
            $this->event(Event::ADD_CART, $pair + ['meta' => ['quantity' => $quantity, 'price' => (int) $product->pro_price]]);
            $this->session['cart'][] = $pair + ['quantity' => $quantity, 'price' => (int) $product->pro_price];
        }
    }

    /**
     * The pair this shopper reaches for: their own size in one of the colours
     * the product comes in, whether or not the shop has that pair.
     *
     * @return ?array{0: int, 1: int, 2: bool}
     */
    private function variantFor(object $product): ?array
    {
        $variants = $this->stock[$product->pro_id] ?? [];
        $sizeId = array_search($this->session['foot'], $this->sizes, true);

        if ($variants === [] || $sizeId === false) {
            return null;
        }

        $colors = array_values(array_unique(array_map(fn ($key) => (int) explode('-', $key)[0], array_keys($variants))));
        $colorId = $this->pick($colors);

        return [$colorId, (int) $sizeId, ($variants[$colorId.'-'.$sizeId] ?? 0) > 0];
    }

    private function leaveCart(): void
    {
        $cart = $this->session['cart'];

        if ($cart === []) {
            return;
        }

        foreach ($cart as $index => $line) {
            if ($this->chance(self::REMOVE_CART_RATE)) {
                $this->event(Event::REMOVE_CART, $this->pairOf($line) + ['meta' => ['quantity' => $line['quantity'], 'emptied_cart' => false]]);
                unset($cart[$index]);
            }
        }

        if ($cart === [] || $this->customers === [] || ! $this->chance(self::CHECKOUT_RATE)) {
            return;
        }

        $this->checkout(array_values($cart));
    }

    /**
     * @param  array<int, array<string, int>>  $cart
     */
    private function checkout(array $cart): void
    {
        // Checkout asks a guest to log in, and the login claims the session.
        if ($this->session['user_id'] === null) {
            $this->session['user_id'] = $this->pick($this->customers);
            $this->claimSession();
        }

        $value = array_sum(array_map(fn ($line) => $line['price'] * $line['quantity'], $cart));

        $this->pageView(route('product.checkout'), 'product.checkout');
        $this->event(Event::CHECKOUT_START, ['meta' => ['lines' => count($cart), 'value' => $value]]);

        if (! $this->chance(self::PURCHASE_RATE)) {
            return;
        }

        $this->event(Event::PLACE_ORDER, ['meta' => ['payment' => $this->weighted(['cod' => 70, 'payUrl' => 20, 'wallet' => 10]), 'total' => $value]]);

        foreach ($cart as $line) {
            $this->event(Event::PURCHASE, $this->pairOf($line) + ['meta' => ['quantity' => $line['quantity'], 'price' => $line['price']]]);
        }

        $this->pageView(route('success.checkout'), 'success.checkout');
    }

    private function claimSession(): void
    {
        foreach ($this->rows as $index => $row) {
            if ($row['session_id'] === $this->session['id']) {
                $this->rows[$index]['user_id'] = $this->session['user_id'];
            }
        }
    }

    private function pageView(string $url, string $route, ?string $referrer = null): void
    {
        $this->session['url'] = $url;
        $this->event(Event::PAGE_VIEW, ['url' => $url, 'referrer' => $referrer, 'meta' => ['route' => $route]]);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function event(string $type, array $fields = []): void
    {
        $this->clock = $this->clock->copy()->addSeconds(mt_rand(8, 120));

        $this->rows[] = [
            'session_id' => $this->session['id'],
            'user_id' => $this->session['user_id'],
            'event_type' => $type,
            'pro_id' => $fields['pro_id'] ?? null,
            'size_id' => $fields['size_id'] ?? null,
            'color_id' => $fields['color_id'] ?? null,
            'order_id' => null,
            'meta' => isset($fields['meta']) ? json_encode($fields['meta'], JSON_UNESCAPED_UNICODE) : null,
            'url' => $this->session['url'],
            'referrer' => $fields['referrer'] ?? null,
            'device' => $this->session['device'],
            'ip' => $this->session['ip'],
            'is_simulated' => true,
            'created_at' => $this->clock,
        ];
    }

    private function flush(): void
    {
        if ($this->rows !== []) {
            DB::table('user_events')->insert($this->rows);
            $this->rows = [];
        }
    }

    /**
     * @param  array<string, int>  $line
     * @return array{pro_id: int, color_id: int, size_id: int}
     */
    private function pairOf(array $line): array
    {
        return ['pro_id' => $line['pro_id'], 'color_id' => $line['color_id'], 'size_id' => $line['size_id']];
    }

    /**
     * Busier in the evening, as a shop selling to people with day jobs is.
     */
    private function sessionStart(): Carbon
    {
        $hour = $this->weighted([8 => 3, 10 => 5, 12 => 9, 14 => 6, 16 => 6, 19 => 12, 21 => 14, 23 => 5]);

        $start = Carbon::now()->subDays(mt_rand(0, self::DAYS - 1))->setTime($hour, mt_rand(0, 59), mt_rand(0, 59));

        return $start->isFuture() ? $start->subDay() : $start;
    }

    private function popularProduct(): object
    {
        $roll = mt_rand(1, $this->totalViews);

        foreach ($this->products as $product) {
            $roll -= $product->pro_views + 1;

            if ($roll <= 0) {
                return $product;
            }
        }

        return $this->products->last();
    }

    /**
     * Roughly normal around the mean, by adding up uniform draws.
     */
    private function footSize(): int
    {
        $sum = 0.0;
        for ($i = 0; $i < 6; $i++) {
            $sum += mt_rand() / mt_getrandmax();
        }

        return (int) round(self::FOOT_SIZE_MEAN + ($sum - 3) * self::FOOT_SIZE_SPREAD * sqrt(2));
    }

    private function keywordFrom(string $name): string
    {
        $words = explode(' ', mb_strtolower($name));

        return implode(' ', array_slice($words, 0, min(2, count($words))));
    }

    private function randomId(): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $id = '';
        for ($i = 0; $i < 40; $i++) {
            $id .= $alphabet[mt_rand(0, 61)];
        }

        return $id;
    }

    private function chance(int $percent): bool
    {
        return mt_rand(1, 100) <= $percent;
    }

    /**
     * @template T
     *
     * @param  array<int, T>  $items
     * @return T
     */
    private function pick(array $items): mixed
    {
        return $items[mt_rand(0, count($items) - 1)];
    }

    /**
     * @param  array<int|string, int>  $weights  outcome => weight
     */
    private function weighted(array $weights): int|string
    {
        $roll = mt_rand(1, array_sum($weights));

        foreach ($weights as $outcome => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $outcome;
            }
        }

        return array_key_first($weights);
    }
}
