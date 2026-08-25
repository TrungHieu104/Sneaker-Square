<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
