<?php

namespace App\Console\Commands;

use App\Services\Analytics\EventAggregator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

class AggregateEvents extends Command
{
    protected $signature = 'events:aggregate
        {--from= : Ngày bắt đầu (Y-m-d), mặc định là hôm qua}
        {--to= : Ngày kết thúc (Y-m-d), mặc định bằng --from}';

    protected $description = 'Tổng hợp nhật ký hành vi thành số liệu theo ngày cho trang phân tích';

    public function handle(EventAggregator $aggregator): int
    {
        try {
            $from = $this->option('from') ? Carbon::createFromFormat('Y-m-d', $this->option('from')) : Carbon::yesterday();
            $to = $this->option('to') ? Carbon::createFromFormat('Y-m-d', $this->option('to')) : $from->copy();
        } catch (Throwable) {
            $this->error('Ngày phải có dạng Y-m-d, ví dụ 2026-10-19.');

            return self::INVALID;
        }

        if ($to->lt($from)) {
            $this->error('--to phải sau hoặc bằng --from.');

            return self::INVALID;
        }

        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $aggregator->aggregate($day);
        }

        $this->info('Đã tổng hợp từ '.$from->toDateString().' đến '.$to->toDateString().'.');

        return self::SUCCESS;
    }
}
