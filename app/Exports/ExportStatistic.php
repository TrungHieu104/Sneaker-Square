<?php

namespace App\Exports;

use App\Exports\Sheets\ListingSheet;
use App\Models\StatisticModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;

/**
 * Revenue by day, over the whole history or one of the dashboard's periods,
 * with the totals for that period under the table.
 */
class ExportStatistic extends ListingSheet
{
    use Exportable;

    private ?Collection $records = null;

    public function __construct(private readonly ?Carbon $from = null, private readonly ?Carbon $to = null) {}

    public static function today(): self
    {
        return new self(self::now(), self::now());
    }

    public static function lastSevenDays(): self
    {
        return new self(self::now()->subDays(7), self::now());
    }

    public static function thisMonth(): self
    {
        return new self(self::now()->startOfMonth(), self::now());
    }

    public static function lastMonth(): self
    {
        return new self(self::now()->subMonth()->startOfMonth(), self::now()->subMonth()->endOfMonth());
    }

    public static function thisYear(): self
    {
        return new self(self::now()->startOfYear(), self::now());
    }

    private static function now(): Carbon
    {
        return Carbon::now();
    }

    protected function heading(): string
    {
        return 'Thống kê doanh thu | Sneaker Square';
    }

    protected function columns(): array
    {
        return ['Ngày', 'Doanh thu', 'Lợi nhuận', 'Số đơn hàng'];
    }

    protected function records(): Collection
    {
        return $this->records ??= StatisticModel::query()
            ->when($this->from, fn ($days) => $days->whereBetween('order_date', [$this->from->toDateString(), $this->to->toDateString()]))
            ->orderBy('order_date', 'desc')
            ->get();
    }

    protected function row(mixed $day): array
    {
        return [
            Carbon::parse($day->order_date)->format('d/m/Y'),
            $this->money($day->sales),
            $this->money($day->profit),
            $day->order_total,
        ];
    }

    protected function footer(): array
    {
        $days = $this->records();

        return [
            ['Tổng số đơn', $days->sum('order_total')],
            ['Tổng doanh thu', $this->money($days->sum('sales'))],
            ['Tổng lợi nhuận', $this->money($days->sum('profit'))],
        ];
    }

    private function money(int|float|string $amount): string
    {
        return number_format((float) $amount, 0, ',', '.').' VNĐ';
    }
}
