<?php

namespace Tests\Feature\Legacy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * CustomCKFinderAuth, the gate CKFinder consults before it lets anyone browse
 * the file manager staff use from the admin editors.
 *
 * Only the browser page is requested: the connector reads and writes the real
 * public/uploads folder.
 */
class DashboardFileManagerTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    public function test_nhan_vien_mo_duoc_trinh_quan_ly_file_ckfinder(): void
    {
        $staff = $this->makeUser('quantri@example.test', 'quantri', role: 1);

        $this->actingAs($staff)->get(route('ckfinder_browser'))
            ->assertOk()
            ->assertSee('CKFinder.start()', false)
            ->assertSee('\/ckfinder\/connector', false);

        $authentication = config('ckfinder.authentication');
        $this->assertIsCallable($authentication);
        $this->assertTrue($authentication());
    }

    public function test_khach_va_nguoi_chua_dang_nhap_khong_mo_duoc_ckfinder(): void
    {
        $this->get(route('ckfinder_browser'))->assertForbidden();
        $this->get(route('ckfinder_connector'))->assertForbidden();

        $this->actingAs($this->makeUser())->get(route('ckfinder_browser'))->assertForbidden();
    }

    public function test_nhan_vien_bi_khoa_khong_mo_duoc_ckfinder(): void
    {
        $staff = $this->makeUser('bikhoa@example.test', 'bikhoa', role: 1);
        $staff->forceFill(['user_status' => 0])->save();

        $this->actingAs($staff)->get(route('ckfinder_browser'))->assertForbidden();
    }

    public function test_nhan_vien_dang_bi_tam_khoa_dang_nhap_khong_mo_duoc_ckfinder(): void
    {
        $staff = $this->makeUser('tamkhoa@example.test', 'tamkhoa', role: 1);
        $staff->forceFill(['locked_at' => now()->addMinutes(5)])->save();

        $this->actingAs($staff)->get(route('ckfinder_browser'))->assertForbidden();
    }
}
