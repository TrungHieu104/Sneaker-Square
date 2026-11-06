<?php

namespace Tests\Feature;

use App\Enums\CustomerSegment;
use App\Enums\OrderStatus;
use App\Models\CustomerSegmentModel;
use App\Models\UserModel;
use App\Services\CustomerSegments\SegmentCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SegmentFixtures;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The nightly RFM scoring and the pages that show it.
 */
class CustomerSegmentTest extends TestCase
{
    use RefreshDatabase;
    use SegmentFixtures;
    use ShopFixtures;

    private function segmentOf(UserModel $user): ?CustomerSegment
    {
        return CustomerSegmentModel::find($user->user_id)?->segment;
    }

    /**
     * Five groups a shop can tell apart, large enough for the ranks to mean
     * what they say.
     *
     * @return array<string, list<UserModel>>
     */
    private function typicalShop(): array
    {
        return [
            'lost' => $this->buyers(3, orders: 1, lastOrderDays: 300, goodsEach: 200_000),
            'atRisk' => $this->buyers(3, orders: 5, lastOrderDays: 200, goodsEach: 3_000_000),
            'regular' => $this->buyers(5, orders: 2, lastOrderDays: 60, goodsEach: 1_000_000),
            'promising' => $this->buyers(3, orders: 1, lastOrderDays: 2, goodsEach: 1_000_000),
            'loyal' => $this->buyers(3, orders: 5, lastOrderDays: 2, goodsEach: 3_000_000),
        ];
    }

    public function test_cham_diem_xep_khach_vao_dung_nhom(): void
    {
        $shop = $this->typicalShop();

        $this->assertSame(17, app(SegmentCalculator::class)->rebuild());

        $this->assertSame(CustomerSegment::Loyal, $this->segmentOf($shop['loyal'][0]));
        $this->assertSame(CustomerSegment::Promising, $this->segmentOf($shop['promising'][0]));
        $this->assertSame(CustomerSegment::Regular, $this->segmentOf($shop['regular'][0]));
        $this->assertSame(CustomerSegment::AtRisk, $this->segmentOf($shop['atRisk'][0]));
        $this->assertSame(CustomerSegment::Lost, $this->segmentOf($shop['lost'][0]));
    }

    public function test_khach_co_so_lieu_bang_nhau_luon_cung_diem(): void
    {
        $sameFigures = $this->buyers(8, orders: 1, lastOrderDays: 5, goodsEach: 1_000_000);
        $this->buyers(4, orders: 3, lastOrderDays: 5, goodsEach: 2_000_000);

        app(SegmentCalculator::class)->rebuild();

        $scores = CustomerSegmentModel::whereIn('user_id', array_map(fn (UserModel $user) => $user->user_id, $sameFigures))
            ->get(['recency_score', 'frequency_score', 'monetary_score'])
            ->map(fn (CustomerSegmentModel $row) => $row->recency_score.$row->frequency_score.$row->monetary_score)
            ->unique();

        $this->assertCount(1, $scores, 'NTILE sẽ chia 8 khách giống hệt nhau vào các mức khác nhau');
        $this->assertSame(1, CustomerSegmentModel::find($sameFigures[0]->user_id)->frequency_score);
    }

    public function test_chi_tinh_don_thanh_cong(): void
    {
        $customer = $this->customer();
        $this->completedOrder($customer, status: OrderStatus::Cancelled);
        $this->completedOrder($customer, status: OrderStatus::Returned);
        $this->completedOrder($customer, status: OrderStatus::Delivering);

        $this->assertSame(0, app(SegmentCalculator::class)->rebuild());
        $this->assertNull($this->segmentOf($customer));
    }

    public function test_tong_chi_khong_tinh_phi_van_chuyen(): void
    {
        $customer = $this->customer();
        $this->completedOrder($customer, goods: 500_000);

        app(SegmentCalculator::class)->rebuild();

        $this->assertSame(500_000, (int) CustomerSegmentModel::find($customer->user_id)->total_spent);
    }

    public function test_qua_it_khach_thi_khong_xep_ai_vao_nhom_rieng(): void
    {
        $buyers = $this->buyers(3, orders: 1, lastOrderDays: 300, goodsEach: 200_000);

        app(SegmentCalculator::class)->rebuild();

        $this->assertSame(CustomerSegment::Regular, $this->segmentOf($buyers[0]), 'Vài khách thì không so sánh được với nhau');
    }

    public function test_tinh_lai_thay_ket_qua_cu(): void
    {
        $customer = $this->customer();
        $this->inSegment($customer, CustomerSegment::Loyal);

        $this->artisan('customers:segment')->assertSuccessful();

        $this->assertNull($this->segmentOf($customer), 'Khách không còn đơn thành công thì ra khỏi bảng');
    }

    private function adminWith(string ...$permissions): UserModel
    {
        $admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);

        foreach ($permissions as $permission) {
            $admin->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $admin;
    }

    public function test_trang_phan_khuc_can_quyen_thong_ke_truy_cap(): void
    {
        Permission::findOrCreate('Thống kê truy cập', 'web');

        $this->actingAs($this->adminWith())->get(route('admin.segments'))->assertForbidden();
    }

