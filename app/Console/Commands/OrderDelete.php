<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderModel;
class OrderDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order-detele';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoffDate = now()->subYear(); 
        OrderModel::where('order_date', '<', $cutoffDate)->delete();
    }
}

