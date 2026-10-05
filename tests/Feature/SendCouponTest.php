<?php

namespace Tests\Feature;

use App\Mail\CouponMail;
use App\Models\CouponModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Sending a coupon to one group of customers, picked by how many orders each
 * has placed.
 */
class SendCouponTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private CouponModel $coupon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coupon = CouponModel::create([
            'coupon_name' => 'Tri ân', 'coupon_code' => 'TRIAN', 'coupon_value' => 10, 'coupon_quantity' => 100,
            'coupon_used' => 0, 'coupon_condition' => 2, 'coupon_date' => '2026-10-01',
            'coupon_start' => '2026-10-01', 'coupon_end' => '2026-12-31',
        ]);
    }

    private function customerWithOrders(string $email, int $orders): UserModel
    {
        $user = $this->makeUser(email: $email, username: strtok($email, '@'));

        for ($i = 0; $i < $orders; $i++) {
            DB::table('order')->insert([
                'order_code' => uniqid('SS'), 'order_name' => 'Khách', 'order_email' => $email,
                'order_address' => '1 Lê Lợi', 'order_local' => 'Quận 1', 'order_phone' => '0900000000',
                'order_delivery_fee' => 0, 'order_total' => 100_000, 'order_payment' => 'cod',
                'order_payment_status' => 0, 'order_date' => '2026-10-01', 'user_id' => $user->user_id,
            ]);
        }

        return $user;
    }

    /**
     * @return list<string>
     */
    private function sendTo(int $audience): array
    {
        Mail::fake();
        $admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Mã giảm giá', 'web'));

        $this->actingAs($admin)
            ->post(route('sendCoupon'), ['coupon' => $audience, 'couid' => $this->coupon->coupon_id])
            ->assertSessionHas('message', 'Gửi mã giảm thành công!');

        $sent = [];
        Mail::assertQueued(CouponMail::class, function (CouponMail $mail) use (&$sent) {
            $sent[] = $mail->to[0]['address'];

            return true;
        });
        sort($sent);

        return $sent;
    }

    private function seedCustomers(): void
    {
        $this->customerWithOrders('chua-mua@example.test', 0);
        $this->customerWithOrders('mot-don@example.test', 1);
        $this->customerWithOrders('ba-don@example.test', 3);
        $this->customerWithOrders('sau-don@example.test', 6);
    }

    public function test_gui_cho_moi_khach_hang(): void
    {
        $this->seedCustomers();

        $this->assertSame(
            ['ba-don@example.test', 'chua-mua@example.test', 'mot-don@example.test', 'sau-don@example.test'],
            $this->sendTo(1)
        );
    }

    public function test_gui_cho_khach_moi_mua_mot_don(): void
    {
        $this->seedCustomers();

        $this->assertSame(['mot-don@example.test'], $this->sendTo(2));
    }

    public function test_gui_cho_khach_mua_tu_hai_den_nam_don(): void
    {
        $this->seedCustomers();

        $this->assertSame(['ba-don@example.test'], $this->sendTo(3));
    }

    public function test_gui_cho_khach_mua_tren_nam_don(): void
    {
        $this->seedCustomers();

        $this->assertSame(['sau-don@example.test'], $this->sendTo(4));
    }
}
