<?php

namespace Tests\Feature;

use App\Mail\CartReminderMail;
use App\Models\CartReminderModel;
use App\Models\OrderModel;
use App\Models\UserEventModel as Event;
use App\Models\UserModel;
use App\Services\SampleData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Sample data goes into the same tables as real data, and one command takes
 * it out again without touching anything real.
 */
class SampleDataTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedLookupTables();
        $product = $this->makeProduct(slug: 'giay-mau');
        $this->addVariant($product, colorId: 2);
        $this->customer = $this->makeUser();
        app()->detectEnvironment(fn () => 'local');
    }

    private function sampleEvents(): int
    {
        return Event::where('session_id', 'like', SampleData::SESSION_PREFIX.'%')->count();
    }

    private function sampleUsers(): int
    {
        return UserModel::where('email', 'like', '%'.SampleData::EMAIL_DOMAIN)->count();
    }

    public function test_nap_du_lieu_mau_vao_chung_bang_voi_tai_khoan_mau_rieng(): void
    {
        $this->artisan('sample:seed')->assertSuccessful();

        $this->assertGreaterThan(0, $this->sampleEvents());
        $this->assertSame(Event::count(), $this->sampleEvents());
        $this->assertSame(60, $this->sampleUsers());
        $this->assertSame(0, Event::where('user_id', $this->customer->user_id)->count(), 'Không gắn lịch sử mẫu vào khách thật');
    }

    public function test_nap_lai_thay_ban_cu_chu_khong_nhan_doi(): void
    {
        $this->artisan('sample:seed');
        $events = Event::count();

        $this->artisan('sample:seed');

        $this->assertSame($events, Event::count());
        $this->assertSame(60, $this->sampleUsers());
    }

    public function test_vua_nap_xong_khong_gui_email_nhac_cho_khach_mau(): void
    {
        $this->artisan('sample:seed');

        $this->artisan('carts:remind')->assertSuccessful();

        Mail::assertNothingQueued();
    }

    public function test_xoa_du_lieu_mau_giu_nguyen_du_lieu_that(): void
    {
        $this->artisan('sample:seed');
        $real = Event::create([
            'session_id' => str_repeat('a', 40), 'user_id' => $this->customer->user_id,
            'event_type' => Event::PAGE_VIEW, 'created_at' => now()->subDay(),
        ]);

        $this->artisan('sample:clear', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, $this->sampleEvents());
        $this->assertSame(0, $this->sampleUsers());
        $this->assertSame(0, CartReminderModel::count());
        $this->assertSame([$real->event_id], Event::pluck('event_id')->all());
        $this->assertNotNull($this->customer->fresh());
    }

    public function test_tai_khoan_mau_da_co_don_that_thi_duoc_giu_lai(): void
    {
        $this->artisan('sample:seed');
        $sample = UserModel::where('email', 'like', '%'.SampleData::EMAIL_DOMAIN)->firstOrFail();
        OrderModel::create([
            'order_code' => 'DH7654321', 'order_name' => 'A', 'order_email' => $sample->email,
            'order_address' => '1 Võ Văn Ngân', 'order_local' => 'Thủ Đức', 'order_phone' => '0912345678',
            'order_delivery_fee' => 0, 'order_coupon_value' => 0, 'order_total' => 1_000_000,
            'order_payment' => 'cod', 'order_payment_status' => 0, 'order_date' => now()->toDateString(),
            'order_status' => 'new', 'user_id' => $sample->user_id,
        ]);

        $this->artisan('sample:clear', ['--force' => true])
            ->expectsOutputToContain('Giữ lại 1 tài khoản mẫu')
            ->assertSuccessful();

        $this->assertSame([$sample->user_id], UserModel::where('email', 'like', '%'.SampleData::EMAIL_DOMAIN)->pluck('user_id')->all());
    }

    public function test_xoa_can_xac_nhan(): void
    {
        $this->artisan('sample:seed');

        $this->artisan('sample:clear')
            ->expectsConfirmation('Xoá toàn bộ dữ liệu mẫu?', 'no')
            ->assertSuccessful();

        $this->assertGreaterThan(0, $this->sampleEvents());
    }

    public function test_khong_chay_ngoai_moi_truong_local(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->artisan('sample:seed')->assertFailed();
        $this->artisan('sample:clear', ['--force' => true])->assertFailed();

        $this->assertSame(0, Event::count());
        Mail::assertNotQueued(CartReminderMail::class);
    }
}
