<?php

namespace App\Models\Concerns;

use Spatie\ResponseCache\Facades\ResponseCache;

/**
 * For a model the storefront prints: storefront pages are cached for days,
 * so any change to one of these rows empties that cache and shows at once.
 */
trait ClearsResponseCache
{
    public static function bootClearsResponseCache(): void
    {
        $clear = fn () => ResponseCache::clear();

        // A restore saves the row, and a force delete fires `deleted` too.
        static::saved($clear);
        static::deleted($clear);
    }
}
