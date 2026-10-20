<?php

namespace App\Services;

use App\Models\OrderModel;
use App\Models\UserEventModel;
use App\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes down what shoppers do, for the BI and CI features to read back.
 *
 * Recording never gets in the shopper's way: a failed write is reported and
 * the request carries on, because a lost analytics row costs far less than the
 * add-to-cart it was describing.
 */
class UserEventLog
{
    public const COOKIE = 'ss_vid';

    private const COOKIE_MINUTES = 60 * 24 * 365;

    private const ID_LENGTH = 40;

    private const URL_LENGTH = 2048;

    private const BOT = '/bot|crawl|spider|slurp/i';

    public function __construct(private readonly Request $request) {}

    /**
     * Gives the visitor an id, from their cookie or a new one, for the rest of
     * the request to record under. Crawlers get none and so record nothing.
     */
    public function identify(): ?string
    {
        if (preg_match(self::BOT, (string) $this->request->userAgent())) {
            return null;
        }

        $id = $this->request->cookie(self::COOKIE);

        if (! is_string($id) || ! preg_match('/^[A-Za-z0-9]{'.self::ID_LENGTH.'}$/', $id)) {
            $id = Str::random(self::ID_LENGTH);
            Cookie::queue(self::COOKIE, $id, self::COOKIE_MINUTES);
        }

        $this->request->attributes->set(self::COOKIE, $id);

        return $id;
    }

    /**
     * @param  array{pro_id?: int|string|null, size_id?: int|string|null, color_id?: int|string|null, order_id?: int|null, meta?: array<string, mixed>}  $fields
     */
    public function record(string $type, array $fields = []): void
    {
        $visitor = $this->visitor();

        if ($visitor === null) {
            return;
        }

        $this->write($type, $fields + [
            'session_id' => $visitor,
            'user_id' => Auth::id(),
            'device' => $this->device(),
            'ip' => $this->request->ip(),
        ]);
    }

    /**
     * One row per line of the order, so what sold can be read per size and
     * colour like everything else.
     *
     * Called wherever an order turns into a sale, and safe to call twice: the
     * gateway's server call and the customer's return both report the same
     * payment. The visitor is taken from the place_order row because that
     * server call carries no cookie.
     */
    public function recordPurchase(OrderModel $order): void
    {
        if (UserEventModel::where('event_type', UserEventModel::PURCHASE)->where('order_id', $order->order_id)->exists()) {
            return;
        }

        $placed = UserEventModel::where('event_type', UserEventModel::PLACE_ORDER)
            ->where('order_id', $order->order_id)
            ->first();
        $visitor = $placed->session_id ?? $this->visitor();

        if ($visitor === null) {
            return;
        }

        foreach ($order->orderDetail as $line) {
            $this->write(UserEventModel::PURCHASE, [
                'session_id' => $visitor,
                'user_id' => $order->user_id,
                'pro_id' => $line->pro_id,
                'size_id' => $line->size_id,
                'color_id' => $line->color_id,
                'order_id' => $order->order_id,
                'meta' => ['quantity' => (int) $line->quantity, 'price' => (int) $line->price],
                'device' => $placed->device ?? $this->device(),
                'ip' => $placed->ip ?? $this->request->ip(),
            ]);
        }
    }

    /**
     * Hands the guest history of this browser to the account that just
     * logged in on it.
     */
    public function claimFor(UserModel $user): void
    {
        $visitor = $this->visitor();

        if ($visitor === null) {
            return;
        }

        UserEventModel::where('session_id', $visitor)
            ->whereNull('user_id')
            ->update(['user_id' => $user->user_id]);
    }

    private function visitor(): ?string
    {
        $id = $this->request->attributes->get(self::COOKIE);

        return is_string($id) ? $id : null;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function write(string $type, array $fields): void
    {
        // A form post or a beacon happened on the page that sent it, so that
        // page is the event's url rather than the endpoint it posted to.
        $isPage = $this->request->isMethod('GET');
        $referer = $this->request->headers->get('referer');

        try {
            UserEventModel::create($fields + [
                'event_type' => $type,
                'url' => Str::limit($isPage ? $this->request->fullUrl() : ($referer ?? $this->request->fullUrl()), self::URL_LENGTH, ''),
                'referrer' => $isPage && $referer ? Str::limit($referer, self::URL_LENGTH, '') : null,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function device(): string
    {
        $agent = (string) $this->request->userAgent();

        return match (true) {
            (bool) preg_match('/iPad|Tablet|Android(?!.*Mobile)/i', $agent) => 'tablet',
            (bool) preg_match('/Mobi|iPhone|Android/i', $agent) => 'mobile',
            default => 'desktop',
        };
    }
}
