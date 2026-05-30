<?php

namespace App\Exports;

use App\Models\StatisticModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class ExportStatisticDay implements FromCollection , WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    private $count = 1; 
    private $tongDoanhThu = 0;
    private $tongLoiNhuan = 0;
    private $tongDonHang = 0;
    public function collection()
    {
        $today = Carbon::today();
        $data = StatisticModel::where('order_date',$today)->get();
        foreach ($data as $row) {
            $this->tongDoanhThu += $row->sales;
            $this->tongLoiNhuan += $row->profit;
            $this->tongDonHang += $row->order_total;
        }


        return $data;
    }

    public function headings(): array
    {
        return [
            [
                'Thống kê doanh thu | Sneaker Square',  
            ],
            [
                'Số thứ tự',
                'Ngày',
                'Doanh thu',
                'Lợi nhuận',
                'Số đơn hàng'
            ],
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:F1'); 
        $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); 
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
        $result = [
            $this->count++,
            Carbon::parse($row->order_date)->format('d/m/Y'),
            number_format($row->sales, 0, ',', '.') . ' VNĐ',
            number_format($row->profit, 0, ',', '.') . ' VNĐ',
            $row->order_total,
        ];

        return $result;
    }
   
    use Exportable;
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $data = [
                    '',
                ];
                $data3 = [
                    'Tổng số đơn', $this->tongDonHang,
                ];
                $data1 = [
                    'Tổng doanh thu', number_format($this->tongDoanhThu, 0, ',', '.') . ' VNĐ',
                ];
                $data2 = [
                    'Tổng lợi nhuận', number_format($this->tongLoiNhuan, 0, ',', '.') . ' VNĐ',
                ];

                $event->sheet->append([$data,$data3,$data1,$data2]); 
            },
        ];
    }
}
