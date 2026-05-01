<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ProductModel;
use App\Models\OrderModel;
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
     */
    public function boot(): void
    {
        view()->composer('*', function($view){
            $min_price = ProductModel::min('pro_price');
            $max_price = ProductModel::max('pro_price');

            $app_product = ProductModel::all()->count();
            $app_order = OrderModel::all()->count();

            $view->with('min_price', $min_price)->with('max_price',$max_price)
            ->with('app_product', $app_product)
            ->with('app_order', $app_order);
        });
    }
}
