<?php

namespace App\Console\Commands;

use App\Services\CustomerSegments\SegmentCalculator;
use Illuminate\Console\Command;

class RecalculateCustomerSegments extends Command
{
    protected $signature = 'customers:segment';

    protected $description = 'Chấm điểm RFM và xếp lại phân khúc cho toàn bộ khách đã có đơn thành công';

    public function handle(SegmentCalculator $calculator): int
    {
        $this->info('Đã xếp phân khúc cho '.$calculator->rebuild().' khách.');

        return self::SUCCESS;
    }
}
