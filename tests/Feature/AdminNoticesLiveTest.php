<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Events\AdminNoticesChanged;
use App\Models\ContactFormModel;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Services\LiveSocket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Support\ReverbFixtures;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The admin bell: every admin page fetches the counts, and refetches them when
 * Reverb says an order or a contact message changed.
 */
class AdminNoticesLiveTest extends TestCase
{
    use RefreshDatabase;
    use ReverbFixtures;
    use ShopFixtures;

    private UserModel $admin;

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $this->customer = $this->makeUser();
    }

    private function makeOrder(): OrderModel
    {
        return OrderModel::create([
            'order_code' => 'DH'.random_int(1000000, 9999999),
            'order_name' => 'Nguyễn Văn A',
            'order_email' => 'khach@example.test',
            'order_address' => '1 Võ Văn Ngân',
            'order_local' => 'Thủ Đức, TP.HCM',
            'order_phone' => '0912345678',
            'order_delivery_fee' => 30000,
            'order_coupon_value' => 0,
            'order_total' => 1_030_000,
            'order_payment' => 'cod',
            'order_payment_status' => 0,
            'order_date' => now()->toDateString(),
            'order_status' => OrderStatus::New,
            'user_id' => $this->customer->user_id,
        ]);
    }

    public function test_chuong_lay_so_lieu_bang_mot_lan_goi_json(): void
    {
        $this->makeOrder();

        $this->actingAs($this->admin)->getJson(route('admin.notifications'))
            ->assertOk()
            ->assertJsonPath('newOrderCount', 1)
            ->assertJsonStructure(['newOrderCount', 'timestampOrder', 'contactCount', 'timestampContact', 'couponCount', 'slideCount']);
    }

    public function test_khach_khong_lay_duoc_so_lieu_chuong(): void
    {
        $this->actingAs($this->customer)->get(route('admin.notifications'))->assertRedirect();
    }

    public function test_don_moi_va_doi_trang_thai_deu_bao_chuong(): void
    {
        Event::fake([AdminNoticesChanged::class]);

        $order = $this->makeOrder();
        Event::assertDispatchedTimes(AdminNoticesChanged::class, 1);

        $order->update(['order_status' => OrderStatus::Confirmed]);
        Event::assertDispatchedTimes(AdminNoticesChanged::class, 2);
    }

    public function test_lien_he_moi_va_doi_trang_thai_deu_bao_chuong(): void
    {
        Event::fake([AdminNoticesChanged::class]);

        $contact = ContactFormModel::create(['name' => 'Khách', 'email' => 'khach@example.test', 'phone' => '0912345678', 'title' => 'Hỏi size', 'content' => 'Size 42 còn không?']);
        $contact->update(['status' => ContactFormModel::HANDLED]);

        Event::assertDispatchedTimes(AdminNoticesChanged::class, 2);
    }

    public function test_chi_quan_tri_vien_dang_hoat_dong_nghe_duoc_kenh_chuong(): void
    {
        $this->reverbOn();
        $kenh = 'private-'.AdminNoticesChanged::CHANNEL;
        $biKhoa = $this->makeUser(email: 'khoa@example.test', username: 'bikhoa', role: 1);
        $biKhoa->user_status = 0;
        $biKhoa->save();

        $this->actingAs($this->admin)->post('/broadcasting/auth', ['socket_id' => self::SOCKET, 'channel_name' => $kenh])
            ->assertOk()
            ->assertExactJson(['auth' => $this->signature($kenh)]);

        $this->actingAs($this->customer)->post('/broadcasting/auth', ['socket_id' => self::SOCKET, 'channel_name' => $kenh])
            ->assertForbidden();
        $this->actingAs($biKhoa)->post('/broadcasting/auth', ['socket_id' => self::SOCKET, 'channel_name' => $kenh])
            ->assertForbidden();
    }

    public function test_tat_reverb_thi_trang_admin_hoi_lai_dinh_ky_va_khong_giu_luong(): void
    {
        $this->actingAs($this->admin)->get(route('support'))->assertOk()
            ->assertSee('"poll":'.LiveSocket::ADMIN_POLL_MS.',"idle":'.LiveSocket::ADMIN_IDLE_MS.',"socket":null', false)
            ->assertSee('backend/assets/js/admin-live.js', false)
            ->assertSee(json_encode(route('admin.notifications')), false)
            ->assertDontSee('js/realtime/pusher-8.6.0.min.js', false)
            ->assertDontSee('EventSource', false);
    }

    public function test_bat_reverb_thi_trang_admin_nhan_khoa_cong_khai_khong_nhan_khoa_bi_mat(): void
    {
        $this->reverbOn();

        $this->actingAs($this->admin)->get(route('support'))->assertOk()
            ->assertSee('js/realtime/pusher-8.6.0.min.js', false)
            ->assertSee('js/realtime/echo-2.6.3.iife.js', false)
            ->assertSee('"key":"'.self::KEY.'"', false)
            ->assertSee('"'.AdminNoticesChanged::CHANNEL.'"', false)
            ->assertDontSee(self::SECRET);
    }
}
