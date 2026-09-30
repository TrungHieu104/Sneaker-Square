<?php

namespace App\Services;

use App\Models\SettingModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Values the admin can change without a deploy.
 *
 * Every read goes through the cache because the auto-complete job and every
 * order page ask for the same handful of keys; a write forgets the cached copy
 * so the new value applies on the next read.
 */
class ShopSettings
{
    public const AUTO_COMPLETE_DAYS = 'orders.auto_complete_days';

    public const DEFAULT_AUTO_COMPLETE_DAYS = 7;

    public const MIN_DAYS = 1;

    public const MAX_DAYS = 30;

    public const RETURN_DAYS = 'orders.return_days';

    public const DEFAULT_RETURN_DAYS = 7;

    public const PAYMENT_WINDOW = 'orders.payment_window_minutes';

    public const DEFAULT_PAYMENT_WINDOW = 30;

    /**
     * Below five minutes a customer who is sent to their banking app and back
     * loses the order on the way; above a day, stock sits behind orders
     * nobody is going to pay for.
     */
    public const MIN_PAYMENT_WINDOW = 5;

    public const MAX_PAYMENT_WINDOW = 1440;

    /**
     * The shop's own details as the storefront prints them, keyed by the
     * form field that edits each one.
     *
     * @var array<string, string>
     */
    public const SHOP_INFO = [
        'address' => 'shop.address',
        'phone' => 'shop.phone',
        'email' => 'shop.email',
        'map_embed' => 'shop.map_embed',
        'fanpage_embed' => 'shop.fanpage_embed',
        'chat_embed' => 'shop.chat_embed',
    ];

    /**
     * Days after the carrier reports a parcel delivered before the order is
     * treated as received, for the customer who never presses the button.
     */
    public function autoCompleteDays(): int
    {
        $days = (int) $this->get(self::AUTO_COMPLETE_DAYS, (string) self::DEFAULT_AUTO_COMPLETE_DAYS);

        // A value typed straight into the database is not validated; clamping
        // here keeps a zero from completing every order the moment it arrives.
        return max(self::MIN_DAYS, min(self::MAX_DAYS, $days));
    }

    public function setAutoCompleteDays(int $days): void
    {
        $this->put(self::AUTO_COMPLETE_DAYS, (string) $days);
    }

    /**
     * Days after an order completes during which the customer may still ask
     * to send it back.
     */
    public function returnDays(): int
    {
        $days = (int) $this->get(self::RETURN_DAYS, (string) self::DEFAULT_RETURN_DAYS);

        return max(self::MIN_DAYS, min(self::MAX_DAYS, $days));
    }

    public function setReturnDays(int $days): void
    {
        $this->put(self::RETURN_DAYS, (string) $days);
    }

    /**
     * How long a gateway order holds its stock waiting to be paid.
     */
    public function paymentWindowMinutes(): int
    {
        $minutes = (int) $this->get(self::PAYMENT_WINDOW, (string) self::DEFAULT_PAYMENT_WINDOW);

        return max(self::MIN_PAYMENT_WINDOW, min(self::MAX_PAYMENT_WINDOW, $minutes));
    }

    public function setPaymentWindowMinutes(int $minutes): void
    {
        $this->put(self::PAYMENT_WINDOW, (string) $minutes);
    }

    /**
     * @return array<string, string> keyed as SHOP_INFO is
     */
    public function shopInfo(): array
    {
        $info = [];

        foreach (self::SHOP_INFO as $field => $key) {
            $info[$field] = (string) $this->get($key, '');
        }

        return $info;
    }

    /**
     * The same details for a page or an email to print, with the phone split
     * into groups the way people read a number aloud: 0369 469 525.
     *
     * @return array<string, string> SHOP_INFO's keys plus `phone_display`
     */
    public function shopInfoForDisplay(): array
    {
        $info = $this->shopInfo();
        $digits = $info['phone'];

        $info['phone_display'] = strlen($digits) === 10
            ? substr($digits, 0, 4).' '.substr($digits, 4, 3).' '.substr($digits, 7)
            : $digits;

        return $info;
    }

    /**
     * @param  array<string, ?string>  $info  keyed as SHOP_INFO is
     */
    public function setShopInfo(array $info): void
    {
        foreach (self::SHOP_INFO as $field => $key) {
            if (array_key_exists($field, $info)) {
                $this->put($key, (string) $info[$field]);
            }
        }
    }

    /**
     * The shop's details in the shape the storefront layouts were written
     * for: a list of rows, each with the old contact table's column names.
     * They loop over it, so a missing address prints nothing rather than
     * failing the page.
     *
     * @return Collection<int, object>
     */
    public function storefrontContact(): Collection
    {
        $info = $this->shopInfo();

        return collect([(object) [
            'contact_address' => $info['address'],
            'contact_phone' => $info['phone'],
            'contact_email' => $info['email'],
            'map_link' => $info['map_embed'],
            'fanpage_link' => $info['fanpage_embed'],
            'tawk_link' => $info['chat_embed'],
        ]]);
    }

    private function get(string $key, ?string $default = null): ?string
    {
        $value = Cache::rememberForever($this->cacheKey($key), function () use ($key) {
            // Cached as an empty string when the row is missing, so a key that
            // was never set does not send every call back to the database.
            return (string) SettingModel::whereKey($key)->value('setting_value');
        });

        return $value === '' ? $default : $value;
    }

    private function put(string $key, string $value): void
    {
        SettingModel::updateOrCreate(['setting_key' => $key], ['setting_value' => $value]);
        Cache::forget($this->cacheKey($key));
    }

    private function cacheKey(string $key): string
    {
        return 'settings.'.$key;
    }
}
