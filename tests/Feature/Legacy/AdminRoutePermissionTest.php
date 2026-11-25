<?php

namespace Tests\Feature\Legacy;

use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Admin routes that once answered any signed-in staff member, whatever
 * permissions they held. Each now asks for the permission of its area.
 */
class AdminRoutePermissionTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Excel::fake();
        Http::fake();
        Http::preventStrayRequests();

        $this->staff = $this->makeUser('nhanvien@example.test', 'nhanvien', role: 1);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function guardedRoutes(): array
    {
        $admins = 'Quản trị Tài khoản (Quản trị viên)';

        return [
            'danh sách quản trị viên' => ['get', '/admin/account', $admins],
            'trang thêm quản trị viên' => ['get', '/admin/account/create', $admins],
            'thêm quản trị viên' => ['post', '/admin/account', $admins],
            'sửa quản trị viên' => ['put', '/admin/account/1', $admins],
            'xuất danh sách quản trị viên' => ['post', '/admin/exportadmin-csv', $admins],
            'cấp vai trò' => ['post', '/admin/insert_roles/1', $admins],
            'cấp quyền cho vai trò' => ['post', '/admin/insert_permission/1', $admins],
            'tạo quyền mới' => ['post', '/admin/insert_permission', $admins],
            'khoá tài khoản khách' => ['put', '/admin/accounts/1', 'Quản trị Tài khoản (Khách hàng)'],
            'xuất danh sách khách' => ['post', '/admin/exportuser-csv', 'Quản trị Tài khoản (Khách hàng)'],
            'ẩn hiện sản phẩm' => ['post', '/admin/products/update-status/1', 'Quản trị Sản phẩm'],
            'đặt sản phẩm hot' => ['post', '/admin/products/update-hot/1', 'Quản trị Sản phẩm'],
            'xuất mã giảm giá' => ['post', '/admin/exportcou-csv', 'Quản trị Mã giảm giá'],
            'gửi mã giảm giá cho khách' => ['post', '/admin/send-coupon', 'Quản trị Mã giảm giá'],
            'xuất đơn hàng' => ['post', '/admin/exportorder-csv', 'Quản trị Đơn hàng'],
            'ẩn hiện slide' => ['post', '/admin/promotion-status/1', 'Quản trị Slide'],
            'ẩn hiện danh mục slide' => ['post', '/admin/status/1', 'Quản trị Slide'],
            'ẩn hiện menu' => ['post', '/admin/menu-status/1', 'Quản trị Menu'],
            'xuất doanh thu' => ['post', '/admin/export-csv', 'Thống kê doanh thu'],
            'xuất doanh thu năm' => ['post', '/admin/export-csv-year', 'Thống kê doanh thu'],
        ];
    }

    #[DataProvider('guardedRoutes')]
    public function test_nhan_vien_khong_co_quyen_bi_tu_choi(string $method, string $uri, string $permission): void
    {
        Permission::findOrCreate($permission, 'web');

        $this->actingAs($this->staff)->{$method}($uri)->assertForbidden();
    }

    #[DataProvider('guardedRoutes')]
    public function test_nhan_vien_co_quyen_qua_duoc_buoc_kiem_quyen(string $method, string $uri, string $permission): void
    {
        $this->staff->givePermissionTo(Permission::findOrCreate($permission, 'web'));

        $status = $this->actingAs($this->staff)->{$method}($uri)->getStatusCode();

        $this->assertNotSame(403, $status);
        $this->assertLessThan(500, $status);
    }

    public function test_cac_trang_phan_quyen_can_quyen_quan_tri_vien(): void
    {
        Permission::findOrCreate('Quản trị Tài khoản (Quản trị viên)', 'web');
        $target = Crypt::encrypt($this->staff->user_id);

        foreach (['/admin/account/'.$target.'/edit', '/admin/assign-role/'.$target, '/admin/assign-permission/'.$target] as $uri) {
            $this->actingAs($this->staff)->get($uri)->assertForbidden();
        }
    }
}
