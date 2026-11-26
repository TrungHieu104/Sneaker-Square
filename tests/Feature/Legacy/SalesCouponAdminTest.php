<?php

namespace Tests\Feature\Legacy;

use App\Models\CouponModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterisation of the coupon CRUD screens in the admin, pinned before a
 * refactor. Sending coupons and the Excel export have their own tests.
 */
class SalesCouponAdminTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const PERMISSION = 'Quản trị Mã giảm giá';

    private const CODE = 'HE2026';

    private const NAME = 'Khuyến mãi hè';

    private const EMPTY_TRASH = 'Không có dữ liệu trong thùng rác!';

    private const NOT_INTEGER = 'Vui lòng nhập số nguyên';

    private const DECIMALS = ['quantity' => 10.5, 'money' => 15.5, 'min_order' => 100000.5, 'max_discount' => 5000.5];

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    // -------------------------------------------------------------- access

    public function test_khach_chua_dang_nhap_bi_dua_ve_trang_dang_nhap_admin(): void
    {
        $this->get(route('coupon.index'))->assertRedirect(route('admin.login'));
        $this->post(route('coupon.store'), $this->payload())->assertRedirect(route('admin.login'));

        $this->assertSame(0, CouponModel::count());
    }

    public function test_nhan_vien_khong_co_quyen_ma_giam_gia_bi_tu_choi(): void
    {
        $coupon = $this->coupon(self::NAME, self::CODE);
        $staff = $this->admin(withPermission: false);

        $this->actingAs($staff)->get(route('coupon.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('coupon.create'))->assertForbidden();
        $this->actingAs($staff)->post(route('coupon.store'), $this->payload(['code' => 'MOI2026', 'name' => 'Mã mới']))->assertForbidden();
        $this->actingAs($staff)->get(route('coupon.edit', $coupon->coupon_id))->assertForbidden();
        $this->actingAs($staff)->put(route('coupon.update', $coupon->coupon_id), $this->payload(['id' => $coupon->coupon_id, 'money' => 99]))->assertForbidden();
        $this->actingAs($staff)->delete(route('coupon.softDelete', $coupon->coupon_id))->assertForbidden();
        $this->actingAs($staff)->get(route('coupon.trashed'))->assertForbidden();
        $this->actingAs($staff)->delete(route('coupon.delete', $coupon->coupon_id))->assertForbidden();

        $this->assertSame(1, CouponModel::count());
        $this->assertSame(10, (int) $coupon->refresh()->coupon_value);
        $this->assertNotSoftDeleted('coupon', ['coupon_id' => $coupon->coupon_id]);
    }

    // ------------------------------------------------------------- listing

    public function test_danh_sach_hien_ma_gia_tri_va_tim_theo_ten_hoac_ma(): void
    {
        $this->coupon(self::NAME, self::CODE, value: 10, condition: 0);
        $this->coupon('Tri ân khách quen', 'TRIAN', value: 50000, condition: 1);

        $this->actingAs($this->admin())->get(route('coupon.index'))
            ->assertOk()
            ->assertSee('Quản lý mã giảm giá')
            ->assertSee(self::CODE)
            ->assertSee('10%')
            ->assertSee('50.000đ');

        $this->actingAs($this->admin())->get(route('coupon.index', ['keyword' => 'TRIAN']))
            ->assertOk()
            ->assertSee('Tri ân khách quen')
            ->assertDontSee(self::NAME);

        $this->actingAs($this->admin())->get(route('coupon.index', ['keyword' => 'hè']))
            ->assertOk()
            ->assertSee(self::NAME)
            ->assertDontSee('Tri ân khách quen');
    }

    public function test_danh_sach_danh_dau_ma_con_han_dung_nhieu_nhat(): void
    {
        // Dates written the way the controller writes them, which is what the
        // query compares against.
        $this->coupon(self::NAME, self::CODE, used: 7, end: now()->addMonth()->format('Y/m/d'));
        $this->coupon('Mã cũ', 'CU2025', used: 9, end: now()->subDay()->format('Y/m/d'));
        $this->coupon('Mã chưa dùng', 'CHUADUNG');

        $html = $this->actingAs($this->admin())->get(route('coupon.index'))
            ->assertOk()
            ->assertSee('Còn hạn')
            ->assertSee('Hết hạn')
            ->getContent();

        // The badge goes to the most used coupon still running, not to the
        // expired one that was used more.
        $this->assertMatchesRegularExpression('/'.self::CODE.'\s*<img src="\/backend\/assets\/img\/giphy.gif"/', $html);
        $this->assertDoesNotMatchRegularExpression('/CU2025\s*<img/', $html);
        $this->assertSame(1, substr_count($html, 'giphy.gif'));
    }

    public function test_trang_them_ma_mo_duoc_va_dien_san_ngay_hom_nay(): void
    {
        $this->actingAs($this->admin())->get(route('coupon.create'))
            ->assertOk()
            ->assertSee('Quản lý mã giảm giá')
            ->assertSee('value="'.today()->format('d-m-Y').'"', false);
    }

    // --------------------------------------------------------------- store

    public function test_them_ma_hop_le_luu_dung_cac_truong(): void
    {
        $this->actingAs($this->admin())->post(route('coupon.store'), $this->payload([
            'start_coupon' => today()->format('d-m-Y'),
            'end_coupon' => today()->addDays(10)->format('d-m-Y'),
            'min_order' => 500000,
            'max_discount' => 100000,
        ]))
            ->assertRedirect(route('coupon.index'))
            ->assertSessionHas('message', 'Thêm thành công')
            ->assertSessionHas('iconMessage', 'success');

        $coupon = CouponModel::where('coupon_code', self::CODE)->firstOrFail();
        $this->assertSame(self::NAME, $coupon->coupon_name);
        $this->assertSame(100, (int) $coupon->coupon_quantity);
        $this->assertSame(15, (int) $coupon->coupon_value);
        $this->assertSame(0, (int) $coupon->coupon_condition);
        $this->assertSame(0, (int) $coupon->coupon_used);
        $this->assertSame(500000, (int) $coupon->coupon_min_order);
        $this->assertSame(100000, (int) $coupon->coupon_max_discount);
        $this->assertSame(today()->toDateString(), date('Y-m-d', strtotime((string) $coupon->coupon_start)));
        $this->assertSame(today()->addDays(10)->toDateString(), date('Y-m-d', strtotime((string) $coupon->coupon_end)));
    }

    public function test_them_ma_giam_theo_tien_khong_nhap_don_toi_thieu_va_giam_toi_da(): void
    {
        $this->actingAs($this->admin())->post(route('coupon.store'), $this->payload([
            'condition' => 1,
            'money' => 30000,
            'min_order' => '',
            'max_discount' => '',
        ]))->assertSessionHasNoErrors();

        $coupon = CouponModel::where('coupon_code', self::CODE)->firstOrFail();
        $this->assertSame(1, (int) $coupon->coupon_condition);
        $this->assertSame(0, (int) $coupon->coupon_min_order);
        $this->assertNull($coupon->coupon_max_discount);
    }

    public function test_them_ma_thieu_thong_tin_bi_tu_choi_bang_tieng_viet(): void
    {
        $this->actingAs($this->admin())->post(route('coupon.store'), [])
            ->assertSessionHasErrors([
                'name' => 'Vui lòng nhập tiêu đề khuyến mãi!',
                'code' => 'Vui lòng nhập mã khuyến mãi!',
                'start_coupon' => 'Vui lòng chọn ngày bắt đầu khuyến mãi!',
                'end_coupon' => 'Vui lòng chọn ngày kết thúc khuyến mãi!',
                'quantity' => 'Vui lòng nhập số lượng!',
                'money' => 'Vui lòng nhập giá trị!',
            ]);

        $this->assertSame(0, CouponModel::count());
    }

    public function test_them_ma_sai_dinh_dang_bi_tu_choi(): void
    {
        $this->actingAs($this->admin())->post(route('coupon.store'), $this->payload([
            'name' => 'ab',
            'code' => 'he',
            'start_coupon' => now()->subDays(3)->toDateString(),
            'end_coupon' => now()->subDays(5)->toDateString(),
            'quantity' => 'nhiều',
            'money' => -5,
            'min_order' => -1,
            'max_discount' => 500,
        ]))->assertSessionHasErrors([
            'name' => 'Tiêu đề quá ngắn',
            'code' => 'Mã khuyến mãi viết chữ in hoa!',
            'start_coupon' => 'Ngày bắt đầu phải sau ngày hiện tại!',
            'end_coupon' => 'Ngày kết thúc phải sau ngày bắt đầu!',
            'quantity' => 'Số lượng mã không được nhập chữ!',
            'money' => 'Vui lòng nhập giá trị lớn hơn 0',
            'min_order' => 'Đơn tối thiểu không được âm',
            'max_discount' => 'Giảm tối đa ít nhất 1.000 VNĐ',
        ]);

        $this->assertSame(0, CouponModel::count());
    }

    public function test_them_ma_trung_ten_hoac_ma_bi_tu_choi(): void
    {
        $this->coupon(self::NAME, self::CODE);

        $this->actingAs($this->admin())->post(route('coupon.store'), $this->payload())
            ->assertSessionHasErrors([
                'name' => 'Tiêu đề đã tồn tại!',
                'code' => 'Mã khuyến mãi đã tồn tại!',
            ]);

        $this->assertSame(1, CouponModel::count());
    }

    // ---------------------------------------------------------------- edit

    public function test_trang_sua_ma_hien_du_lieu_cu(): void
    {
        $coupon = $this->coupon(self::NAME, self::CODE);

        $this->actingAs($this->admin())->get(route('coupon.edit', $coupon->coupon_id))
            ->assertOk()
            ->assertSee(self::CODE)
            ->assertSee('name="id" value="'.$coupon->coupon_id.'"', false);
    }

    public function test_sua_ma_khong_ton_tai_quay_ve_danh_sach(): void
    {
        $this->actingAs($this->admin())->get(route('coupon.edit', 999))
            ->assertRedirect(route('coupon.index'))
            ->assertSessionHas('message', 'Không tồn tại mã khuyến mãi')
            ->assertSessionHas('iconMessage', 'info');
    }

    public function test_cap_nhat_ma_giu_ten_va_ma_cu_luu_cac_truong_moi(): void
    {
        $coupon = $this->coupon(self::NAME, self::CODE);

        $this->actingAs($this->admin())->put(route('coupon.update', $coupon->coupon_id), $this->payload([
            'id' => $coupon->coupon_id,
            'quantity' => 40,
            'money' => 25,
            'start_coupon' => '01-12-2026',
            'end_coupon' => '31-12-2026',
            'min_order' => 300000,
            'max_discount' => '',
        ]))
            ->assertRedirect(route('coupon.index'))
            ->assertSessionHas('message', 'Chỉnh sửa thành công');

        $coupon->refresh();
        $this->assertSame(self::CODE, $coupon->coupon_code);
        $this->assertSame(40, (int) $coupon->coupon_quantity);
        $this->assertSame(25, (int) $coupon->coupon_value);
        $this->assertSame(300000, (int) $coupon->coupon_min_order);
        $this->assertNull($coupon->coupon_max_discount);
        $this->assertSame('2026-12-01', date('Y-m-d', strtotime((string) $coupon->coupon_start)));
        $this->assertSame('2026-12-31', date('Y-m-d', strtotime((string) $coupon->coupon_end)));
    }

    public function test_cap_nhat_ma_trung_ma_khac_hoac_ngay_sai_bi_tu_choi(): void
    {
        $this->coupon('Tri ân khách quen', 'TRIAN');
        $coupon = $this->coupon(self::NAME, self::CODE);

        $this->actingAs($this->admin())->put(route('coupon.update', $coupon->coupon_id), $this->payload([
            'id' => $coupon->coupon_id,
            'name' => 'Tri ân khách quen',
            'code' => 'TRIAN',
            'start_coupon' => '2026-12-10',
            'end_coupon' => '2026-12-01',
        ]))->assertSessionHasErrors([
            'name' => 'Tiêu đề đã tồn tại!',
            'code' => 'Mã khuyến mãi đã tồn tại!',
            'end_coupon' => 'Ngày kết thúc phải sau ngày bắt đầu!',
        ]);

        $this->assertSame(self::CODE, $coupon->refresh()->coupon_code);
    }

    public function test_them_ma_nhap_so_le_bi_tu_choi_vi_khong_phai_so_nguyen(): void
    {
        $this->actingAs($this->admin())->post(route('coupon.store'), $this->payload(self::DECIMALS))
            ->assertSessionHasErrors(array_fill_keys(array_keys(self::DECIMALS), self::NOT_INTEGER));

        $this->assertSame(0, CouponModel::count());
    }

    public function test_cap_nhat_ma_nhap_so_le_bi_tu_choi_vi_khong_phai_so_nguyen(): void
    {
        $coupon = $this->coupon(self::NAME, self::CODE);

        $this->actingAs($this->admin())->put(route('coupon.update', $coupon->coupon_id), $this->payload(['id' => $coupon->coupon_id] + self::DECIMALS))
            ->assertSessionHasErrors(array_fill_keys(array_keys(self::DECIMALS), self::NOT_INTEGER));
    }

    public function test_cap_nhat_ma_khong_ton_tai_quay_ve_danh_sach(): void
    {
        $this->actingAs($this->admin())->put(route('coupon.update', 999), $this->payload(['id' => 999]))
            ->assertRedirect(route('coupon.index'))
            ->assertSessionHas('message', 'Không tồn tại mã khuyến mãi');

        $this->assertSame(0, CouponModel::count());
    }

    // -------------------------------------------------------------- delete

    public function test_xoa_ma_chua_dung_dua_vao_thung_rac(): void
    {
        $coupon = $this->coupon(self::NAME, self::CODE);

        $this->actingAs($this->admin())->from(route('coupon.index'))->delete(route('coupon.softDelete', $coupon->coupon_id))
            ->assertRedirect(route('coupon.index'))
            ->assertSessionHas('message', 'Xóa thành công');

        $this->assertSoftDeleted('coupon', ['coupon_id' => $coupon->coupon_id]);
    }

    public function test_khong_xoa_duoc_ma_da_dung_trong_don_hang(): void
    {
        $coupon = $this->coupon(self::NAME, self::CODE);
        $this->orderUsing($coupon);

        $this->actingAs($this->admin())->delete(route('coupon.softDelete', $coupon->coupon_id))
            ->assertSessionHas('message', 'Mã khuyến mãi đã được sử dụng trong các đơn hàng!')
            ->assertSessionHas('iconMessage', 'info');

        $this->assertNotSoftDeleted('coupon', ['coupon_id' => $coupon->coupon_id]);
    }

    public function test_xoa_ma_khong_ton_tai_quay_lai_va_bao_khong_ton_tai(): void
    {
        $this->actingAs($this->admin())->from(route('coupon.index'))->delete(route('coupon.softDelete', 999))
            ->assertRedirect(route('coupon.index'))
            ->assertSessionHas('message', 'Không tồn tại mã khuyến mãi')
            ->assertSessionHas('iconMessage', 'info');
    }

    // --------------------------------------------------------------- trash

    public function test_thung_rac_chi_hien_ma_da_xoa_va_tim_duoc(): void
    {
        $this->coupon(self::NAME, self::CODE)->delete();
        $this->coupon('Tri ân khách quen', 'TRIAN')->delete();
        $this->coupon('Mã đang chạy', 'DANGCHAY');

        $this->actingAs($this->admin())->get(route('coupon.trashed'))
            ->assertOk()
            ->assertSee('Quản lý mã khuyến mãi')
            ->assertSee(self::CODE)
            ->assertSee('TRIAN')
            ->assertDontSee('DANGCHAY');

        $this->actingAs($this->admin())->get(route('coupon.trashed', ['keyword' => 'TRIAN']))
            ->assertOk()
            ->assertSee('TRIAN')
            ->assertDontSee(self::CODE);
    }

    public function test_khoi_phuc_mot_ma_va_tat_ca(): void
    {
        $first = $this->coupon(self::NAME, self::CODE);
        $second = $this->coupon('Tri ân khách quen', 'TRIAN');
        $first->delete();
        $second->delete();

        $this->actingAs($this->admin())->from(route('coupon.trashed'))->patch(route('coupon.restore', $first->coupon_id))
            ->assertRedirect(route('coupon.trashed'))
            ->assertSessionHas('message', 'Khôi phục thành công');
        $this->assertNotSoftDeleted('coupon', ['coupon_id' => $first->coupon_id]);

        $this->actingAs($this->admin())->patch(route('coupon.restoreAll'))->assertSessionHas('message', 'Hoàn tác thành công!');
        $this->assertNotSoftDeleted('coupon', ['coupon_id' => $second->coupon_id]);

        $this->actingAs($this->admin())->patch(route('coupon.restoreAll'))
            ->assertSessionHas('message', self::EMPTY_TRASH)
            ->assertSessionHas('iconMessage', 'info');
    }

    public function test_xoa_vinh_vien_mot_ma_va_tat_ca(): void
    {
        $first = $this->coupon(self::NAME, self::CODE);
        $second = $this->coupon('Tri ân khách quen', 'TRIAN');
        $first->delete();
        $second->delete();

        $this->actingAs($this->admin())->delete(route('coupon.delete', $first->coupon_id))->assertSessionHas('message', 'Xóa thành công');
        $this->assertDatabaseMissing('coupon', ['coupon_id' => $first->coupon_id]);

        $this->actingAs($this->admin())->delete(route('coupon.delete.all'))->assertSessionHas('message', 'Xóa mã giảm giá thành công!');
        $this->assertSame(0, CouponModel::withTrashed()->count());

        $this->actingAs($this->admin())->delete(route('coupon.delete.all'))->assertSessionHas('message', self::EMPTY_TRASH);
    }

    public function test_khong_xoa_vinh_vien_duoc_ma_da_dung_trong_don_hang(): void
    {
        $coupon = $this->coupon(self::NAME, self::CODE);
        $this->orderUsing($coupon);
        $coupon->delete();

        $this->actingAs($this->admin())->delete(route('coupon.delete', $coupon->coupon_id))
            ->assertSessionHas('message', 'Không thể xóa, mã khuyến mãi đã được sử dụng trong các đơn hàng.')
            ->assertSessionHas('iconMessage', 'error');

        $this->assertSoftDeleted('coupon', ['coupon_id' => $coupon->coupon_id]);
    }

    public function test_khoi_phuc_ma_khong_ton_tai_tra_404(): void
    {
        $this->actingAs($this->admin())->patch(route('coupon.restore', 999))->assertNotFound();
    }

    public function test_xoa_vinh_vien_ma_khong_ton_tai_tra_404(): void
    {
        $this->actingAs($this->admin())->delete(route('coupon.delete', 999))->assertNotFound();
    }

    public function test_nut_xoa_hoi_xac_nhan_bang_hop_thoai_chung(): void
    {
        $this->coupon(self::NAME, self::CODE);
        $this->coupon('Tri ân khách quen', 'TRIAN')->delete();

        $this->actingAs($this->admin())->get(route('coupon.index'))
            ->assertSee('data-confirm="Bạn có chắc muốn xóa?"', false);
        $this->actingAs($this->admin())->get(route('coupon.trashed'))
            ->assertSee('data-confirm="Bạn có chắc muốn xóa tất cả mã giảm giá vĩnh viễn?"', false)
            ->assertSee('data-confirm="Bạn có chắc muốn xóa mã giảm giá này vĩnh viễn?"', false);
    }

    private function admin(bool $withPermission = true): UserModel
    {
        $admin = UserModel::where('email', 'admin@example.test')->first()
            ?? $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $permission = Permission::findOrCreate(self::PERMISSION, 'web');

        if ($withPermission) {
            $admin->givePermissionTo($permission);
        }

        return $admin;
    }

    private function coupon(string $name, string $code, int $value = 10, int $condition = 0, int $used = 0, ?string $end = null): CouponModel
    {
        return CouponModel::create([
            'coupon_name' => $name,
            'coupon_code' => $code,
            'coupon_value' => $value,
            'coupon_quantity' => 100,
            'coupon_used' => $used,
            'coupon_condition' => $condition,
            'coupon_start' => now()->subDays(10)->toDateString(),
            'coupon_end' => $end ?? now()->addMonth()->toDateString(),
        ]);
    }

    private function orderUsing(CouponModel $coupon): void
    {
        $customer = $this->makeUser();

        DB::table('order')->insert([
            'order_code' => 'SSCOUPON1', 'order_name' => 'Khách', 'order_email' => $customer->email,
            'order_address' => '1 Lê Lợi', 'order_local' => 'Quận 1', 'order_phone' => '0900000000',
            'order_delivery_fee' => 0, 'order_total' => 100_000, 'order_payment' => 'cod',
            'order_payment_status' => 0, 'order_date' => now()->toDateString(),
            'coupon_id' => $coupon->coupon_id, 'user_id' => $customer->user_id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => self::NAME,
            'code' => self::CODE,
            'quantity' => 100,
            'start_coupon' => now()->toDateString(),
            'end_coupon' => now()->addMonth()->toDateString(),
            'condition' => 0,
            'money' => 15,
        ], $overrides);
    }
}