    public function test_trang_phan_khuc_hien_so_khach_va_chi_tieu_tung_nhom(): void
    {
        $this->typicalShop();
        app(SegmentCalculator::class)->rebuild();

        $this->actingAs($this->adminWith('Thống kê truy cập'))->get(route('admin.segments'))
            ->assertOk()
            ->assertSee('17 khách đã có đơn thành công')
            ->assertSeeInOrder(['Thân thiết', 'Chi TB', '15,0 tr', 'Tiềm năng', '1,0 tr'])
            ->assertDontSee('Gửi ưu đãi', 'Không có quyền mã giảm giá thì không gửi được');
    }

    public function test_bang_giai_thich_nhom_hien_moc_that_cua_cua_hang(): void
    {
        $this->typicalShop();
        app(SegmentCalculator::class)->rebuild();
        $rows = CustomerSegmentModel::get();
        $recent = (int) $rows->where('recency_score', 3)->max(fn (CustomerSegmentModel $row) => $row->last_order_on->diffInDays(now()->startOfDay()));
        $orders = (int) $rows->where('frequency_score', 3)->min('order_count');

        $this->actingAs($this->adminWith('Thống kê truy cập'))->get(route('admin.segments'))
            ->assertSee('Cách chia nhóm khách')
            ->assertSee('Trong '.$recent.' ngày qua')
            ->assertSee('Từ '.$orders.' đơn')
            ->assertSee('Dưới 9 khách thì tất cả')
            ->assertSee('Từng mua nhiều đơn và chi nhiều tiền, nhưng lâu rồi chưa quay lại');
    }

    public function test_trang_phan_khuc_khong_con_dong_khuyen_nghi(): void
    {
        $this->typicalShop();
        app(SegmentCalculator::class)->rebuild();
        $admin = $this->adminWith('Thống kê truy cập');

        $this->actingAs($admin)->get(route('admin.segments'))->assertDontSee('Đáng đầu tư nhất')->assertDontSee('Giữ chân');
        $this->actingAs($admin)->get(route('admin.segments.show', CustomerSegment::Loyal))->assertOk()->assertDontSee('Giữ chân');
    }

    public function test_it_khach_thi_bang_giai_thich_khong_hien_moc(): void
    {
        $this->buyers(2, 1, 5, 1_000_000);
        app(SegmentCalculator::class)->rebuild();

        $this->actingAs($this->adminWith('Thống kê truy cập'))->get(route('admin.segments'))
            ->assertOk()
            ->assertSee('Cách chia nhóm khách')
            ->assertDontSee('Mức cao')
            ->assertDontSee('Trong 0 ngày qua');
    }

    public function test_trang_phan_khuc_khi_chua_co_khach_nao(): void
    {
        $this->actingAs($this->adminWith('Thống kê truy cập'))->get(route('admin.segments'))
            ->assertOk()
            ->assertSee('Chưa có khách nào có đơn thành công')
            ->assertSee('Chưa gửi chiến dịch nào');
    }

    public function test_danh_sach_mot_nhom_chi_hien_khach_cua_nhom_do(): void
    {
        $shop = $this->typicalShop();
        app(SegmentCalculator::class)->rebuild();

        $this->actingAs($this->adminWith('Thống kê truy cập'))->get(route('admin.segments.show', CustomerSegment::AtRisk))
            ->assertOk()
            ->assertSee($shop['atRisk'][0]->email)
            ->assertSee('15.000.000 đ')
            ->assertDontSee($shop['loyal'][0]->email);
    }

    public function test_nhom_khong_ton_tai_thi_bao_khong_tim_thay(): void
    {
        $this->actingAs($this->adminWith('Thống kê truy cập'))->get('/admin/phan-khuc/khong-co')->assertNotFound();
    }

    public function test_danh_sach_tai_khoan_hien_va_loc_theo_phan_khuc(): void
    {
        $vip = $this->customer();
        $this->inSegment($vip, CustomerSegment::Loyal);
        $newcomer = $this->customer();
        $admin = $this->adminWith('Quản trị Tài khoản (Khách hàng)');

        $this->actingAs($admin)->get(route('account.user'))
            ->assertOk()
            ->assertSeeInOrder([$vip->email, 'Thân thiết'])
            ->assertSeeInOrder([$newcomer->email, 'Chưa mua']);

        $this->actingAs($admin)->get(route('account.user', ['phan-khuc' => CustomerSegment::Loyal->value]))
            ->assertSee($vip->email)
            ->assertDontSee($newcomer->email);

        $this->actingAs($admin)->get(route('account.user', ['phan-khuc' => 'chua-mua']))
            ->assertSee($newcomer->email)
            ->assertDontSee($vip->email);
    }

    public function test_khach_khong_thay_ten_nhom_cua_minh(): void
    {
        $customer = $this->customer();
        $this->inSegment($customer, CustomerSegment::AtRisk);

        $this->actingAs($customer)->get(route('user.wallet'))
            ->assertOk()
            ->assertDontSee('Sắp rời bỏ');
    }
}
