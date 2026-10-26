<?php

namespace Database\Seeders;

use App\Models\CartReminderModel;
use App\Models\UserEventModel as Event;
use App\Services\CartReminders\AbandonedCarts;
use App\Services\CartReminders\CartLines;
use App\Services\SampleData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Sample shopping sessions, for the analytics to have something to show on a
 * local copy. Run through `php artisan sample:seed`, taken out again with
 * `php artisan sample:clear`; SampleData says how the rows are recognised.
 *
 * The shoppers who log in are sample accounts of their own, never the shop's
 * real customers, and their mail goes to a domain that cannot receive it.
 *
 * The sessions walk the real catalogue: products are picked in proportion to
 * their view counts, each shopper has one foot size, and a pair the shop never
 * stocked turns into a stock_out_hit just as it would on the page. Nothing here
 * writes an order or touches stock.
 */
class SampleDataSeeder extends Seeder
{
    private const SESSIONS = 2000;

    private const DAYS = 30;

    // Fixed so the demo numbers do not move between two runs.
    private const RANDOM_SEED = 20261013;

    private const LOGGED_IN_RATE = 35;

    private const CUSTOMERS = 60;

    // Known, so a developer can log in as a sample shopper to try things.
    public const PASSWORD = 'Sample@123';

    private const FAMILY_NAMES = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ'];

    private const GIVEN_NAMES = [
        'Minh Anh', 'Gia Bảo', 'Quốc Huy', 'Thu Trang', 'Hoàng Nam', 'Ngọc Hân', 'Đức Thịnh', 'Khánh Linh',
        'Tuấn Kiệt', 'Bảo Ngọc', 'Thanh Tùng', 'Mỹ Duyên', 'Hải Đăng', 'Phương Thảo', 'Anh Khoa', 'Thảo Vy',
    ];

    private const SEARCH_RATE = 35;

    private const MISSED_SEARCH_RATE = 25;

    private const FILTER_RATE = 25;

    private const SELECT_RATE = 60;

    private const ADD_CART_RATE = 15;

    private const REMOVE_CART_RATE = 15;

    private const WISHLIST_RATE = 6;

    private const CHECKOUT_RATE = 50;

    private const PURCHASE_RATE = 55;

    private const SIMILAR_CLICK_RATE = 18;

    private const RECENT_CLICK_RATE = 5;

    private const RECOMMENDED = 5;

    // The reminder settings' defaults, and how shoppers answer each email.
    private const REMINDER_HOURS = [1 => 4, 2 => 24];

    private const REMINDER_OPEN_RATE = [1 => 45, 2 => 40];

    private const REMINDER_CLICK_RATE = [1 => 25, 2 => 35];

    private const REMINDER_RETURN_RATE = [1 => 35, 2 => 45];

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

    /** @var array<int, array<string, mixed>> */
    private array $reminders = [];

    /** @var array<string, mixed> the session being written */
    private array $session = [];

    private Carbon $clock;

    public function run(): void
    {
        mt_srand(self::RANDOM_SEED);
        $this->loadCatalogue();

        app(SampleData::class)->clear();

        if ($this->products->isEmpty()) {
            return;
        }

        $this->customers = $this->createCustomers();

        for ($i = 0; $i < self::SESSIONS; $i++) {
            $this->simulateSession();

            if (count($this->rows) >= self::CHUNK) {
                $this->flush();
            }
        }

        $this->flush();
        DB::table('cart_reminders')->insert($this->reminders);
        $this->remindOpenCarts();
    }

    private function loadCatalogue(): void
    {
        $this->products = DB::table('products')
            ->where('pro_hidden', 1)
            ->whereNull('deleted_at')
            ->get(['pro_id', 'cate_id', 'pro_slug', 'pro_name', 'pro_price', 'pro_views']);

        foreach (DB::table('products_quantity')->get(['pro_id', 'color_id', 'size_id', 'quantity']) as $variant) {
            $this->stock[$variant->pro_id][$variant->color_id.'-'.$variant->size_id] = (int) $variant->quantity;
        }

        $this->sizes = DB::table('size')->pluck('size', 'size_id')->map(fn ($size) => (int) $size)->all();
        $this->totalViews = (int) $this->products->sum(fn ($product) => $product->pro_views + 1);
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
            'shown' => [],
            'viewed' => [],
        ];

