<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Services\DashboardStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Pins down the rule the dashboard counts orders by.
 *
 * "An order that counts as a sale" — cash on delivery as soon as it is placed,
 * a gateway order only once it is paid — was written out in full three times
 * inside DashboardController::index(). It is one scope now, and these tests
 * exist so moving it did not quietly change any figure.
 */
class DashboardStatisticsTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->customer = $this->makeUser();
    }

    private function makeOrder(string $payment, int $paymentStatus, int $status = OrderStatus::New->value): OrderModel
    {
        return OrderModel::create([
            'order_code' => 'DH' . random_int(1000000, 9999999),
            'order_name' => 'Nguyễn Văn A',
            'order_email' => 'khach@example.test',
            'order_address' => '1 Võ Văn Ngân',
            'order_local' => 'Thủ Đức, TP.HCM',
            'order_phone' => '0912345678',
            'order_delivery_fee' => 30000,
            'order_coupon_value' => 0,
            'order_total' => 1_030_000,
            'order_payment' => $payment,
            'order_payment_status' => $paymentStatus,
            'order_date' => now()->toDateString(),
            'order_status' => $status,
            'user_id' => $this->customer->user_id,
        ]);
    }

    private function stats(): DashboardStatisticsService
    {
        return app(DashboardStatisticsService::class);
    }

    public function test_don_cod_chua_thanh_toan_duoc_tinh_la_don_that(): void
    {
        $this->makeOrder('cod', 0);

        $this->assertSame(1, $this->stats()->newOrderCount());
        $this->assertSame(1, $this->stats()->totals()['totalOrder']);
    }

    public function test_don_qua_cong_thanh_toan_chi_duoc_tinh_khi_da_tra_tien(): void
    {
        $this->makeOrder('redirect', 0);
        $this->makeOrder('payUrl', 0);

        $this->assertSame(0, $this->stats()->newOrderCount(), 'Đơn online chưa trả tiền thì chưa phải doanh thu');

        $this->makeOrder('redirect', 1);

        $this->assertSame(1, $this->stats()->newOrderCount());
    }

    public function test_don_cod_da_thanh_toan_khong_bi_dem_them_lan_nua(): void
    {
        // COD is marked paid on delivery, at which point the order is no longer
        // new. Counting it again here would double-count the same sale.
        $this->makeOrder('cod', 1);

        $this->assertSame(0, $this->stats()->newOrderCount());
    }

    public function test_don_da_xu_ly_khong_con_nam_trong_don_moi(): void
    {
        $this->makeOrder('cod', 0, OrderStatus::Confirmed->value);
        $this->makeOrder('cod', 0, OrderStatus::New->value);

        $this->assertSame(1, $this->stats()->newOrderCount(), 'Chỉ đếm đơn đang ở trạng thái mới');
        $this->assertSame(2, $this->stats()->totals()['totalOrder'], 'Nhưng tổng số đơn thì tính cả hai');
    }

    public function test_don_hom_nay_duoc_dem_rieng(): void
    {
        $this->makeOrder('cod', 0);
        $this->makeOrder('redirect', 1);
        $old = $this->makeOrder('cod', 0);
        $old->order_date = now()->subMonth()->toDateString();
        $old->save();

        $this->assertSame(2, $this->stats()->todayOrderCount());
    }

    public function test_thong_ke_thang_phan_loai_dung_trang_thai(): void
    {
        $this->makeOrder('cod', 0, OrderStatus::Cancelled->value);
        $this->makeOrder('cod', 1, OrderStatus::Completed->value);

        $summary = $this->stats()->ordersThisMonth();

        $this->assertSame(1, $summary['orderFail']);
        $this->assertSame(1, $summary['orderDelivered']);
    }
}
