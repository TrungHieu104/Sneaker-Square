<?php

namespace App\Exports;

use App\Models\OrderModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Carbon\Carbon;
class OrdersSheet implements FromCollection, WithHeadings, WithMapping, WithEvents, WithStyles, ShouldAutoSize
{
    private $count = 1; 
    public function collection()
    {
        // Thực hiện lấy dữ liệu từ model và trả về một collection
        // Ví dụ:
        return OrderModel::orderBy('order_id','desc')->get();
    }
    public function title(): string
    {
        return 'Tất cả';
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->setTitle($this->title());
            },
        ];
    }
    public function headings(): array
    {
        return [
            [
                'Danh sách đơn hàng | Sneaker Square',  
            ],
            [
                'Số thứ tự',
                'Mã đơn hàng',
                'Họ và tên',
                'Email',
                'Số điện thoại',
                'Địa chỉ nhận hàng',
                'Vị trí nhận hàng',
                'Thời gian đặt hàng',
                'Tổng tiền',
                'Trạng thái',
            ],
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:I1'); 
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
            $row->order_code,
            $row->order_name,
            $row->order_email,
            $row->order_phone,
            $row->order_address,
            $row->order_local,
            Carbon::parse($row->order_date)->format('d-m-Y'),
            
            number_format($row->order_total, 0, ',', '.') . 'đ',
            
            ($row->order_status == 0) ? "Đơn hàng mới" : 
            (($row->order_delivery_status == 1 && $row->order_status == 1) ? "Vận chuyển" :
            (($row->order_status == 1) ? "Đã xác nhận" :
            (($row->order_status == 2) ? "Đã hủy" :
            (($row->order_status == 3) ? "Hoàn hàng" :"Thành công")))),
        ];


        return $result;
    }
}
class OrderDaySheet implements FromCollection, WithHeadings, WithMapping, WithEvents, WithStyles, ShouldAutoSize
{
    private $count = 1; 
    public function collection()
    {
        $today = Carbon::today();
        return OrderModel::where('order_date',$today)->orderBy('order_id','asc')->get();
    }
    public function title(): string
    {
        return 'Hôm nay';
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->setTitle($this->title());
            },
        ];
    }
    public function headings(): array
    {
        return [
            [
                'Đơn hàng ngày hôm nay | Sneaker Square',  
            ],
            [
                'Số thứ tự',
                'Mã đơn hàng',
                'Họ và tên',
                'Email',
                'Số điện thoại',
                'Địa chỉ nhận hàng',
                'Vị trí nhận hàng',
                'Thời gian đặt hàng',
                'Tổng tiền',
                'Trạng thái',
            ],
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:I1'); 
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
            $row->order_code,
            $row->order_name,
            $row->order_email,
            $row->order_phone,
            $row->order_address,
            $row->order_local,
            Carbon::parse($row->order_date)->format('d-m-Y'),
            
            number_format($row->order_total, 0, ',', '.') . 'đ',
            
            ($row->order_status == 0) ? "Đơn hàng mới" : 
            (($row->order_delivery_status == 1 && $row->order_status == 1) ? "Vận chuyển" :
            (($row->order_status == 1) ? "Đã xác nhận" :
            (($row->order_status == 2) ? "Đã hủy" :
            (($row->order_status == 3) ? "Hoàn hàng" :"Thành công")))),
        ];


        return $result;
    }
}

class OrderWeekSheet implements FromCollection, WithHeadings, WithMapping, WithEvents, WithStyles, ShouldAutoSize
{
    private $count = 1; 
    public function collection()
    {
        $today = Carbon::today();
        $sub7days = Carbon::now('Asia/Ho_Chi_minh')->subDays(7)->toDateString();
        return OrderModel::whereBetween('order_date',[$sub7days,$today])->orderBy('order_id','asc')->get();
    }
    public function title(): string
    {
        return '7 ngày qua';
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->setTitle($this->title());
            },
        ];
    }
    public function headings(): array
    {
        return [
            [
                'Đơn hàng 7 ngày qua | Sneaker Square',  
            ],
            [
                'Số thứ tự',
                'Mã đơn hàng',
                'Họ và tên',
                'Email',
                'Số điện thoại',
                'Địa chỉ nhận hàng',
                'Vị trí nhận hàng',
                'Thời gian đặt hàng',
                'Tổng tiền',
                'Trạng thái',
            ],
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:I1'); 
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
            $row->order_code,
            $row->order_name,
            $row->order_email,
            $row->order_phone,
            $row->order_address,
            $row->order_local,
            Carbon::parse($row->order_date)->format('d-m-Y'),
            number_format($row->order_total, 0, ',', '.') . 'đ',
            ($row->order_status == 0) ? "Đơn hàng mới" : 
            (($row->order_delivery_status == 1 && $row->order_status == 1) ? "Vận chuyển" :
            (($row->order_status == 1) ? "Đã xác nhận" :
            (($row->order_status == 2) ? "Đã hủy" :
            (($row->order_status == 3) ? "Hoàn hàng" :"Thành công")))),
        ];


        return $result;
    }
}