        $this->pageView(route('home.page'), 'home.page', $this->pick(self::REFERRERS) ?: null);
        $this->browse();

        for ($n = $this->weighted([1 => 45, 2 => 30, 3 => 15, 4 => 10]); $n > 0; $n--) {
            $this->viewNext();
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

    /**
     * The next product, reached through a recommendation now and then.
     */
    private function viewNext(): void
    {
        if ($this->session['shown'] !== [] && $this->chance(self::SIMILAR_CLICK_RATE)) {
            $this->viewProduct($this->products->firstWhere('pro_id', $this->pick($this->session['shown'])), 'similar');
        } elseif (count($this->session['viewed']) > 1 && $this->chance(self::RECENT_CLICK_RATE)) {
            $this->viewProduct($this->products->firstWhere('pro_id', $this->pick($this->session['viewed'])), 'recent');
        } else {
            $this->viewProduct($this->popularProduct());
        }
    }

    private function viewProduct(object $product, ?string $source = null): void
    {
        $this->pageView(route('product.detail', $product->pro_slug), 'product.detail');
        $this->event(Event::VIEW_PRODUCT, ['pro_id' => $product->pro_id] + ($source ? ['meta' => ['source' => $source]] : []));
        $this->recommendAfter($product);

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
     * The two blocks the product page loads under the product: the most viewed
     * of the same category, and what this visit has already seen.
     */
    private function recommendAfter(object $product): void
    {
        $similar = $this->products
            ->where('cate_id', $product->cate_id)
            ->where('pro_id', '!=', $product->pro_id)
            ->sortByDesc('pro_views')
            ->take(self::RECOMMENDED)
            ->pluck('pro_id')
            ->all();
        $recent = array_slice(array_values(array_diff($this->session['viewed'], [$product->pro_id])), 0, self::RECOMMENDED);

        foreach (['similar' => $similar, 'recent' => $recent] as $source => $ids) {
            if ($ids !== []) {
                $this->event(Event::RECOMMEND, ['pro_id' => $product->pro_id, 'meta' => ['source' => $source, 'pro_ids' => $ids]]);
            }
        }

        $this->session['shown'] = $similar;
        $this->session['viewed'] = array_values(array_unique(array_merge([$product->pro_id], $this->session['viewed'])));
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

        if ($cart === [] || $this->customers === []) {
            return;
        }

        if (! $this->chance(self::CHECKOUT_RATE)) {
            $this->remindAbandoned(array_values($cart));

            return;
        }

        $this->checkout(array_values($cart));
    }

    /**
     * The reminder emails a logged-in shopper's abandoned cart would get, and
     * now and then the order that follows a click. A simulated reminder makes
     * no coupon: the coupon table is the shop's real list.
     *
     * @param  array<int, array<string, int>>  $cart
     */
    private function remindAbandoned(array $cart): void
    {
        if ($this->session['user_id'] === null) {
            return;
        }

        $left = $this->clock->copy();
        $key = $this->session['id'];

        foreach (self::REMINDER_HOURS as $step => $hours) {
            $sent = $left->copy()->addHours($hours)->addMinutes(mt_rand(0, 59));

            if ($sent->isFuture()) {
                return;
            }

            $opened = $this->chance(self::REMINDER_OPEN_RATE[$step]) ? $sent->copy()->addMinutes(mt_rand(5, 600)) : null;
            $clicked = $opened && $this->chance(self::REMINDER_CLICK_RATE[$step]) ? $opened->copy()->addMinutes(mt_rand(0, 3)) : null;

            $this->reminders[] = [
                'user_id' => $this->session['user_id'],
                'cart_key' => $key,
                'step' => $step,
                'lines' => json_encode(array_map(fn ($line) => $this->pairOf($line) + ['quantity' => $line['quantity']], $cart)),
                'cart_value' => array_sum(array_map(fn ($line) => $line['price'] * $line['quantity'], $cart)),
                'coupon_id' => null,
                'token' => $this->randomId(),
                'sent_at' => $sent,
                'opened_at' => $opened !== null && ! $opened->isFuture() ? $opened : null,
                'clicked_at' => $clicked !== null && ! $clicked->isFuture() ? $clicked : null,
                'created_at' => $sent,
                'updated_at' => $sent,
            ];

            if ($clicked !== null && $clicked->copy()->addHour()->isPast() && $this->chance(self::REMINDER_RETURN_RATE[$step])) {
                $this->returnFromReminder($cart, $clicked);

                return;
            }
        }
    }

    /**
     * Back through the email's button, which restores the cart, and on to pay.
     *
     * @param  array<int, array<string, int>>  $cart
     */
    private function returnFromReminder(array $cart, Carbon $clicked): void
    {
        $this->session['id'] = $this->randomId();
        $this->clock = $clicked->copy();

        $this->pageView(route('product.cart'), 'product.cart');
        $this->pageView(route('product.checkout'), 'product.checkout');

        $value = array_sum(array_map(fn ($line) => $line['price'] * $line['quantity'], $cart));
        $this->event(Event::CHECKOUT_START, ['meta' => ['lines' => count($cart), 'value' => $value]]);
        $this->event(Event::PLACE_ORDER, ['meta' => ['payment' => 'cod', 'total' => $value]]);

        foreach ($cart as $line) {
            $this->event(Event::PURCHASE, $this->pairOf($line) + ['meta' => ['quantity' => $line['quantity'], 'price' => $line['price']]]);
        }
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
     * @return list<int>
     */
    private function createCustomers(): array
    {
        $password = Hash::make(self::PASSWORD);
        $now = now();
        $rows = [];

        for ($n = 1; $n <= self::CUSTOMERS; $n++) {
            $handle = sprintf('khachmau%02d', $n);
            $rows[] = [
                'username' => $handle,
                'name' => $this->pick(self::FAMILY_NAMES).' '.$this->pick(self::GIVEN_NAMES),
                'email' => $handle.SampleData::EMAIL_DOMAIN,
                'password' => $password,
                'user_status' => 1,
                'user_role' => 0,
                'created_at' => $now->copy()->subDays(mt_rand(self::DAYS, self::DAYS * 6)),
                'updated_at' => $now,
            ];
        }

        DB::table('users')->insert($rows);

        return DB::table('users')->where('email', 'like', '%'.SampleData::EMAIL_DOMAIN)->pluck('user_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * The cart each sample shopper still has open has, in this story, already
     * had both its reminders, opened as often as any other but not acted on.
     * Without them the hourly carts:remind would write to the sample accounts
     * the first time it ran.
     */
    private function remindOpenCarts(): void
    {
        $carts = app(AbandonedCarts::class);
        $lines = app(CartLines::class);

        foreach ($this->customers as $userId) {
            $cart = $carts->openCart($userId);

            if ($cart === null) {
                continue;
            }

            foreach (self::REMINDER_HOURS as $step => $hours) {
                $sent = $cart->lastActivity->copy()->addHours($hours);
                $sent = $sent->isFuture() ? now() : $sent;
                $opened = $this->chance(self::REMINDER_OPEN_RATE[$step]) ? $sent->copy()->addMinutes(mt_rand(5, 600)) : null;
                $clicked = $opened && $this->chance(self::REMINDER_CLICK_RATE[$step]) ? $opened->copy()->addMinutes(mt_rand(0, 3)) : null;

                CartReminderModel::firstOrCreate(['cart_key' => $cart->key, 'step' => $step], [
                    'user_id' => $userId,
                    'lines' => $cart->lines,
                    'cart_value' => $lines->value($lines->sellable($cart->lines)),
                    'token' => $this->randomId(),
                    'sent_at' => $sent,
                    'opened_at' => $opened?->isPast() ? $opened : null,
                    'clicked_at' => $clicked?->isPast() ? $clicked : null,
                ]);
            }
        }
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
        $id = SampleData::SESSION_PREFIX;
        while (strlen($id) < 40) {
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
