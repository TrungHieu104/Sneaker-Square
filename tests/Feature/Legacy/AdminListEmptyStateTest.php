<?php

namespace Tests\Feature\Legacy;

use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The staff account list hides the signed-in admin's own row, so it is empty
 * exactly when no other staff account matches.
 */
class AdminListEmptyStateTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const EMPTY_LIST = 'Không có dữ liệu để hiển thị.';

    private UserModel $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Http::fake();
        Http::preventStrayRequests();

        $this->manager = $this->makeUser('quanly@gmail.com', 'quanly', role: 1);
        $this->manager->givePermissionTo(Permission::findOrCreate('Quản trị Tài khoản (Quản trị viên)', 'web'));
    }

    public function test_tim_ra_dung_mot_nhan_vien_khac_thi_hien_nhan_vien_do(): void
    {
        $this->makeUser('nhanvien@gmail.com', 'nhanvien', role: 1);

        $this->actingAs($this->manager)->get(route('account.index', ['keyword' => 'nhanvien']))
            ->assertOk()
            ->assertSee('nhanvien@gmail.com')
            ->assertDontSee(self::EMPTY_LIST);
    }

    public function test_chi_tim_ra_chinh_minh_thi_bao_khong_co_du_lieu(): void
    {
        $this->makeUser('nhanvien@gmail.com', 'nhanvien', role: 1);

        $this->actingAs($this->manager)->get(route('account.index', ['keyword' => 'quanly']))
            ->assertOk()
            ->assertSee(self::EMPTY_LIST)
            ->assertDontSee('nhanvien@gmail.com');
    }

    public function test_tim_khong_ra_ai_thi_bao_khong_co_du_lieu(): void
    {
        $this->actingAs($this->manager)->get(route('account.index', ['keyword' => 'khong-co-ai']))
            ->assertOk()
            ->assertSee(self::EMPTY_LIST);
    }
}