class OrderPrevMonthSheet implements FromCollection, WithHeadings, WithMapping, WithEvents, WithStyles, ShouldAutoSize
{
    private $count = 1; 
    public function collection()
    {
        $today = Carbon::today();
        $start_month = Carbon::now('Asia/Ho_Chi_minh')->subMonth()->startOfMonth()->toDateString();
        $end_month = Carbon::now('Asia/Ho_Chi_minh')->subMonth()->endOfMonth()->toDateString();
        return OrderModel::whereBetween('order_date',[$start_month,$end_month])->orderBy('order_id','asc')->get();
    }
    public function title(): string
    {
        return 'Tháng trước';
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->setTitle($this->title());
            },
        ];
    }
    public function headings(): array
    {
        return [
            [
                'Đơn hàng tháng trước | Sneaker Square',  
            ],
            [
                'Số thứ tự',
                'Mã đơn hàng',
                'Họ và tên',
                'Email',
                'Số điện thoại',
                'Địa chỉ nhận hàng',
                'Vị trí nhận hàng',
                'Thời gian đặt hàng',
                'Tổng tiền',
                'Trạng thái',
            ],
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:I1'); 
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
            $row->order_code,
            $row->order_name,
            $row->order_email,
            $row->order_phone,
            $row->order_address,
            $row->order_local,
            Carbon::parse($row->order_date)->format('d-m-Y'),
            
            number_format($row->order_total, 0, ',', '.') . 'đ',
            
            ($row->order_status == 0) ? "Đơn hàng mới" : 
            (($row->order_delivery_status == 1 && $row->order_status == 1) ? "Vận chuyển" :
            (($row->order_status == 1) ? "Đã xác nhận" :
            (($row->order_status == 2) ? "Đã hủy" :
            (($row->order_status == 3) ? "Hoàn hàng" :"Thành công")))),
        ];


        return $result;
    }
}

class OrderMonthSheet implements FromCollection, WithHeadings, WithMapping, WithEvents, WithStyles, ShouldAutoSize
{
    private $count = 1; 
    public function collection()
    {
        $today = Carbon::today();
        $thismonth = Carbon::now('Asia/Ho_Chi_minh')->startOfMonth()->toDateString();
        return OrderModel::whereBetween('order_date',[$thismonth,$today])->orderBy('order_id','asc')->get();
    }
    public function title(): string
    {
        return 'Tháng này';
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->setTitle($this->title());
            },
        ];
    }
    public function headings(): array
    {
        return [
            [
                'Đơn hàng tháng này | Sneaker Square',  
            ],
            [
                'Số thứ tự',
                'Mã đơn hàng',
                'Họ và tên',
                'Email',
                'Số điện thoại',
                'Địa chỉ nhận hàng',
                'Vị trí nhận hàng',
                'Thời gian đặt hàng',
                'Tổng tiền',
                'Trạng thái',
            ],
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:I1'); 
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
            $row->order_code,
            $row->order_name,
            $row->order_email,
            $row->order_phone,
            $row->order_address,
            $row->order_local,
            Carbon::parse($row->order_date)->format('d-m-Y'),
            
            number_format($row->order_total, 0, ',', '.') . 'đ',
            
            ($row->order_status == 0) ? "Đơn hàng mới" : 
            (($row->order_delivery_status == 1 && $row->order_status == 1) ? "Vận chuyển" :
            (($row->order_status == 1) ? "Đã xác nhận" :
            (($row->order_status == 2) ? "Đã hủy" :
            (($row->order_status == 3) ? "Hoàn hàng" :"Thành công")))),
        ];


        return $result;
    }
}
class OrderYearSheet implements FromCollection, WithHeadings, WithMapping, WithEvents, WithStyles, ShouldAutoSize
{
    private $count = 1; 
    public function collection()
    {
        $today = Carbon::today();
        $sub365days = Carbon::now('Asia/Ho_Chi_minh')->subDays(365)->toDateString();
        return OrderModel::whereBetween('order_date',[$sub365days,$today])->orderBy('order_id','asc')->get();
    }
    public function title(): string
    {
        return 'Năm qua';
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->setTitle($this->title());
            },
        ];
    }
    public function headings(): array
    {
        return [
            [
                'Đơn hàng 365 ngày qua | Sneaker Square',  
            ],
            [
                'Số thứ tự',
                'Mã đơn hàng',
                'Họ và tên',
                'Email',
                'Số điện thoại',
                'Địa chỉ nhận hàng',
                'Vị trí nhận hàng',
                'Thời gian đặt hàng',
                'Tổng tiền',
                'Trạng thái',
            ],
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:I1'); 
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
            $row->order_code,
            $row->order_name,
            $row->order_email,
            $row->order_phone,
            $row->order_address,
            $row->order_local,
            Carbon::parse($row->order_date)->format('d-m-Y'),
            
            number_format($row->order_total, 0, ',', '.') . 'đ',
            
            ($row->order_status == 0) ? "Đơn hàng mới" : 
            (($row->order_delivery_status == 1 && $row->order_status == 1) ? "Vận chuyển" :
            (($row->order_status == 1) ? "Đã xác nhận" :
            (($row->order_status == 2) ? "Đã hủy" :
            (($row->order_status == 3) ? "Hoàn hàng" :"Thành công")))),
        ];


        return $result;
    }
}

class ExportOrder implements WithMultipleSheets
{
    private $count = 1; 
    public function sheets(): array
    {
        $sheets = [];
       
        $dataOrders = OrderModel::get();
        $sheets[] = new OrdersSheet($dataOrders);
        
        $today = Carbon::today();
        $dataDate= OrderModel::where('order_date',$today)->get();
        $sheets[] = new OrderDaySheet($dataDate);
        
        $dataWeek = OrderModel::get();
        $sheets[] = new OrderWeekSheet($dataWeek);

        $dataMonth = OrderModel::get();
        $sheets[] = new OrderMonthSheet($dataMonth);

        $dataPrevMonth = OrderModel::get();
        $sheets[] = new OrderPrevMonthSheet($dataPrevMonth);

        $dataYear = OrderModel::get();
        $sheets[] = new OrderYearSheet($dataYear);

        return $sheets;
    }
    
}
