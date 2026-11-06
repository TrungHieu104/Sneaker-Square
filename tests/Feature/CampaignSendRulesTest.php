<?php

namespace Tests\Feature;

use App\Actions\SendCampaignAction;
use App\Enums\CustomerSegment;
use App\Mail\CampaignMail;
use App\Models\CampaignModel;
use App\Models\CampaignRecipientModel;
use App\Models\CouponModel;
use App\Models\OrderModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Tests\Support\SegmentFixtures;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * What keeps a campaign from reaching further than meant: its codes belong to
 * the customers they were sent to, two sends cannot double up on a customer,
 * and a lost customer is written to once.
 */
class CampaignSendRulesTest extends TestCase
{
    use RefreshDatabase;
    use SegmentFixtures;
    use ShopFixtures;

    private const NOT_YOURS = 'không dành cho tài khoản này';

    private const NOBODY_LEFT = 'Không có khách nào';

    private const ONCE_ONLY = 'Nhóm này chỉ được gửi một lần';

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

    private function member(CustomerSegment $segment): UserModel
    {
        $customer = $this->customer();
        $this->inSegment($customer, $segment);

        return $customer;
    }

    private function send(CustomerSegment $segment): TestResponse
    {
        return $this->actingAs($this->admin)->post(route('admin.campaigns.store', $segment), [
            'name' => 'Ưu đãi', 'percent' => 15, 'max_discount' => 200_000, 'days' => 7, 'subject' => 'Tặng bạn mã', 'message' => 'Mời bạn ghé lại.',
            'recipients' => DB::table('customer_segments')->pluck('user_id')->all(),
        ]);
    }

    private function sentToOwner(UserModel $owner): CampaignRecipientModel
    {
        $this->inSegment($owner, CustomerSegment::AtRisk);
        $this->send(CustomerSegment::AtRisk);
        auth()->guard('web')->logout();

        return CampaignRecipientModel::where('user_id', $owner->user_id)->firstOrFail();
    }

    private function applyCode(CouponModel $coupon): void
    {
        $this->withSession(['cart' => [$this->cartLine($this->makeProduct(slug: 'giay-nhap-ma'))]])
            ->from(route('product.cart'))
            ->post(route('checkCoupon.cart'), ['coupon' => $coupon->coupon_code, 'giatri_donhang' => 1_000_000]);
    }

    public function test_ma_chien_dich_gan_voi_khach_duoc_gui(): void
    {
        $owner = $this->customer();

        $recipient = $this->sentToOwner($owner);

        $this->assertSame((int) $owner->user_id, (int) $recipient->coupon->coupon_user_id);
    }

    public function test_chu_ma_nhap_ma_chien_dich_duoc_ap(): void
    {
        $owner = $this->customer();
        $coupon = $this->sentToOwner($owner)->coupon;

        $this->actingAs($owner);
        $this->applyCode($coupon);

        $this->assertTrue($coupon->is(session('coupon_data')));
    }

    public function test_nguoi_khac_nhap_ma_chien_dich_bi_tu_choi(): void
    {
        $coupon = $this->sentToOwner($this->customer())->coupon;

        $this->actingAs($this->customer());
        $this->applyCode($coupon);

        $this->assertStringContainsString(self::NOT_YOURS, (string) session('message'));
        $this->assertNull(session('coupon_data'));
    }

    public function test_nguoi_khac_bam_link_chien_dich_khong_duoc_ap_va_bao_dung_ly_do(): void
    {
        $recipient = $this->sentToOwner($this->customer());

        $this->actingAs($this->customer())->get(route('campaign.visit', $recipient->token))
            ->assertRedirect(route('product.page'))
            ->assertSessionMissing('coupon_data')
            ->assertSessionHas('message', fn (string $message) => str_contains($message, self::NOT_YOURS));
    }

    public function test_chua_dang_nhap_bam_link_van_duoc_ap_ma(): void
    {
        $recipient = $this->sentToOwner($this->customer());

        $this->get(route('campaign.visit', $recipient->token))
            ->assertSessionHas('coupon_data', fn (CouponModel $coupon) => $coupon->is($recipient->coupon));
    }

    public function test_ap_ma_luc_chua_dang_nhap_roi_nguoi_khac_dat_hang_van_tra_du_gia(): void
    {
        $recipient = $this->sentToOwner($this->customer());
        $stranger = $this->customer();
        $this->makeAddress($stranger);

        $this->withSession(['cart' => [$this->cartLine($this->makeProduct(slug: 'giay-dat-hang'))]])
            ->get(route('campaign.visit', $recipient->token))
            ->assertSessionHas('coupon_data');

        $this->actingAs($stranger)->post(route('product.checkoutPOST'), ['payment' => 'cod'])
            ->assertRedirect(route('success.checkout'));

        $order = OrderModel::where('user_id', $stranger->user_id)->firstOrFail();
        $this->assertSame(0, (int) $order->order_coupon_value, 'Người khác phải trả đủ giá');
        $this->assertNull($order->coupon_id);
        $this->assertSame([1, 0], [(int) $recipient->coupon->fresh()->coupon_quantity, (int) $recipient->coupon->fresh()->coupon_used]);
    }

