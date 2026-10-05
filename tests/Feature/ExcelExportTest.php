<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Exports\ExportAccounts;
use App\Exports\ExportCoupon;
use App\Exports\ExportOrder;
use App\Exports\ExportStatistic;
use App\Models\CouponModel;
use App\Models\StatisticModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * What each spreadsheet the admin downloads actually contains: its sheets,
 * the title row, the column headers, the rows and, for revenue, the totals.
 */
class ExcelExportTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
    }

    private function open(object $export): Spreadsheet
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, Excel::raw($export, ExcelFormat::XLSX));
        $book = IOFactory::load($path);
        unlink($path);

        return $book;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function rows(Spreadsheet $book, int $sheet = 0): array
    {
        return $book->getSheet($sheet)->toArray(null, true, false);
    }

    public function test_file_don_hang_co_du_6_sheet_theo_khoang_thoi_gian(): void
    {
        $customer = $this->makeUser();
        $order = app(PlaceOrderAction::class)->execute(
            $customer,
            [$this->cartLine($this->makeProduct(slug: 'giay-xuat-file'), 1)],
            null,
            $this->makeAddress($customer),
            ['payment' => 'cod', 'note_customer' => null],
        );

        // MySQL stores the date column normalised; SQLite keeps whatever string
        // it was handed, so the test writes it the way MySQL would hold it.
        $order->order_date = now()->toDateString();
        $order->save();

        $book = $this->open(new ExportOrder);

        $this->assertSame(
            ['Tất cả', 'Hôm nay', '7 ngày qua', 'Tháng này', 'Tháng trước', 'Năm qua'],
            $book->getSheetNames()
        );

        $all = $this->rows($book);
        $this->assertSame('Danh sách đơn hàng | Sneaker Square', $all[0][0]);
        $this->assertSame(['Số thứ tự', 'Mã đơn hàng', 'Họ và tên'], array_slice($all[1], 0, 3));
        $this->assertSame([1, $order->order_code], array_slice($all[2], 0, 2));
        $this->assertSame($order->fresh()->order_status->label(), $all[2][9]);

        $this->assertSame('Đơn hàng ngày hôm nay | Sneaker Square', $this->rows($book, 1)[0][0]);
        $this->assertSame($order->order_code, $this->rows($book, 1)[2][1]);
        $this->assertSame('Đơn hàng 365 ngày qua | Sneaker Square', $this->rows($book, 5)[0][0]);
    }

    public function test_file_doanh_thu_co_dong_tong_cuoi_bang(): void
    {
        StatisticModel::create(['order_date' => now()->toDateString(), 'sales' => 1_500_000, 'profit' => 400_000, 'order_total' => 2]);
        StatisticModel::create(['order_date' => now()->subDays(40)->toDateString(), 'sales' => 500_000, 'profit' => 100_000, 'order_total' => 1]);

        $all = $this->rows($this->open(new ExportStatistic));

        $this->assertSame('Thống kê doanh thu | Sneaker Square', $all[0][0]);
        $this->assertSame(['Số thứ tự', 'Ngày', 'Doanh thu', 'Lợi nhuận', 'Số đơn hàng'], array_slice($all[1], 0, 5));
        $this->assertSame([1, now()->format('d/m/Y'), '1.500.000 VNĐ', '400.000 VNĐ', 2], array_slice($all[2], 0, 5));
        $this->assertSame(['Tổng số đơn', 3], array_slice($all[5], 0, 2));
        $this->assertSame(['Tổng doanh thu', '2.000.000 VNĐ'], array_slice($all[6], 0, 2));
        $this->assertSame(['Tổng lợi nhuận', '500.000 VNĐ'], array_slice($all[7], 0, 2));

        // A period keeps only its own days, and totals only those.
        $month = $this->rows($this->open(ExportStatistic::thisMonth()));
        $this->assertSame(1, $month[2][0]);
        $this->assertNull($month[3][0]);
        $this->assertSame(['Tổng số đơn', 2], array_slice($month[4], 0, 2));
    }

    public function test_file_ma_giam_gia_va_tai_khoan(): void
    {
        CouponModel::create([
            'coupon_name' => 'Giảm hè', 'coupon_code' => 'HE2026', 'coupon_value' => 50_000, 'coupon_quantity' => 10,
            'coupon_used' => 0, 'coupon_condition' => 1, 'coupon_date' => '2026-06-01',
            'coupon_start' => '2026-06-01', 'coupon_end' => now()->addMonth()->toDateString(),
        ]);
        $this->makeUser(email: 'khach@example.test', username: 'khach');
        $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);

        $coupons = $this->rows($this->open(new ExportCoupon));
        $this->assertSame('Danh sách mã giảm giá | Sneaker Square', $coupons[0][0]);
        $this->assertSame([1, 'Giảm hè', 'HE2026', 10], array_slice($coupons[2], 0, 4));
        $this->assertSame(['Giảm theo tiền', '50.000đ', 'Còn hạn'], array_slice($coupons[2], 7, 3));

        $customers = $this->rows($this->open(ExportAccounts::customers()));
        $this->assertSame('Danh sách khách hàng | Sneaker Square', $customers[0][0]);
        $this->assertSame([1, 'khach'], array_slice($customers[2], 0, 2));
        $this->assertCount(3, array_filter($customers, fn ($row) => $row[0] !== null));

        $admins = $this->rows($this->open(ExportAccounts::admins()));
        $this->assertSame('Danh sách quản trị viên | Sneaker Square', $admins[0][0]);
        $this->assertSame([1, 'quantri', 'Nguyễn Văn A', 'admin@example.test', 'Kích hoạt'], array_slice($admins[2], 0, 5));
    }
}
