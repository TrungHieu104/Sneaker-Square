<?php

namespace App\Exports;

use App\Exports\Sheets\ListingSheet;
use App\Models\CouponModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExportCoupon extends ListingSheet
{
    protected function heading(): string
    {
        return 'Danh sách mã giảm giá | Sneaker Square';
    }

    protected function numberHeader(): string
    {
        return 'STT';
    }

    protected function columns(): array
    {
        return [
            'Tiêu đề', 'Mã CODE', 'Số lượng', 'Ngày khởi tạo', 'Ngày bắt đầu',
            'Ngày kết thúc', 'Điều kiện giảm giá', 'Số giảm', 'Tình trạng',
        ];
    }

    protected function records(): Collection
    {
        return CouponModel::get();
    }

    protected function row(mixed $coupon): array
    {
        $byAmount = (int) $coupon->coupon_condition === 1;
        $today = Carbon::now()->toDateString();

        return [
            $coupon->coupon_name,
            $coupon->coupon_code,
            $coupon->coupon_quantity,
            Carbon::parse($coupon->coupon_date)->format('d-m-Y'),
            Carbon::parse($coupon->coupon_start)->format('d-m-Y'),
            Carbon::parse($coupon->coupon_end)->format('d-m-Y'),
            $byAmount ? 'Giảm theo tiền' : 'Giảm theo %',
            $byAmount ? number_format($coupon->coupon_value, 0, ',', '.').'đ' : $coupon->coupon_value.'%',
            Carbon::parse($coupon->coupon_end)->toDateString() >= $today ? 'Còn hạn' : 'Hết hạn',
        ];
    }
}
