<?php

namespace App\Exports;

use App\Models\StatisticModel;
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

class ExportOrderDay implements FromCollection , WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    private $count = 1; 
    private $tongDoanhThu = 0;
    private $tongLoiNhuan = 0;

    public function collection()
    {
        $data = StatisticModel::all();
        foreach ($data as $row) {
            $this->tongDoanhThu += $row->sales;
            $this->tongLoiNhuan += $row->profit;
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
                'Tổng doanh thu',
                'Tổng lợi nhuận',
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
            Carbon::parse($row->order_date)->format('d-m-Y'),
            $row->sales,
            $row->profit,
            '',
            '',
        ];

        if ($this->count === 2) {
            $result[4] = number_format($this->tongDoanhThu, 0, ',', '.') . 'đ';
            $result[5] = number_format($this->tongLoiNhuan, 0, ',', '.') . 'đ';
        }

        return $result;
    }
}
