<?php

namespace Tests\Feature;

use App\Mail\CartReminderMail;
use App\Models\CartReminderModel;
use App\Models\CouponModel;
use App\Models\ProductModel;
use App\Models\UserEventModel as Event;
use App\Models\UserModel;
use App\Services\CartReminders\OpenCartBoard;
use App\Services\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The admin's view of the carts left open, and the coupon an admin can give
 * one customer by hand instead of every reminder carrying one.
 */
class OpenCartTest extends TestCase
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

    private function admin(bool $canGiveCoupons = true): UserModel
    {
        $admin = $this->makeUser('admin@example.test', 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web'));

        if ($canGiveCoupons) {
            $admin->givePermissionTo(Permission::findOrCreate('Quản trị Mã giảm giá', 'web'));
        }

        return $admin;
    }

    private function addToCart(UserModel $user, Carbon $at, int $quantity = 1): void
    {
        Event::create([
            'session_id' => str_pad('phien'.$user->user_id, 40, '0'),
            'user_id' => $user->user_id,
            'event_type' => Event::ADD_CART,
            'pro_id' => $this->product->pro_id, 'size_id' => 1, 'color_id' => 1,
            'meta' => ['quantity' => $quantity, 'price' => 1_000_000],
            'created_at' => $at,
        ]);
    }

    /**
     * Both automatic reminders, already sent for the customer's open cart.
     */
    private function remindedTwice(UserModel $user, bool $opened = true): void
    {
        $key = $user->user_id.'-'.Event::where('user_id', $user->user_id)->min('event_id');

        foreach ([CartReminderModel::FIRST, CartReminderModel::SECOND] as $step) {
            CartReminderModel::create([
                'user_id' => $user->user_id, 'cart_key' => $key, 'step' => $step, 'lines' => [],
                'token' => Str::random(40), 'sent_at' => now()->subHours(3 - $step),
                'opened_at' => $opened && $step === CartReminderModel::SECOND ? now() : null,
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function reasonsFor(UserModel $user): array
    {
        $row = collect(app(OpenCartBoard::class)->rows())->first(fn (array $row) => $row['user']->is($user));

        return $row['reasons'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $terms
     */
    private function offer(UserModel $admin, ?UserModel $to = null, array $terms = []): TestResponse
    {
        return $this->actingAs($admin)->post(route('open_cart.offer', ($to ?? $this->customer)->user_id), $terms + [
            'coupon_percent' => 10, 'coupon_days' => 5, 'min_order' => 0, 'max_discount' => null,
        ]);
    }

    // --------------------------------------------------------------- the list

    public function test_trang_liet_ke_gio_dang_treo_sap_xep_theo_gia_tri(): void
    {
        $big = $this->makeUser('lon@example.test', 'gioto');
        $bought = $this->makeUser('damua@example.test', 'damua');
        $this->addToCart($this->customer, now()->subHours(2));
        $this->addToCart($big, now()->subDays(2), quantity: 3);
        $this->addToCart($bought, now()->subHours(3));
        Event::create([
            'session_id' => str_repeat('b', 40), 'user_id' => $bought->user_id,
            'event_type' => Event::PLACE_ORDER, 'created_at' => now()->subHour(),
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('open_cart.index'))
            ->assertOk()
            ->assertSeeInOrder(['lon@example.test', '3.000.000đ', 'khach@example.test', '1.000.000đ'])
            ->assertDontSee('damua@example.test');

        $this->actingAs($admin)->get(route('open_cart.index', ['sap-xep' => OpenCartBoard::SORT_RECENT]))
            ->assertSeeInOrder(['khach@example.test', 'lon@example.test']);
    }

    public function test_de_xuat_tang_ma_khi_da_nhac_het_ma_khach_co_mo_email(): void
    {
        // Someone else's reminders first, so this cart's are not ids 1 and 2.
        $this->remindedTwice($this->makeUser('khac@example.test', 'khachkhac'), opened: false);
        $this->addToCart($this->customer, now()->subDays(2), quantity: 2);
        $this->remindedTwice($this->customer);

        $this->assertSame(['Đã nhắc 2 lần, chưa mua', 'Có mở email', 'Giỏ từ 1.000.000đ'], $this->reasonsFor($this->customer));

        $this->actingAs($this->admin())->get(route('open_cart.index', ['de-xuat' => 1]))
            ->assertSee('khach@example.test')
            ->assertSee('Đề xuất')
            ->assertSee('Có mở ít nhất một email nhắc', false)
            ->assertSee(route('setting.edit').'#tab-nhac-gio-hang', false);
    }

    public function test_khong_de_xuat_khi_chua_nhac_het_khong_mo_email_hoac_gio_nho(): void
    {
        $this->addToCart($this->customer, now()->subDays(2), quantity: 2);
        $this->assertSame([], $this->reasonsFor($this->customer), 'Nhắc thường chưa chạy hết thì chưa cần giảm giá');

        $this->remindedTwice($this->customer, opened: false);
        $this->assertSame([], $this->reasonsFor($this->customer), 'Không mở email thì cũng không mở mã');

        CartReminderModel::query()->update(['opened_at' => now()]);
        app(ShopSettings::class)->setCartReminder(['offer_min_value' => 3_000_000]);
        $this->assertSame([], $this->reasonsFor($this->customer), 'Giỏ 2.000.000đ dưới mức đề xuất');
    }

    public function test_khach_vua_nhan_ma_gan_day_khong_duoc_de_xuat_lai(): void
    {
        $this->addToCart($this->customer, now()->subDays(2), quantity: 2);
        $this->remindedTwice($this->customer);
        CartReminderModel::create([
            'user_id' => $this->customer->user_id, 'cart_key' => 'gio-truoc', 'step' => CartReminderModel::OFFER,
            'lines' => [], 'token' => Str::random(40), 'sent_at' => now()->subDays(30),
        ]);

        $this->assertSame([], $this->reasonsFor($this->customer));

        app(ShopSettings::class)->setCartReminder(['offer_cooldown_days' => 20]);
        $this->assertNotSame([], $this->reasonsFor($this->customer));
    }

    public function test_gio_nhieu_san_pham_co_nut_xem_them_va_the_ton_kho_tung_san_pham(): void
    {
        $products = [$this->product];
        foreach (['giay-hai', 'giay-ba', 'giay-bon', 'giay-nam'] as $slug) {
            $products[] = $this->makeProduct(price: 2_000_000, salePrice: 1_500_000, stock: 4, slug: $slug);
        }
        // Ids out of size order, so the card has to sort by the size itself.
        DB::table('size')->insert([['size_id' => 2, 'size' => '43', 'size_hidden' => 1], ['size_id' => 3, 'size' => '41', 'size_hidden' => 1]]);
        $this->addVariant($products[1], colorId: 1, sizeId: 2, stock: 0);
        $this->addVariant($products[1], colorId: 1, sizeId: 3, stock: 0);
        foreach ($products as $i => $product) {
            Event::create([
                'session_id' => str_pad('phien', 40, '0'), 'user_id' => $this->customer->user_id,
                'event_type' => Event::ADD_CART, 'pro_id' => $product->pro_id, 'size_id' => 1, 'color_id' => 1,
                'meta' => ['quantity' => 1], 'created_at' => now()->subHours(5 - $i),
            ]);
        }

        $page = $this->actingAs($this->admin())->get(route('open_cart.index'))->assertOk();

        $page->assertSee('Xem thêm 2 sản phẩm')->assertSee('Thu gọn');
        foreach ($products as $product) {
            $page->assertSee($product->pro_name);
        }
        $page->assertSee('Còn 4 đôi trong kho')
            ->assertSeeInOrder(['41: 0', '42: 4', '43: 0'], false)
            ->assertSee('1.500.000đ')
            ->assertSee('bg-warning text-dark', false);
    }

    // ------------------------------------------------------------- the offer

    public function test_admin_tang_ma_gui_email_ngay_va_ma_duoc_ap_san_khi_khach_bam(): void
    {
        $this->addToCart($this->customer, now()->subHour(), quantity: 2);
        $admin = $this->admin();

        $this->offer($admin)->assertRedirect()->assertSessionHas('message', 'Đã tặng mã và gửi email cho khách.');

        $offer = CartReminderModel::where('step', CartReminderModel::OFFER)->firstOrFail();
        $coupon = CouponModel::findOrFail($offer->coupon_id);
        $this->assertSame($admin->user_id, $offer->sent_by);
        $this->assertSame([10, 1, 0], [(int) $coupon->coupon_value, (int) $coupon->coupon_quantity, (int) $coupon->coupon_condition]);
        $this->assertSame(now()->addDays(5)->toDateString(), Carbon::parse($coupon->coupon_end)->toDateString());
        Mail::assertQueued(CartReminderMail::class, fn (CartReminderMail $mail) => $mail->hasTo('khach@example.test')
            && $mail->reminder->is($offer));
        (new CartReminderMail($offer))->assertSeeInHtml($coupon->coupon_code);

        $this->get(route('cart.restore', $offer->token))->assertRedirect(route('product.cart'));

        $this->assertSame($coupon->coupon_id, session('coupon_data')->coupon_id);
    }

    public function test_ma_tang_mang_dieu_kien_admin_dat_cho_rieng_gio_do(): void
    {
        $this->addToCart($this->customer, now()->subHour(), quantity: 2);

        $this->offer($this->admin(), terms: ['coupon_percent' => 5, 'min_order' => 1_800_000, 'max_discount' => 80_000]);

        $offer = CartReminderModel::where('step', CartReminderModel::OFFER)->firstOrFail();
        $coupon = CouponModel::findOrFail($offer->coupon_id);
        $this->assertSame([5, 1_800_000, 80_000], [(int) $coupon->coupon_value, (int) $coupon->coupon_min_order, (int) $coupon->coupon_max_discount]);
        (new CartReminderMail($offer))->assertSeeInHtml('đơn từ 1.800.000 VNĐ · giảm tối đa 80.000 VNĐ');
    }

    public function test_trang_goi_y_don_toi_thieu_bang_90_phan_tram_gio(): void
    {
        $this->addToCart($this->customer, now()->subHour(), quantity: 2);

        $this->actingAs($this->admin())->get(route('open_cart.index'))
            ->assertSee('data-min-order="1800000"', false);
    }

    public function test_da_tang_ma_thi_ngung_nhac_tu_dong(): void
    {
        $this->addToCart($this->customer, now()->subHours(30));
        $this->offer($this->admin());

        $this->artisan('carts:remind')->assertSuccessful();

        $this->assertSame([CartReminderModel::OFFER], CartReminderModel::pluck('step')->all());
    }

    public function test_khong_tang_ma_hai_lan_cho_mot_gio_hay_cho_khach_da_huy_nhan(): void
    {
        $quitter = $this->makeUser('huy@example.test', 'huynhan');
        $this->addToCart($this->customer, now()->subHour());
        $this->addToCart($quitter, now()->subHour());
        DB::table('cart_reminder_opt_outs')->insert(['user_id' => $quitter->user_id, 'created_at' => now()]);
        $admin = $this->admin();

        $this->offer($admin);
        $this->offer($admin)->assertSessionHas('message', 'Giỏ hàng này đã được tặng mã.');
        $this->offer($admin, $quitter)->assertSessionHas('message', 'Khách đã huỷ nhận email nhắc giỏ hàng.');

        $this->assertSame(1, CouponModel::count());
        Mail::assertQueuedCount(1);
    }

    public function test_gio_da_dat_hang_thi_khong_tang_ma(): void
    {
        $this->addToCart($this->customer, now()->subHours(2));
        Event::create([
            'session_id' => str_repeat('b', 40), 'user_id' => $this->customer->user_id,
            'event_type' => Event::PLACE_ORDER, 'created_at' => now()->subHour(),
        ]);

        $this->offer($this->admin())->assertSessionHas('message', 'Khách đã đặt hàng hoặc bỏ hết sản phẩm khỏi giỏ.');

        $this->assertSame(0, CouponModel::count());
    }

    public function test_tang_ma_can_them_quyen_quan_tri_ma_giam_gia(): void
    {
        $this->addToCart($this->customer, now()->subHour());
        $admin = $this->admin(canGiveCoupons: false);

        $this->actingAs($admin)->get(route('open_cart.index'))->assertOk()->assertSee('Cần quyền Quản trị Mã giảm giá');
        $this->offer($admin)->assertForbidden();

        $this->assertSame(0, CouponModel::count());
        Mail::assertNothingQueued();
    }

    public function test_muc_giam_ngoai_gioi_han_bi_tu_choi(): void
    {
        $this->addToCart($this->customer, now()->subHour());

        $this->offer($this->admin(), terms: ['coupon_percent' => 50, 'min_order' => -1, 'max_discount' => 10])
            ->assertSessionHasErrors(['coupon_percent', 'min_order', 'max_discount']);

        $this->assertSame(0, CouponModel::count());
    }
}