    public function test_ma_khong_gan_voi_ai_van_dung_duoc_cho_moi_tai_khoan(): void
    {
        $coupon = CouponModel::create([
            'coupon_name' => 'Mã chung', 'coupon_code' => 'MACHUNG', 'coupon_value' => 10, 'coupon_quantity' => 5, 'coupon_used' => 0,
            'coupon_condition' => 0, 'coupon_start' => now()->toDateString(), 'coupon_end' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($this->customer());
        $this->applyCode($coupon);

        $this->assertTrue($coupon->is(session('coupon_data')));
    }

    public function test_hai_lan_gui_cung_luc_moi_khach_chi_nhan_mot_ma(): void
    {
        $first = $this->member(CustomerSegment::AtRisk);
        $second = $this->member(CustomerSegment::AtRisk);
        $execute = fn (): int => app(SendCampaignAction::class)->execute(CustomerSegment::AtRisk, [
            'name' => 'Ưu đãi', 'percent' => 15, 'min_order' => 0, 'max_discount' => 200_000, 'days' => 7, 'subject' => 'Tặng bạn mã', 'message' => 'Mời bạn ghé lại.',
        ], [(int) $first->user_id, (int) $second->user_id], (int) $this->admin->user_id);

        // The rival runs once this send has read its list, before it writes anything.
        $rivalSent = null;
        CampaignModel::created(function () use (&$rivalSent, $execute) {
            if ($rivalSent === null) {
                $rivalSent = 0;
                $rivalSent = $execute();
            }
        });

        $sent = $execute();

        $this->assertSame([2, 0], [$rivalSent, $sent]);
        $this->assertSame(2, CampaignRecipientModel::count());
        $this->assertSame(2, CouponModel::where('coupon_code', 'like', 'UUDAI%')->count());
        $this->assertSame(1, CampaignModel::count(), 'Lần gửi không còn ai để gửi không được để lại chiến dịch rỗng');
        Mail::assertQueued(CampaignMail::class, 2);
    }

    public function test_nhom_da_roi_bo_chi_nhan_mot_chien_dich_cuoi(): void
    {
        $this->member(CustomerSegment::Lost);
        $this->send(CustomerSegment::Lost)->assertSessionHas('message', 'Đã gửi ưu đãi cho 1 khách.');
        CampaignRecipientModel::query()->update(['sent_at' => now()->subYear()]);

        $this->send(CustomerSegment::Lost)
            ->assertSessionHas('message', fn (string $message) => str_contains($message, self::NOBODY_LEFT));

        Mail::assertQueued(CampaignMail::class, 1);
        $this->assertSame(1, CampaignModel::count());
    }

    public function test_khach_moi_vao_nhom_da_roi_bo_van_nhan_chien_dich_cuoi(): void
    {
        $this->member(CustomerSegment::Lost);
        $this->send(CustomerSegment::Lost);
        CampaignRecipientModel::query()->update(['sent_at' => now()->subDays(8)]);
        $newcomer = $this->member(CustomerSegment::Lost);

        $this->send(CustomerSegment::Lost)->assertSessionHas('message', 'Đã gửi ưu đãi cho 1 khách.');

        Mail::assertQueued(CampaignMail::class, fn (CampaignMail $mail) => $mail->hasTo($newcomer->email));
        Mail::assertQueued(CampaignMail::class, 2);
    }

    public function test_uu_dai_khi_con_o_nhom_khac_khong_tinh_la_chien_dich_cuoi(): void
    {
        $customer = $this->member(CustomerSegment::AtRisk);
        $this->send(CustomerSegment::AtRisk);
        CampaignRecipientModel::query()->update(['sent_at' => now()->subDays(8)]);
        DB::table('customer_segments')->where('user_id', $customer->user_id)->update(['segment' => CustomerSegment::Lost->value]);

        $this->send(CustomerSegment::Lost)->assertSessionHas('message', 'Đã gửi ưu đãi cho 1 khách.');
    }

    public function test_form_nhom_da_roi_bo_bao_chi_gui_mot_lan_va_khach_da_nhan(): void
    {
        $customer = $this->member(CustomerSegment::Lost);
        $this->send(CustomerSegment::Lost);
        CampaignRecipientModel::query()->update(['sent_at' => now()->subYear()]);

        $this->actingAs($this->admin)->get(route('admin.campaigns.create', CustomerSegment::Lost))
            ->assertOk()
            ->assertSee(self::ONCE_ONLY)
            ->assertDontSee($customer->email)
            ->assertSee('Gửi cho 0 khách')
            ->assertSee('1 khách khác của nhóm không có trong danh sách vì đã huỷ nhận email hoặc đã nhận ưu đãi trước đó.');
    }

    public function test_nhom_khac_khong_bi_gioi_han_mot_lan(): void
    {
        $this->member(CustomerSegment::AtRisk);

        $this->actingAs($this->admin)->get(route('admin.campaigns.create', CustomerSegment::AtRisk))
            ->assertOk()
            ->assertDontSee(self::ONCE_ONLY);
    }
}
