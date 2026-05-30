<?php

namespace App\Exports;
use App\Models\CouponModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;


class ExportCoupon implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    private $count = 1; 
    public function collection()
    {
        $data = CouponModel::get();
        return $data;
    }
    public function headings(): array
    {
        return [
            [
                'Danh sách mã giảm giá | Sneaker Square',  
            ],
            [
                'STT',
                'Tiêu đề',
                'Mã CODE',
                'Số lượng',
                'Ngày khởi tạo',
                'Ngày bắt đầu',
                'Ngày kết thúc',
                'Điều kiện giảm giá',
                'Số giảm',
                'Tình trạng',
            ],
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:J1'); 
        $sheet->getStyle('A1:J1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        return [
            2 => [
                'font' => [
                    // 'color' => new Color(Color::COLOR_BLACK),
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FFC0C0C0',
                    ],
                ],
            ],
        ];
    }
    public function map($row): array
    {
        $today = Carbon::now('Asia/Ho_Chi_Minh')->format('Y/m/d');
        $result = [
            $this->count++,
            $row->coupon_name,
            $row->coupon_code,
            $row->coupon_quantity,
            Carbon::parse($row->coupon_date)->format('d-m-Y'),
            Carbon::parse( $row->coupon_start)->format('d-m-Y'),
            Carbon::parse( $row->coupon_end)->format('d-m-Y'),
            ($row->coupon_condition == 1) ? "Giảm theo tiền" : "Giảm theo %",
            ($row->coupon_condition == 1) ? number_format($row->coupon_value, 0, ',', '.') . 'đ' : ($row->coupon_value).'%',
            (date('Y-m-d', strtotime($row->coupon_end)) >= date('Y-m-d', strtotime($today))) ?

               "Còn hạn"
            
            :
                "Hết hạn"
            ,
                                                        
        ];


        return $result;
    }
}
