<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The four steps at the top of the customer's order page: which one is done,
 * which one the order is waiting on, and what each says underneath.
 */
class OrderProgressTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedLookupTables();
        $this->customer = $this->makeUser();
    }

    private function placeOrder(string $payment): OrderModel
    {
        return app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($this->makeProduct(slug: 'giay-'.uniqid(), stock: 10))],
            null,
            $this->makeAddress($this->customer),
            ['payment' => $payment, 'note_customer' => null],
        );
    }

    /**
     * @return list<array{string, string, string}> state, label and the line under it, per step
     */
    private function steps(OrderModel $order): array
    {
        $html = $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->getContent();

        preg_match_all(
            '#<li class="order-step ([a-z ]+)".*?<div class="order-step-label">(.*?)</div>\s*<div class="order-step-meta">(.*?)</div>\s*</li>#s',
            $html,
            $matches,
            PREG_SET_ORDER,
        );

        return array_map(fn ($m) => [
            trim($m[1]),
            trim($m[2]),
            trim(preg_replace('/\s+/', ' ', strip_tags($m[3]))),
        ], $matches);
    }

    public function test_don_chua_thanh_toan_dung_o_buoc_cho_thanh_toan_kem_han_va_nut_tra(): void
    {
        $this->freezeSecond();
        $order = $this->placeOrder('redirect');

        $steps = $this->steps($order);

        $this->assertCount(4, $steps);
        $this->assertSame(['done', 'Đã đặt hàng'], array_slice($steps[0], 0, 2));
        $this->assertSame(
            ['current warn', 'Chờ thanh toán', 'Hạn '.$order->fresh()->order_payment_due_at->format('H:i').' · Thanh toán ngay'],
            $steps[1],
        );
        $this->assertSame(['todo', 'Chuẩn bị hàng', ''], $steps[2]);
        $this->assertSame(['todo', 'Nhận hàng'], array_slice($steps[3], 0, 2));
    }

    public function test_da_thanh_toan_thi_chi_ghi_gio_va_sang_buoc_chuan_bi(): void
    {
        $order = $this->placeOrder('redirect');
        $order->forceFill([
            'order_payment_status' => 1,
            'order_payment_time' => Carbon::parse('2026-09-29 16:35'),
        ])->save();

        $steps = $this->steps($order);

        $this->assertSame(['done', 'Đã thanh toán', '16:35 29/09/2026'], $steps[1]);
        $this->assertSame(['current', 'Đang chuẩn bị hàng', 'Xem hành trình'], $steps[2]);
    }

    public function test_don_cod_moi_cho_cua_hang_xac_nhan(): void
    {
        $order = $this->placeOrder('cod');

        $steps = $this->steps($order);

        $this->assertSame(['current', 'Chờ xác nhận', 'Thanh toán khi nhận hàng'], $steps[1]);
        $this->assertSame(['todo', 'Chuẩn bị hàng', ''], $steps[2]);
    }

    public function test_da_giao_thi_cho_khach_xac_nhan_nhan_hang(): void
    {
        $order = $this->placeOrder('cod');
        $order->forceFill([
            'order_status' => OrderStatus::Delivered,
            'order_delivery_status' => 1,
            'order_shipping_status' => 'delivered',
            'order_delivered_at' => Carbon::parse('2026-09-30 10:05'),
        ])->save();

        $steps = $this->steps($order);

        $this->assertSame(['done', 'Đã xác nhận', 'Thanh toán khi nhận hàng'], $steps[1]);
        $this->assertSame(['done', 'Đã giao hàng', 'Xem hành trình'], $steps[2]);
        $this->assertSame(['current', 'Chờ bạn xác nhận', 'Đã giao 10:05 30/09/2026'], $steps[3]);
    }

    public function test_don_hoan_thanh_thi_ca_bon_buoc_xong(): void
    {
        $order = $this->placeOrder('redirect');
        $order->forceFill([
            'order_status' => OrderStatus::Completed,
            'order_payment_status' => 1,
            'order_payment_time' => Carbon::parse('2026-09-29 16:35'),
            'order_delivered_at' => Carbon::parse('2026-09-30 10:05'),
            'order_completed_at' => Carbon::parse('2026-10-01 09:12'),
        ])->save();

        $steps = $this->steps($order);

        $this->assertSame(['done', 'done', 'done', 'done'], array_column($steps, 0));
        $this->assertSame(['done', 'Đã nhận hàng', '09:12 01/10/2026'], $steps[3]);
    }
}
