<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Each admin area is closed to an admin who lacks its permission, whatever
 * way the route file spells that permission.
 */
class AdminRoutesTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    public function test_vao_admin_thi_ve_trang_dang_nhap_admin(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_khong_co_quyen_thi_khong_vao_duoc_khu_vuc_do(): void
    {
        $admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Bài viết', 'web'));
        Permission::findOrCreate('Quản trị Đơn hàng', 'web');

        $this->actingAs($admin)->get(route('tag.trashed'))->assertOk();
        $this->actingAs($admin)->get(route('order.index'))->assertForbidden();
    }
}
