<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Models\WalletTransactionModel;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * What the order page tells the shop about the money.
 *
 * The page printed how the customer chose to pay and stopped there, so an
 * order that had been paid for looked exactly like one that had not.
 */
class AdminOrderPaymentTest extends TestCase
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

    private function makeOrder(string $payment = 'cod'): OrderModel
    {
        $product = $this->makeProduct(slug: 'giay-thanh-toan-'.$payment, stock: 5);

        return app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($product, 1)],
            null,
            $this->makeAddress($this->customer),
            ['payment' => $payment, 'note_customer' => null],
        );
    }

    private function makeOrderAdmin(): UserModel
    {
        $admin = $this->makeUser(email: 'thanhtoan@example.test', username: 'quanlytt', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web'));

        return $admin;
    }

    private function openOrder(OrderModel $order)
    {
        return $this->actingAs($this->makeOrderAdmin())
            ->get(route('orders.edit', encrypt($order->order_id)))
            ->assertOk();
    }

    public function test_don_da_thanh_toan_hien_thoi_diem_nhan_tien(): void
    {
        $order = $this->makeOrder('redirect');
        $luc = Carbon::create(2026, 9, 24, 20, 7);
        $order->forceFill([
            'order_payment_status' => 1,
            'order_payment_time' => $luc,
        ])->save();

        $this->openOrder($order)
            ->assertSee('Đã thanh toán · '.$luc->format('H:i d/m/Y'));
    }

    public function test_don_cong_thanh_toan_chua_tra_tien_thi_noi_chua_thanh_toan(): void
    {
        $this->openOrder($this->makeOrder('redirect'))
            ->assertSee('Chưa thanh toán');
    }

    public function test_don_cod_chua_thu_thi_noi_thu_khi_giao_hang(): void
    {
        $this->openOrder($this->makeOrder('cod'))
            ->assertSee('Thu khi giao hàng');
    }

    public function test_don_tra_bang_vi_in_dung_ten_phuong_thuc(): void
    {
        app(WalletService::class)->credit(
            app(WalletService::class)->for($this->customer),
            5_000_000,
            WalletTransactionModel::TYPE_ADJUSTMENT,
            'Nạp sẵn cho bài kiểm thử',
        );

        // The page ran an if/else over three payment methods and fell through
        // to MoMo, so a wallet order was reported as paid through MoMo.
        $this->openOrder($this->makeOrder('wallet'))
            ->assertSee('Thanh toán bằng SPay')
            ->assertDontSee('Thanh toán qua Momo');
    }
}
