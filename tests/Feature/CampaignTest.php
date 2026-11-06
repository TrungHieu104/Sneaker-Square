<?php

namespace Tests\Feature;

use App\Enums\CustomerSegment;
use App\Enums\OrderStatus;
use App\Mail\CampaignMail;
use App\Models\CampaignModel;
use App\Models\CampaignRecipientModel;
use App\Models\CouponModel;
use App\Models\UserModel;
use App\Services\CustomerSegments\SegmentReport;
use App\Services\SampleData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Tests\Support\SegmentFixtures;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * An admin picks a segment and sends it a coupon; each customer gets a code of
 * their own, and the orders using those codes are the campaign's result.
 */
class CampaignTest extends TestCase
{
    use RefreshDatabase;
    use SegmentFixtures;
    use ShopFixtures;

    private UserModel $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedLookupTables();
        $this->admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $this->admin->givePermissionTo(
            Permission::findOrCreate('Thống kê truy cập', 'web'),
            Permission::findOrCreate('Quản trị Mã giảm giá', 'web'),
        );
    }

    private function atRisk(): UserModel
    {
        $customer = $this->customer();
        $this->inSegment($customer, CustomerSegment::AtRisk);

        return $customer;
    }

    /**
     * @return array<string, mixed>
     */
    private function form(): array
    {
        return [
            'name' => 'Mời khách cũ quay lại', 'percent' => 15, 'max_discount' => 200_000, 'days' => 7, 'subject' => 'Tặng bạn mã giảm giá', 'message' => 'Đã lâu bạn chưa ghé.',
            'recipients' => DB::table('customer_segments')->pluck('user_id')->all(),
        ];
    }

    private function send(CustomerSegment $segment = CustomerSegment::AtRisk): TestResponse
    {
        return $this->actingAs($this->admin)->post(route('admin.campaigns.store', $segment), $this->form());
    }

    public function test_form_dien_san_theo_nhom_va_khong_nhac_ten_nhom_trong_email(): void
    {
        $this->atRisk();

        $this->actingAs($this->admin)->get(route('admin.campaigns.create', CustomerSegment::AtRisk))
            ->assertOk()
            ->assertSee('Mời khách cũ quay lại')
            ->assertSee('value="15"', false)
            ->assertSee('Gửi cho 1 khách')
            ->assertSeeInOrder(['Xem trước email', 'Đã lâu bạn chưa ghé', '15%']);
    }

    public function test_gui_cho_dung_nhom_moi_khach_mot_ma_rieng(): void
    {
        $first = $this->atRisk();
        $second = $this->atRisk();
        $other = $this->customer();
        $this->inSegment($other, CustomerSegment::Loyal);

        $this->send()->assertRedirect(route('admin.segments'));

        $this->assertSame(1, CampaignModel::count());
        $this->assertEqualsCanonicalizing([$first->user_id, $second->user_id], CampaignRecipientModel::pluck('user_id')->all());
        Mail::assertQueued(CampaignMail::class, 2);
        Mail::assertNotQueued(CampaignMail::class, fn (CampaignMail $mail) => $mail->hasTo($other->email));

        $coupons = CouponModel::where('coupon_code', 'like', 'UUDAI%')->get();
        $this->assertCount(2, $coupons->unique('coupon_code'));
        $this->assertSame(15, (int) $coupons[0]->coupon_value);
        $this->assertSame(200_000, (int) $coupons[0]->coupon_max_discount);
        $this->assertSame(1, (int) $coupons[0]->coupon_quantity);
        $this->assertSame(now()->addDays(7)->toDateString(), $coupons[0]->coupon_end);
    }

    public function test_bo_qua_khach_da_huy_nhan_email(): void
    {
        $optedOut = $this->atRisk();
        DB::table('campaign_opt_outs')->insert(['user_id' => $optedOut->user_id, 'created_at' => now()]);
        $noReminders = $this->atRisk();
        DB::table('cart_reminder_opt_outs')->insert(['user_id' => $noReminders->user_id, 'created_at' => now()]);
        $this->atRisk();

        $this->send();

        Mail::assertQueued(CampaignMail::class, 1);
    }

    public function test_bam_gui_hai_lan_khong_gui_trung(): void
    {
        $this->atRisk();

        $this->send();
        $this->send()->assertSessionHas('message', fn (string $message) => str_contains($message, 'Không có khách nào'));

        Mail::assertQueued(CampaignMail::class, 1);
        $this->assertSame(1, CampaignModel::count());
    }

    public function test_het_thoi_gian_nghi_thi_gui_lai_duoc(): void
    {
        $this->atRisk();
        $this->send();
        CampaignRecipientModel::query()->update(['sent_at' => now()->subDays(8)]);

        $this->send();

        $this->assertSame(2, CampaignModel::count());
    }

    public function test_tai_khoan_mau_khong_gui_mail_that(): void
    {
        $sample = $this->makeUser(email: 'khachmau01'.SampleData::EMAIL_DOMAIN, username: 'khachmau01');
        $this->inSegment($sample, CustomerSegment::AtRisk);

        $this->send();

        Mail::assertNothingQueued();
        $this->assertSame(1, CampaignRecipientModel::count());
    }

    public function test_can_quyen_ma_giam_gia_de_gui(): void
    {
        $viewer = $this->makeUser(email: 'xem@example.test', username: 'chixem', role: 1);
        $viewer->givePermissionTo('Thống kê truy cập');
        $this->atRisk();

        $this->actingAs($viewer)->post(route('admin.campaigns.store', CustomerSegment::AtRisk), $this->form())->assertForbidden();

        Mail::assertNothingQueued();
    }

    public function test_muc_giam_ngoai_gioi_han_bi_tu_choi(): void
    {
        $this->atRisk();

        $this->actingAs($this->admin)->post(route('admin.campaigns.store', CustomerSegment::AtRisk), ['percent' => 90] + $this->form())
            ->assertSessionHasErrors('percent');

        Mail::assertNothingQueued();
    }

    public function test_form_cho_xem_khach_nhan_uu_dai(): void
    {
        $customer = $this->atRisk();

        $this->actingAs($this->admin)->get(route('admin.campaigns.create', CustomerSegment::AtRisk))
            ->assertOk()
            ->assertSee('Xem và chọn khách')
            ->assertSee($customer->email)
            ->assertSeeInOrder([$customer->name, '10 ngày trước', '2.000.000 đ'])
            ->assertDontSee('Hệ thống điền sẵn');
    }

    public function test_form_bao_so_khach_khong_co_trong_danh_sach(): void
    {
        $optedOut = $this->atRisk();
        DB::table('campaign_opt_outs')->insert(['user_id' => $optedOut->user_id, 'created_at' => now()]);
        $this->atRisk();

        $this->actingAs($this->admin)->get(route('admin.campaigns.create', CustomerSegment::AtRisk))
            ->assertSee('Gửi cho 1 khách')
            ->assertDontSee($optedOut->email)
            ->assertSee('1 khách khác của nhóm không có trong danh sách');
    }

    public function test_bo_chon_khach_thi_khach_do_khong_nhan_trong_dot_nay(): void
    {
        $kept = $this->atRisk();
        $left = $this->atRisk();

        $this->actingAs($this->admin)->post(route('admin.campaigns.store', CustomerSegment::AtRisk), ['recipients' => [$kept->user_id]] + $this->form())
            ->assertRedirect(route('admin.segments'))
            ->assertSessionHas('message', 'Đã gửi ưu đãi cho 1 khách.');

        $this->assertSame([(int) $kept->user_id], CampaignRecipientModel::pluck('user_id')->map(fn ($id) => (int) $id)->all());
        Mail::assertQueued(CampaignMail::class, 1);
        Mail::assertNotQueued(CampaignMail::class, fn (CampaignMail $mail) => $mail->hasTo($left->email));
        $this->assertSame(1, CouponModel::where('coupon_code', 'like', 'UUDAI%')->count());

        $this->actingAs($this->admin)->get(route('admin.campaigns.create', CustomerSegment::AtRisk))
            ->assertSee($left->email);
    }

    public function test_sua_form_cung_khong_gui_duoc_cho_khach_ngoai_nhom_hoac_da_huy(): void
    {
        $this->atRisk();
        $outsider = $this->customer();
        $this->inSegment($outsider, CustomerSegment::Loyal);
        $optedOut = $this->atRisk();
        DB::table('campaign_opt_outs')->insert(['user_id' => $optedOut->user_id, 'created_at' => now()]);

        $this->actingAs($this->admin)->post(route('admin.campaigns.store', CustomerSegment::AtRisk), ['recipients' => [$outsider->user_id, $optedOut->user_id]] + $this->form())
            ->assertSessionHas('message', fn (string $message) => str_contains($message, 'Không có khách nào'));

        Mail::assertNothingQueued();
        $this->assertSame(0, CampaignModel::count());
    }

    public function test_khong_chon_khach_nao_thi_bi_tu_choi(): void
    {
        $this->atRisk();
        $form = $this->form();
        unset($form['recipients']);

        $this->actingAs($this->admin)->post(route('admin.campaigns.store', CustomerSegment::AtRisk), $form)
            ->assertSessionHasErrors(['recipients' => 'Vui lòng chọn ít nhất một khách.']);

        Mail::assertNothingQueued();
    }

    public function test_form_dien_san_don_toi_thieu(): void
    {
        $this->atRisk();

        $this->actingAs($this->admin)->get(route('admin.campaigns.create', CustomerSegment::AtRisk))
            ->assertSee('Đơn tối thiểu')
            ->assertSee('value="1000000"', false)
            ->assertSee('Đơn từ 1.000.000 VNĐ, giảm tối đa 200.000 VNĐ');
    }

    public function test_don_toi_thieu_am_bi_tu_choi(): void
    {
        $this->atRisk();

        $this->actingAs($this->admin)->post(route('admin.campaigns.store', CustomerSegment::AtRisk), ['min_order' => -1] + $this->form())
            ->assertSessionHasErrors('min_order');

        Mail::assertNothingQueued();
    }

    private function recipientWithMinimum(int $minimum = 1_000_000): CampaignRecipientModel
    {
        $this->atRisk();
        $this->actingAs($this->admin)->post(route('admin.campaigns.store', CustomerSegment::AtRisk), ['min_order' => $minimum] + $this->form());
        auth()->guard('web')->logout();

        return CampaignRecipientModel::firstOrFail();
    }

    public function test_ma_chien_dich_mang_don_toi_thieu_va_email_noi_ro(): void
    {
        $recipient = $this->recipientWithMinimum();

        $this->assertSame(1_000_000, (int) $recipient->coupon->coupon_min_order);
        $this->assertSame(1_000_000, (int) $recipient->campaign->min_order);

        $html = (new CampaignMail($recipient))->render();

        $this->assertStringContainsString('Đơn từ 1.000.000 VNĐ, giảm tối đa 200.000 VNĐ', $html);
        $this->assertStringContainsString('Nhập mã ở giỏ hàng khi đơn đủ điều kiện', $html);
        $this->assertStringNotContainsString('Mã được áp sẵn', $html);
    }

    public function test_khong_dat_don_toi_thieu_thi_email_van_bao_ap_san(): void
    {
        $html = (new CampaignMail($this->recipient()))->render();

        $this->assertStringContainsString('Mã được áp sẵn', $html);
        $this->assertStringNotContainsString('Đơn từ', $html);
    }

    public function test_bam_email_khi_gio_chua_du_don_toi_thieu_bao_dung_ly_do(): void
    {
        $recipient = $this->recipientWithMinimum();

        $this->get(route('campaign.visit', $recipient->token))
            ->assertRedirect(route('product.page'))
            ->assertSessionMissing('coupon_data')
            ->assertSessionHas('message', 'Mã '.$recipient->coupon->coupon_code.' dùng cho đơn có tiền hàng từ 1.000.000 VNĐ. Hãy nhập mã ở giỏ hàng khi đơn đủ điều kiện.');
    }

    public function test_bam_email_khi_gio_du_don_toi_thieu_thi_ap_ma(): void
    {
        $recipient = $this->recipientWithMinimum();
        $product = $this->makeProduct(price: 1_200_000, slug: 'giay-du-muc');

        $this->withSession(['cart' => [$this->cartLine($product, 1)]])->get(route('campaign.visit', $recipient->token))
            ->assertSessionHas('coupon_data', fn (CouponModel $coupon) => $coupon->is($recipient->coupon));
    }

    public function test_gio_du_don_toi_thieu_tinh_theo_gia_that_khong_theo_session(): void
    {
        $recipient = $this->recipientWithMinimum();
        $product = $this->makeProduct(price: 500_000, slug: 'giay-re');

        $this->withSession(['cart' => [$this->cartLine($product, 1, fakePrice: 5_000_000)]])->get(route('campaign.visit', $recipient->token))
            ->assertSessionMissing('coupon_data');
    }

    public function test_ma_het_han_du_co_don_toi_thieu_van_bao_het_han(): void
    {
        $recipient = $this->recipientWithMinimum();
        $recipient->coupon->update(['coupon_end' => now()->subDays(2)->toDateString()]);

        $this->get(route('campaign.visit', $recipient->token))
            ->assertSessionHas('message', fn (string $message) => str_contains($message, 'đã hết hạn hoặc đã được sử dụng'));
    }

    public function test_trang_phan_khuc_ghi_don_toi_thieu_cua_chien_dich(): void
    {
        $this->recipientWithMinimum();

        $this->actingAs($this->admin)->get(route('admin.segments'))
            ->assertSeeInOrder(['Giảm 15%', 'Đơn từ 1.000.000đ']);
    }

    private function recipient(): CampaignRecipientModel
    {
        $this->atRisk();
        $this->send();

        return CampaignRecipientModel::firstOrFail();
    }

    public function test_email_co_ma_lien_ket_va_khong_nhac_ten_nhom(): void
    {
        $recipient = $this->recipient();

        $html = (new CampaignMail($recipient))->render();

        $this->assertStringContainsString($recipient->coupon->coupon_code, $html);
        $this->assertStringContainsString(route('campaign.visit', $recipient->token), $html);
        $this->assertStringContainsString(route('campaign.unsubscribe', $recipient->token), $html);
        $this->assertStringContainsString('Giảm tối đa 200.000 VNĐ', $html);
        $this->assertStringNotContainsString('Sắp rời bỏ', $html);
    }

    public function test_bam_nut_trong_email_ghi_nhan_va_ap_san_ma(): void
    {
        $recipient = $this->recipient();
        auth()->guard('web')->logout();

        $this->get(route('campaign.visit', $recipient->token))
            ->assertRedirect(route('product.page'))
            ->assertSessionHas('coupon_data', fn (CouponModel $coupon) => $coupon->is($recipient->coupon));

        $this->assertNotNull($recipient->fresh()->clicked_at);
        $this->assertNotNull($recipient->fresh()->opened_at);
    }

    public function test_ma_da_dung_het_thi_khong_ap_nua(): void
    {
        $recipient = $this->recipient();
        $recipient->coupon->update(['coupon_quantity' => 0]);

        $this->get(route('campaign.visit', $recipient->token))
            ->assertRedirect(route('product.page'))
            ->assertSessionMissing('coupon_data');
    }

    public function test_tai_hinh_trong_email_tinh_la_da_mo(): void
    {
        $recipient = $this->recipient();

        $this->get(route('campaign.opened', $recipient->token))->assertOk()->assertHeader('Content-Type', 'image/gif');

        $this->assertNotNull($recipient->fresh()->opened_at);
    }

    public function test_mo_link_huy_nhan_chua_huy_cho_toi_khi_xac_nhan(): void
    {
        $recipient = $this->recipient();

        $this->get(route('campaign.unsubscribe', $recipient->token))->assertOk()->assertSee('Không nhận email ưu đãi nữa?');
        $this->assertSame(0, DB::table('campaign_opt_outs')->count(), 'Máy quét link mở GET không được tính là huỷ');

        $this->post(route('campaign.unsubscribe.confirm', $recipient->token))->assertOk()->assertSee('Bạn đã huỷ nhận email ưu đãi');
        $this->assertSame(1, DB::table('campaign_opt_outs')->where('user_id', $recipient->user_id)->count());
    }

    public function test_token_sai_thi_khong_tim_thay(): void
    {
        $this->get(route('campaign.visit', 'khong-co'))->assertNotFound();
    }

    public function test_ket_qua_chien_dich_chi_tinh_don_dung_dung_ma(): void
    {
        $recipient = $this->recipient();
        $customer = $recipient->user;
        $recipient->update(['opened_at' => now(), 'clicked_at' => now()]);
        $this->completedOrder($customer, goods: 2_000_000, couponId: $recipient->coupon_id);
        $this->completedOrder($customer, goods: 900_000);
        $this->completedOrder($customer, goods: 500_000, couponId: $recipient->coupon_id, status: OrderStatus::Cancelled);

        $campaign = app(SegmentReport::class)->campaigns()->first();

        $this->assertSame(1, $campaign->sent);
        $this->assertSame(1, $campaign->opened);
        $this->assertSame(1, $campaign->clicked);
        $this->assertSame(1, $campaign->getAttribute('orders'));
        $this->assertSame(2_000_000, $campaign->getAttribute('revenue'));

        $this->actingAs($this->admin)->get(route('admin.segments'))
            ->assertSeeInOrder(['Mời khách cũ quay lại', 'Sắp rời bỏ', 'Giảm 15%', 'Doanh thu', '2.000.000 đ']);
    }

    public function test_tang_ma_gio_hang_goi_y_muc_giam_theo_nhom(): void
    {
        $customer = $this->atRisk();
        $product = $this->makeProduct(slug: 'giay-treo');
        DB::table('user_events')->insert([
            'session_id' => str_repeat('a', 40), 'user_id' => $customer->user_id, 'event_type' => 'add_cart',
            'pro_id' => $product->pro_id, 'size_id' => 1, 'color_id' => 1, 'meta' => json_encode(['quantity' => 1]), 'created_at' => now()->subDay(),
        ]);
        $this->admin->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web'));

        $this->actingAs($this->admin)->get(route('open_cart.index'))
            ->assertOk()
            ->assertSee('data-percent="15"', false)
            ->assertSee('data-segment="Sắp rời bỏ"', false);
    }
}
