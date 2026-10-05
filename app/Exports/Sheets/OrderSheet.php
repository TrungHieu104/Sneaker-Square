<?php

namespace App\Exports\Sheets;

use App\Models\OrderModel;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OrderSheet extends ListingSheet
{
    /**
     * @param  Closure(Builder<OrderModel>): Builder<OrderModel>  $scope
     */
    public function __construct(
        private readonly string $tab,
        private readonly string $heading,
        private readonly Closure $scope,
    ) {}

    protected function title(): string
    {
        return $this->tab;
    }

    protected function heading(): string
    {
        return $this->heading;
    }

    protected function columns(): array
    {
        return [
            'Mã đơn hàng', 'Họ và tên', 'Email', 'Số điện thoại', 'Địa chỉ nhận hàng',
            'Vị trí nhận hàng', 'Thời gian đặt hàng', 'Tổng tiền', 'Trạng thái',
        ];
    }

    protected function records(): Collection
    {
        return ($this->scope)(OrderModel::query())->get();
    }

    protected function row(mixed $order): array
    {
        return [
            $order->order_code,
            $order->order_name,
            $order->order_email,
            $order->order_phone,
            $order->order_address,
            $order->order_local,
            Carbon::parse($order->order_date)->format('d-m-Y'),
            number_format($order->order_total, 0, ',', '.').'đ',
            $order->order_status->label(),
        ];
    }
}
