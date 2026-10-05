<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:generate-sitemap')->daily();
        $schedule->command('orders:auto-complete')->withoutOverlapping()->hourly();
        // Every minute because the payment window is counted in minutes: an
        // hourly run would hold an abandoned order's stock for up to an hour
        // past the deadline the customer was shown.
        $schedule->command('orders:expire-unpaid')->withoutOverlapping()->everyMinute();
        $schedule->command('wallet:doi-soat')->withoutOverlapping()->dailyAt('03:00');
        $schedule->call(function () {
            Artisan::call('cache:clear');
        })->dailyAt('16:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
