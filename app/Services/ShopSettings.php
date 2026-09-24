<?php

namespace App\Services;

use App\Models\SettingModel;
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
