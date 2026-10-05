<?php

namespace App\Exports;

use App\Exports\Sheets\OrderSheet;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Every order, then the same list cut to the periods the dashboard reports on.
 */
class ExportOrder implements WithMultipleSheets
{
    public function sheets(): array
    {
        $now = Carbon::now();
        $between = fn (Carbon $from, Carbon $to) => fn ($orders) => $orders
            ->whereBetween('order_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('order_id', 'asc');

        return [
            new OrderSheet('Tất cả', 'Danh sách đơn hàng | Sneaker Square', fn ($orders) => $orders->orderBy('order_id', 'desc')),
            new OrderSheet('Hôm nay', 'Đơn hàng ngày hôm nay | Sneaker Square', $between($now, $now)),
            new OrderSheet('7 ngày qua', 'Đơn hàng 7 ngày qua | Sneaker Square', $between($now->copy()->subDays(7), $now)),
            new OrderSheet('Tháng này', 'Đơn hàng tháng này | Sneaker Square', $between($now->copy()->startOfMonth(), $now)),
            new OrderSheet('Tháng trước', 'Đơn hàng tháng trước | Sneaker Square', $between($now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth())),
            new OrderSheet('Năm qua', 'Đơn hàng 365 ngày qua | Sneaker Square', $between($now->copy()->subDays(365), $now)),
        ];
    }
}
