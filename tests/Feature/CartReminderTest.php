<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Mail\CartReminderMail;
use App\Models\CartReminderModel;
use App\Models\CouponModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\UserEventModel as Event;
use App\Models\UserModel;
use App\Services\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Reminding an account of the cart it left, at most twice and never with a
 * coupon, and what the links in that email do.
 */
class CartReminderTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $customer;

    private ProductModel $product;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedLookupTables();
        $this->customer = $this->makeUser();
        $this->product = $this->makeProduct(slug: 'giay-trong-gio');
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function event(string $type, Carbon $at, array $fields = [], ?int $userId = null): void
    {
        Event::create($fields + [
            'session_id' => str_pad('phien', 40, '0'),
            'user_id' => $userId ?? $this->customer->user_id,
            'event_type' => $type,
            'created_at' => $at,
        ]);
    }

    private function addToCart(Carbon $at, int $quantity = 1, ?int $userId = null): void
    {
        $this->event(Event::ADD_CART, $at, [
            'pro_id' => $this->product->pro_id, 'size_id' => 1, 'color_id' => 1,
            'meta' => ['quantity' => $quantity, 'price' => 1_000_000],
        ], $userId);
    }

    private function remind(): void
    {
        $this->artisan('carts:remind')->assertSuccessful();
    }

    private function sent(int $step = CartReminderModel::FIRST): CartReminderModel
    {
        return CartReminderModel::where('step', $step)->firstOrFail();
    }

    // ------------------------------------------------------------- who and when

    public function test_gio_de_qua_so_gio_cau_hinh_thi_gui_lan_nhac_dau(): void
    {
        $this->addToCart(now()->subHours(5), quantity: 2);

        $this->remind();

        $reminder = $this->sent();
        $this->assertSame([['pro_id' => $this->product->pro_id, 'size_id' => 1, 'color_id' => 1, 'quantity' => 2]], $reminder->lines);
        $this->assertSame(2_000_000, (int) $reminder->cart_value);
        $this->assertNull($reminder->coupon_id, 'Lần nhắc đầu không kèm mã giảm giá');
        Mail::assertQueued(CartReminderMail::class, fn (CartReminderMail $mail) => $mail->hasTo('khach@example.test'));
    }

    public function test_chua_du_gio_thi_chua_nhac(): void
    {
        $this->addToCart(now()->subHours(3));

        $this->remind();

        $this->assertSame(0, CartReminderModel::count());
        Mail::assertNothingQueued();
    }

    public function test_da_dat_hang_thi_khong_nhac(): void
    {
        $this->addToCart(now()->subHours(6));
        $this->event(Event::PLACE_ORDER, now()->subHours(5));

        $this->remind();

        $this->assertSame(0, CartReminderModel::count());
    }

    public function test_don_dat_khong_qua_nhat_ky_van_dung_viec_nhac(): void
    {
        $this->addToCart(now()->subHours(6));
        $order = OrderModel::create([
            'order_code' => 'DH1234567', 'order_name' => 'A', 'order_email' => 'khach@example.test',
            'order_address' => '1 Võ Văn Ngân', 'order_local' => 'Thủ Đức', 'order_phone' => '0912345678',
            'order_delivery_fee' => 0, 'order_coupon_value' => 0, 'order_total' => 1_000_000,
            'order_payment' => 'cod', 'order_payment_status' => 0, 'order_date' => now()->toDateString(),
            'order_status' => OrderStatus::New, 'user_id' => $this->customer->user_id,
        ]);
        $order->created_at = now()->subHours(2);
        $order->save();

        $this->remind();

        $this->assertSame(0, CartReminderModel::count());
    }

    public function test_bo_het_san_pham_khoi_gio_thi_khong_nhac(): void
    {
        $this->addToCart(now()->subHours(6));
        $this->event(Event::REMOVE_CART, now()->subHours(5), ['pro_id' => $this->product->pro_id, 'size_id' => 1, 'color_id' => 1]);

        $this->remind();

        $this->assertSame(0, CartReminderModel::count());
    }

    public function test_khach_vang_lai_va_gio_qua_cu_khong_duoc_nhac(): void
    {
        $this->event(Event::ADD_CART, now()->subHours(6), ['pro_id' => $this->product->pro_id, 'size_id' => 1, 'color_id' => 1], userId: null);
        Event::query()->update(['user_id' => null]);
        $old = $this->makeUser('cu@example.test', 'khachcu');
        $this->addToCart(now()->subDays(8), userId: $old->user_id);

        $this->remind();

        $this->assertSame(0, CartReminderModel::count());
    }

    public function test_het_hang_toan_bo_gio_thi_khong_gui(): void
    {
        DB::table('products_quantity')->update(['quantity' => 0]);
        $this->addToCart(now()->subHours(6));

        $this->remind();

        $this->assertSame(0, CartReminderModel::count());
        Mail::assertNothingQueued();
    }

    // ---------------------------------------------------------- the second one

    public function test_lan_hai_khong_kem_ma_giam_gia(): void
    {
        $this->addToCart(now()->subHours(30));
        $this->travelTo(now()->subHours(25));
        $this->remind();
        $this->travelBack();

        $this->remind();

        $second = $this->sent(CartReminderModel::SECOND);
        $this->assertNull($second->coupon_id, 'Tự động tặng mã sẽ dạy khách bỏ giỏ để chờ mã');
        $this->assertSame(0, CouponModel::count());
        $this->assertSame($this->sent()->cart_key, $second->cart_key);
    }

    public function test_lan_hai_khong_den_qua_som_sau_lan_dau(): void
    {
        // Left 30 hours ago, but the first reminder went out only now.
        $this->addToCart(now()->subHours(30));

        $this->remind();
        $this->remind();

        $this->assertSame(1, CartReminderModel::count());
    }

    public function test_khong_bao_gio_nhac_qua_hai_lan(): void
    {
        $this->addToCart(now()->subDays(3));
        $this->travelTo(now()->subDays(2));
        $this->remind();
        $this->travelBack();
        $this->remind();
        $this->remind();

        $this->assertSame(2, CartReminderModel::count());
    }

    public function test_cau_hinh_tat_lan_hai_va_tat_ca_tinh_nang(): void
    {
        app(ShopSettings::class)->setCartReminder(['second_enabled' => false]);
        $this->addToCart(now()->subDays(3));
        $this->travelTo(now()->subDays(2));
        $this->remind();
        $this->travelBack();
        $this->remind();

        $this->assertSame(1, CartReminderModel::count());

        app(ShopSettings::class)->setCartReminder(['enabled' => false]);
        $other = $this->makeUser('khac@example.test', 'khachkhac');
        $this->addToCart(now()->subHours(5), userId: $other->user_id);
        $this->remind();

        $this->assertSame(1, CartReminderModel::count());
    }

    // ------------------------------------------------------------- the links

    public function test_bam_nut_trong_mail_khoi_phuc_gio(): void
    {
        $this->addToCart(now()->subHours(5), quantity: 2);
        $this->remind();
        $reminder = $this->sent();

        $this->get(route('cart.restore', $reminder->token))->assertRedirect(route('product.cart'));

        $cart = session('cart');
        $this->assertCount(1, $cart);
        $this->assertSame(['giay-trong-gio', 2, 1_000_000], [$cart[0]['proSlug'], $cart[0]['quantity'], $cart[0]['pro_price']]);
        $this->assertNull(session('coupon_data'));
        $this->assertNotNull($reminder->fresh()->clicked_at);
        $this->assertNotNull($reminder->fresh()->opened_at, 'Đã bấm nghĩa là đã mở');
    }

    public function test_khoi_phuc_khong_nhan_doi_san_pham_da_co_trong_gio(): void
    {
        $this->addToCart(now()->subHours(5), quantity: 2);
        $this->remind();

        $this->withSession(['cart' => [[
            'proSlug' => 'giay-trong-gio', 'pro_name' => $this->product->pro_name, 'quantity' => 1,
            'color_id' => 1, 'size_id' => 1, 'pro_price' => 1_000_000,
        ]]])->get(route('cart.restore', $this->sent()->token));

        $this->assertSame(2, session('cart')[0]['quantity']);
        $this->assertCount(1, session('cart'));
    }

    public function test_mo_mail_ghi_nhan_mot_lan(): void
    {
        $this->addToCart(now()->subHours(5));
        $this->remind();
        $token = $this->sent()->token;

        $this->get(route('cart.reminder.opened', $token))->assertOk()->assertHeader('Content-Type', 'image/gif');
        $first = $this->sent()->opened_at;
        $this->travel(1)->hours();
        $this->get(route('cart.reminder.opened', $token));

        $this->assertEquals($first, $this->sent()->opened_at);
    }

    public function test_token_sai_khong_lam_gi(): void
    {
        $this->get(route('cart.restore', str_repeat('x', 40)))->assertNotFound();
        $this->post(route('cart.reminder.unsubscribe.confirm', str_repeat('x', 40)))->assertNotFound();
    }

    public function test_huy_nhan_can_xac_nhan_roi_moi_ngung_nhac(): void
    {
        $this->addToCart(now()->subDays(3));
        $this->travelTo(now()->subDays(2));
        $this->remind();
        $this->travelBack();
        $token = $this->sent()->token;

        $this->get(route('cart.reminder.unsubscribe', $token))->assertOk()->assertSee('Huỷ nhận email nhắc giỏ hàng');
        $this->assertSame(0, DB::table('cart_reminder_opt_outs')->count(), 'Mở liên kết chưa được tính là huỷ');

        $this->post(route('cart.reminder.unsubscribe.confirm', $token))->assertOk()->assertSee('Bạn đã huỷ nhận');
        $this->remind();

        $this->assertSame(1, CartReminderModel::count());
    }

    public function test_email_liet_ke_dung_san_pham_va_co_lien_ket(): void
    {
        $this->addToCart(now()->subHours(5), quantity: 2);
        $this->remind();
        $reminder = $this->sent();

        $mail = new CartReminderMail($reminder);

        $mail->assertSeeInHtml($this->product->pro_name);
        $mail->assertSeeInHtml('Size 42');
        $mail->assertSeeInHtml('2.000.000');
        $mail->assertSeeInHtml(route('cart.restore', $reminder->token));
        $mail->assertSeeInHtml(route('cart.reminder.unsubscribe', $reminder->token));
        $mail->assertDontSeeInHtml('Tặng riêng bạn mã giảm');
    }

    // ------------------------------------------------------------ admin config

    public function test_admin_luu_cau_hinh_nhac_gio_hang(): void
    {
        $admin = $this->makeUser('admin@example.test', 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web'));

        $this->actingAs($admin)->get(route('setting.edit'))->assertOk()->assertSee('Email nhắc giỏ hàng bỏ quên');

        $this->actingAs($admin)->put(route('setting.update_cart_reminder'), [
            'enabled' => '1', 'first_hours' => 2, 'second_hours' => 2, 'max_age_days' => 7,
            'coupon_percent' => 10, 'coupon_days' => 5, 'offer_min_value' => 2_000_000, 'offer_cooldown_days' => 60,
        ])->assertSessionHasErrors('second_hours');

        $this->actingAs($admin)->put(route('setting.update_cart_reminder'), [
            'enabled' => '1', 'first_hours' => 2, 'second_hours' => 48, 'max_age_days' => 14,
            'coupon_percent' => 10, 'coupon_days' => 5, 'offer_min_value' => 2_000_000, 'offer_cooldown_days' => 60,
        ])->assertRedirect();

        $this->assertSame([
            'enabled' => true, 'first_hours' => 2, 'second_enabled' => false, 'second_hours' => 48, 'max_age_days' => 14,
            'coupon_percent' => 10, 'coupon_days' => 5, 'offer_min_value' => 2_000_000, 'offer_cooldown_days' => 60,
        ], app(ShopSettings::class)->cartReminder());
    }
}
