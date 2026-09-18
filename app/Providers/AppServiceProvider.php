<?php

namespace App\Providers;

use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\GhnCarrier;
use App\Services\Shipping\ShippingCarrier;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // No token means no GHN account, which is the state of a fresh clone
        // and of the test suite. Falling back keeps the checkout working
        // instead of failing on a credential nobody has yet set.
        $this->app->bind(ShippingCarrier::class, fn () => config('services.ghn.token')
            ? new GhnCarrier
            : new FakeCarrier);
    }

    /**
     * Bootstrap any application services.
     *
     * A view composer registered on '*' used to live here. It ran four queries
     * — including two `all()->count()` calls that load the whole `products` and
     * `order` tables into memory — and it ran them again for every view
     * rendered, so a listing page with nine product cards paid for them nine
     * times over. None of the four variables it shared was read by any
     * template. Removing it costs the application nothing.
     */
    public function boot(): void
    {
        //
    }
}
