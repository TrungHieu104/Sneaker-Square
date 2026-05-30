<?php

namespace App\Exports;

use App\Models\UserModel;
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

class ExportUserAcount implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    private $count = 1; 
    public function collection()
    {
        $data = UserModel::where('user_role',0)->get();
        return $data;
    }
    public function headings(): array
    {
        return [
            [
                'Danh sách khách hàng | Sneaker Square',  
            ],
            [
                'Số thứ tự',
                'Username',
                'Họ tên',
                'Email',
                'Trạng thái',
                'Ngày đăng ký',
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
            $row->username,
            $row->name,
            $row->email,
            ($row->user_status == 1) ? "Kích hoạt" : "Vô hiệu",
            Carbon::parse($row->created_at)->format('d-m-Y'),
        ];


        return $result;
    }
}
