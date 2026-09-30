<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Models\OrderModel;
use App\Models\UserModel;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Orders are private to the customer who placed them, and nothing may
 * rewrite or erase one behind the order flow's back.
 */
class OrderSafetyTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $owner;

    private OrderModel $order;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedLookupTables();
        $this->owner = $this->makeUser();
        $this->order = app(PlaceOrderAction::class)->execute(
            $this->owner,
            [$this->cartLine($this->makeProduct(stock: 10))],
            null,
            $this->makeAddress($this->owner),
            ['payment' => 'cod', 'note_customer' => null],
        );
    }

    private function stranger(): UserModel
    {
        return $this->makeUser(email: uniqid().'@example.test', username: uniqid('kh'));
    }

    public function test_khach_khac_khong_xem_duoc_don_cua_minh(): void
    {
        $this->actingAs($this->stranger())
            ->get(route('orderBill.checkout', $this->order->order_code))
            ->assertRedirect()
            ->assertSessionHas('message', 'Không tồn tại đơn hàng này !');

        $this->actingAs($this->owner)
            ->get(route('orderBill.checkout', $this->order->order_code))
            ->assertOk()
            ->assertSee($this->order->order_address);
    }

    public function test_khach_khac_khong_in_duoc_don_cua_minh(): void
    {
        $this->actingAs($this->stranger())
            ->get(route('printBill.checkout', $this->order->order_code))
            ->assertRedirect()
            ->assertSessionHas('message', 'Bạn chỉ được quyền xuất hóa đơn của bạn !');
    }

    public function test_in_ma_don_khong_ton_tai_khong_bao_loi(): void
    {
        $this->actingAs($this->owner)
            ->get(route('printBill.checkout', 'KHONGCO'))
            ->assertRedirect();
    }

    public function test_khong_con_route_ghi_thang_trang_thai_don(): void
    {
        $this->assertFalse(Route::has('update.order'));
    }

    public function test_khong_con_job_xoa_don_cu(): void
    {
        $this->assertArrayNotHasKey('order-detele', Artisan::all());

        $lich = collect(app(Schedule::class)->events())->map(fn ($event) => (string) $event->command);

        // The schedule is loaded, or the check below would pass on nothing.
        $this->assertTrue($lich->contains(fn ($lenh) => str_contains($lenh, 'orders:expire-unpaid')));
        $this->assertFalse($lich->contains(fn ($lenh) => str_contains($lenh, 'order-detele')));
    }
}
