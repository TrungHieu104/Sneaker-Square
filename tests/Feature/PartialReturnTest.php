<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Enums\OrderStatus;
use App\Models\CouponModel;
use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use App\Models\OrderReturnModel;
use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Sending back part of an order rather than all of it.
 *
 * Two pairs ordered to try on, one kept: the shop must restock one pair, take
 * one pair off the revenue report and refund one pair — with no delivery fee,
 * because that parcel was still carried.
 */
class PartialReturnTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $customer;

    private ProductModel $giay;

    private ProductModel $tat;

    private ?UserModel $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('public');
        $this->seedLookupTables();
        $this->customer = $this->makeUser();
    }

    // ---------------------------------------------------------- fixtures

    /**
     * A completed two-line order: two pairs of shoes and one pair of socks.
     */
    private function makeCompletedOrder(?CouponModel $coupon = null): OrderModel
    {
        $this->giay = $this->makeProduct(price: 1_000_000, stock: 10, slug: 'giay-chay-bo');
        $this->tat = $this->makeProduct(price: 100_000, stock: 10, slug: 'tat-the-thao');

        $order = app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($this->giay, 2), $this->cartLine($this->tat, 1)],
            $coupon,
            $this->makeAddress($this->customer),
            ['payment' => 'cod', 'note_customer' => null],
        );

        $order->order_status = OrderStatus::Delivered;
        $order->order_shipping_code = 'LDI01';
        $order->order_shipping_status = 'delivered';
        $order->order_delivery_status = 1;
        $order->save();

        $this->actingAs($this->customer)->post(route('success.order'), ['order_code' => $order->order_code]);

        return $order->fresh();
    }

    private function lineOf(OrderModel $order, ProductModel $product): OrderDetailModel
    {
        return OrderDetailModel::where('order_id', $order->order_id)
            ->where('pro_id', $product->pro_id)
            ->firstOrFail();
    }

    /**
     * @param  array<int, int>  $items
     */
    private function requestReturn(OrderModel $order, array $items, ?UserModel $as = null)
    {
        return $this->actingAs($as ?? $this->customer)
            ->from(route('orderBill.checkout', $order->order_code))
            ->patch(route('return.order', $order->order_code), [
                'items' => $items,
                'reason' => 'sai_size',
                'description' => 'Đôi thứ hai bị chật',
                'refund_info' => 'Vietcombank 0123456789 NGUYEN VAN A',
            ]);
    }

    private function admin(string $route, OrderModel $order, array $data = [])
    {
        $this->admin ??= tap(
            $this->makeUser(email: 'donhang@example.test', username: 'quanlydon', role: 1),
            fn (UserModel $u) => $u->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web')),
        );

        return $this->actingAs($this->admin)->post(route($route, $order->order_id), $data);
    }

    private function stockOfProduct(ProductModel $product): int
    {
        return (int) ProductQuantityModel::where('pro_id', $product->pro_id)->value('quantity');
    }

    private function sales(): int
    {
        return (int) DB::table('statistical')->value('sales');
    }

    private function wallet(): int
    {
        return app(WalletService::class)->for($this->customer)->balance;
    }

    // -------------------------------------------------------- chọn hàng trả

    public function test_khach_chon_tra_mot_phan_thi_chi_ghi_dung_dong_do(): void
    {
        $order = $this->makeCompletedOrder();
        $giay = $this->lineOf($order, $this->giay);

        $this->requestReturn($order, [$giay->order_details_id => 1])->assertRedirect();

        $yeuCau = OrderReturnModel::firstOrFail();

        $this->assertCount(1, $yeuCau->items);
        $this->assertSame(1, $yeuCau->items->first()->quantity);
        $this->assertTrue($yeuCau->isPartial());
    }

    public function test_tra_het_moi_dong_thi_khong_phai_tra_mot_phan(): void
    {
        $order = $this->makeCompletedOrder();

        $this->requestReturn($order, [
            $this->lineOf($order, $this->giay)->order_details_id => 2,
            $this->lineOf($order, $this->tat)->order_details_id => 1,
        ])->assertRedirect();

        $this->assertFalse(OrderReturnModel::firstOrFail()->isPartial());
    }

    public function test_khong_chon_san_pham_nao_thi_khong_gui_duoc(): void
    {
        $order = $this->makeCompletedOrder();
        $giay = $this->lineOf($order, $this->giay);

        $this->requestReturn($order, [$giay->order_details_id => 0])->assertRedirect();

        $this->assertSame(0, OrderReturnModel::count());
    }

    public function test_khong_tra_duoc_nhieu_hon_so_da_mua(): void
    {
        $order = $this->makeCompletedOrder();
        $giay = $this->lineOf($order, $this->giay);

        $this->requestReturn($order, [$giay->order_details_id => 5])->assertRedirect();

        $this->assertSame(0, OrderReturnModel::count());
    }

    public function test_khong_tra_duoc_dong_hang_cua_don_khac(): void
    {
        $order = $this->makeCompletedOrder();

        $donKhac = app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($this->giay, 1)],
            null,
            $this->makeAddress($this->customer),
            ['payment' => 'cod', 'note_customer' => null],
        );
        $dongCuaDonKhac = $this->lineOf($donKhac, $this->giay);

        $this->requestReturn($order, [$dongCuaDonKhac->order_details_id => 1])->assertRedirect();

        $this->assertSame(0, OrderReturnModel::where('order_id', $order->order_id)->count());
    }

    // ------------------------------------------------------------- tồn kho

    public function test_nhan_hang_tra_chi_cong_lai_dung_so_luong(): void
    {
        $order = $this->makeCompletedOrder();
        $giay = $this->lineOf($order, $this->giay);

        $this->requestReturn($order, [$giay->order_details_id => 1]);
        $this->admin('returns.approve', $order);
        $this->admin('returns.receive', $order);

        // Ten on the shelf, two sold, one of those two back again.
        $this->assertSame(9, $this->stockOfProduct($this->giay));
        $this->assertSame(9, $this->stockOfProduct($this->tat));
    }

    public function test_tra_mot_phan_thi_don_ve_trang_thai_hoan_mot_phan(): void
    {
        $order = $this->makeCompletedOrder();
        $giay = $this->lineOf($order, $this->giay);

        $this->requestReturn($order, [$giay->order_details_id => 1]);
        $this->admin('returns.approve', $order);
        $this->admin('returns.receive', $order);

        $this->assertSame(OrderStatus::PartiallyReturned, $order->fresh()->order_status);
    }

    public function test_tra_toan_bo_thi_don_ve_hoan_ve_kho(): void
    {
        $order = $this->makeCompletedOrder();

        $this->requestReturn($order, [
            $this->lineOf($order, $this->giay)->order_details_id => 2,
            $this->lineOf($order, $this->tat)->order_details_id => 1,
        ]);
        $this->admin('returns.approve', $order);
        $this->admin('returns.receive', $order);

        $this->assertSame(OrderStatus::Returned, $order->fresh()->order_status);
        $this->assertSame(10, $this->stockOfProduct($this->giay));
        $this->assertSame(10, $this->stockOfProduct($this->tat));
    }

    // -------------------------------------------------------------- tiền

    public function test_tra_mot_phan_khong_hoan_phi_van_chuyen(): void
    {
        $order = $this->makeCompletedOrder();
        $giay = $this->lineOf($order, $this->giay);

        $this->requestReturn($order, [$giay->order_details_id => 1]);

        $this->assertSame(1_000_000, OrderReturnModel::firstOrFail()->refundDue());
    }

    public function test_tra_toan_bo_thi_hoan_ca_phi_van_chuyen(): void
    {
        $order = $this->makeCompletedOrder();

        $this->requestReturn($order, [
            $this->lineOf($order, $this->giay)->order_details_id => 2,
            $this->lineOf($order, $this->tat)->order_details_id => 1,
        ]);

        $this->assertSame((int) $order->order_total, OrderReturnModel::firstOrFail()->refundDue());
    }

    public function test_tien_hoan_chia_deu_ma_giam_gia_theo_gia_tri_hang_tra(): void
    {
        $coupon = CouponModel::create([
            'coupon_name' => 'Giảm 210k',
            'coupon_code' => 'GIAM210',
            'coupon_value' => 210_000,
            'coupon_condition' => 1,
            'coupon_quantity' => 5,
            'coupon_used' => 0,
            'coupon_start' => now()->subDay()->toDateString(),
            'coupon_end' => now()->addDays(30)->toDateString(),
        ]);

        $order = $this->makeCompletedOrder($coupon);
        $giay = $this->lineOf($order, $this->giay);

        $this->requestReturn($order, [$giay->order_details_id => 1]);

        // Goods 2.100.000đ, of which the returned pair is 1.000.000đ: it
        // carries 10/21 of the discount, so 1.000.000 - 100.000.
        $this->assertSame(900_000, OrderReturnModel::firstOrFail()->refundDue());
    }

    public function test_hoan_tien_vao_vi_khach_va_chi_tru_doanh_thu_phan_da_tra(): void
    {
        $order = $this->makeCompletedOrder();
        $order->forceFill(['order_payment_status' => 1])->save();
        $doanhThuTruoc = $this->sales();

        $giay = $this->lineOf($order, $this->giay);
        $this->requestReturn($order, [$giay->order_details_id => 1]);
        $this->admin('returns.approve', $order);
        $this->admin('returns.receive', $order);
        $this->admin('returns.refund', $order, ['refund_amount' => 1_000_000]);

        $this->assertSame(1_000_000, $this->wallet());
        $this->assertSame($doanhThuTruoc - 1_000_000, $this->sales());
        $this->assertSame(OrderReturnModel::REFUNDED, OrderReturnModel::firstOrFail()->status);
        $this->assertFalse($order->fresh()->order_refund_required);
    }

    public function test_khong_hoan_duoc_nhieu_hon_gia_tri_hang_tra(): void
    {
        $order = $this->makeCompletedOrder();
        $order->forceFill(['order_payment_status' => 1])->save();

        $giay = $this->lineOf($order, $this->giay);
        $this->requestReturn($order, [$giay->order_details_id => 1]);
        $this->admin('returns.approve', $order);
        $this->admin('returns.receive', $order);

        $this->admin('returns.refund', $order, ['refund_amount' => (int) $order->order_total])
            ->assertSessionHasErrors('refund_amount');

        $this->assertSame(0, $this->wallet());
    }

    public function test_tra_toan_bo_thi_don_thoi_duoc_tinh_la_doanh_thu(): void
    {
        $order = $this->makeCompletedOrder();

        $this->requestReturn($order, [
            $this->lineOf($order, $this->giay)->order_details_id => 2,
            $this->lineOf($order, $this->tat)->order_details_id => 1,
        ]);
        $this->admin('returns.approve', $order);
        $this->admin('returns.receive', $order);
        $this->admin('returns.refund', $order, ['refund_amount' => (int) $order->order_total]);

        $this->assertSame(0, $this->sales());
        $this->assertSame(0, (int) $order->fresh()->order_revenue_counted);
    }

    public function test_tra_mot_phan_van_giu_co_da_ghi_doanh_thu(): void
    {
        $order = $this->makeCompletedOrder();
        $giay = $this->lineOf($order, $this->giay);

        $this->requestReturn($order, [$giay->order_details_id => 1]);
        $this->admin('returns.approve', $order);
        $this->admin('returns.receive', $order);
        $this->admin('returns.refund', $order, ['refund_amount' => 1_000_000]);

        // The kept pair is still a sale, so the order stays on the report and
        // a second reversal must not be able to take it off twice.
        $this->assertSame(1, (int) $order->fresh()->order_revenue_counted);
    }

    // ---------------------------------------------------------- giao diện

    public function test_admin_thay_dong_hang_khach_tra_va_so_tien_phai_hoan(): void
    {
        $order = $this->makeCompletedOrder();
        $giay = $this->lineOf($order, $this->giay);

        $this->requestReturn($order, [$giay->order_details_id => 1]);

        $this->admin ??= tap(
            $this->makeUser(email: 'donhang@example.test', username: 'quanlydon', role: 1),
            fn (UserModel $u) => $u->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web')),
        );

        $this->actingAs($this->admin)
            ->get(route('orders.edit', encrypt($order->order_id)))
            ->assertOk()
            ->assertSee('Trả một phần')
            ->assertSee('1 / 2')
            ->assertSee('1.000.000');
    }
}
