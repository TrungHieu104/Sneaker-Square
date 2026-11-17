<?php

namespace Tests\Feature;

use App\Enums\PageType;
use App\Models\PageModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * A database from before `pages` still has its pages in `faq`; the command
 * moves them over once and leaves nothing of the old table behind. It also
 * folds the old about-page permission into the one for policies.
 */
class MovePagesFromFaqTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const COMMAND = 'pages:move-from-faq';

    private const OLD_PERMISSION = 'Quản trị FAQ';

    private const NEW_PERMISSION = 'Quản trị Chính sách & FAQ';

    private const ABOUT_PERMISSION = 'Giới thiệu';

    public function test_chuyen_chinh_sach_va_trang_gioi_thieu_giu_nguyen_id_va_ban_da_xoa_tam(): void
    {
        $this->oldTable([
            $this->oldRow(3, 'Chính sách giao hàng'),
            $this->oldRow(7, 'Giới thiệu về Sneaker Square', about: true),
            $this->oldRow(9, 'Chính sách cũ', deletedAt: '2026-10-01 08:00:00'),
        ]);
        DB::table('migrations')->insert(['migration' => '2026_05_25_093443_create_faq_table', 'batch' => 1]);

        $this->artisan(self::COMMAND)->expectsOutputToContain('Đã chuyển 3 trang')->assertSuccessful();

        $this->assertFalse(Schema::hasTable('faq'));
        $this->assertDatabaseMissing('migrations', ['migration' => '2026_05_25_093443_create_faq_table']);
        $this->assertSame(PageType::Policy, PageModel::find(3)?->page_type);
        $this->assertSame('Chính sách giao hàng', PageModel::find(3)?->page_name);
        $this->assertSame('<p>Nội dung Chính sách giao hàng</p>', PageModel::find(3)?->page_content);
        $this->assertSame(PageType::About, PageModel::find(7)?->page_type);
        $this->assertSoftDeleted('pages', ['page_id' => 9]);
    }

    public function test_quyen_cu_doi_ten_va_admin_dang_co_quyen_van_giu_quyen(): void
    {
        $this->oldTable([$this->oldRow(1, 'Chính sách giao hàng')]);
        $admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate(self::OLD_PERMISSION, 'web'));

        $this->artisan(self::COMMAND)->assertSuccessful();

        $this->assertDatabaseMissing('permissions', ['name' => self::OLD_PERMISSION]);
        $this->assertTrue($admin->fresh()?->hasPermissionTo(self::NEW_PERMISSION));
        $this->actingAs($admin)->get(route('page.index'))->assertOk()->assertSee('Chính sách giao hàng');
    }

    public function test_quyen_moi_da_co_thi_khong_doi_ten_de_len(): void
    {
        $this->oldTable([$this->oldRow(1, 'Chính sách giao hàng')]);
        Permission::findOrCreate(self::OLD_PERMISSION, 'web');
        Permission::findOrCreate(self::NEW_PERMISSION, 'web');

        $this->artisan(self::COMMAND)->expectsOutputToContain('đã có sẵn')->assertSuccessful();

        $this->assertDatabaseHas('permissions', ['name' => self::OLD_PERMISSION]);
        $this->assertDatabaseHas('permissions', ['name' => self::NEW_PERMISSION]);
    }

    public function test_chay_lai_khi_da_chuyen_xong_thi_khong_lam_gi(): void
    {
        $this->artisan(self::COMMAND)->expectsOutput('Không còn bảng faq, không có gì để chuyển.')->assertSuccessful();
    }

    public function test_quyen_gioi_thieu_gop_vao_quyen_chinh_sach_ke_ca_khi_da_chuyen_trang(): void
    {
        $editor = Role::findOrCreate('Biên tập', 'web');
        $editor->givePermissionTo(Permission::findOrCreate(self::ABOUT_PERMISSION, 'web'));
        $direct = $this->makeUser(email: 'gioithieu@example.test', username: 'gioithieu', role: 1);
        $direct->givePermissionTo(self::ABOUT_PERMISSION);
        Permission::findOrCreate(self::NEW_PERMISSION, 'web');

        $this->artisan(self::COMMAND)->expectsOutputToContain('Đã gộp quyền "Giới thiệu"')->assertSuccessful();

        $this->assertDatabaseMissing('permissions', ['name' => self::ABOUT_PERMISSION]);
        $this->assertTrue($editor->fresh()?->hasPermissionTo(self::NEW_PERMISSION));
        $this->assertTrue($direct->fresh()?->hasPermissionTo(self::NEW_PERMISSION));

        $this->artisan(self::COMMAND)->doesntExpectOutputToContain('Đã gộp')->assertSuccessful();
    }

    public function test_chuyen_trang_va_gop_quyen_gioi_thieu_trong_mot_lan_chay(): void
    {
        $this->oldTable([$this->oldRow(1, 'Giới thiệu về Sneaker Square', about: true)]);
        Permission::findOrCreate(self::OLD_PERMISSION, 'web');
        $admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate(self::ABOUT_PERMISSION, 'web'));

        $this->artisan(self::COMMAND)->assertSuccessful();

        $this->assertDatabaseMissing('permissions', ['name' => self::OLD_PERMISSION]);
        $this->assertDatabaseMissing('permissions', ['name' => self::ABOUT_PERMISSION]);
        $this->actingAs($admin->fresh())->get(route('page.edit', encrypt(1)))->assertOk()->assertSee('Giới thiệu về Sneaker Square');
    }

    public function test_bang_pages_da_co_du_lieu_thi_dung_lai_va_giu_bang_faq(): void
    {
        $this->oldTable([$this->oldRow(1, 'Chính sách giao hàng')]);
        PageModel::create(['page_type' => PageType::Policy, 'page_name' => 'Đã có', 'page_description' => '', 'page_content' => 'x']);

        $this->artisan(self::COMMAND)->assertFailed();

        $this->assertTrue(Schema::hasTable('faq'));
        $this->assertSame(1, PageModel::count());
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function oldTable(array $rows): void
    {
        Schema::create('faq', function (Blueprint $table) {
            $table->increments('faq_id');
            $table->string('faq_name')->unique();
            $table->text('faq_description');
            $table->text('faq_content');
            $table->string('faq_slug')->unique()->nullable();
            $table->boolean('faq_hidden')->default(1);
            $table->boolean('faq_about')->default(0);
            $table->string('faq_meta_keywords', 2000)->nullable();
            $table->string('faq_meta_description', 2000)->nullable();
            $table->string('faq_created_by')->nullable();
            $table->string('faq_updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('faq')->insert($rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function oldRow(int $id, string $name, bool $about = false, ?string $deletedAt = null): array
    {
        return [
            'faq_id' => $id,
            'faq_name' => $name,
            'faq_description' => '',
            'faq_content' => '<p>Nội dung '.$name.'</p>',
            'faq_slug' => str($name)->slug()->value(),
            'faq_hidden' => 1,
            'faq_about' => $about ? 1 : 0,
            'faq_meta_keywords' => null,
            'faq_meta_description' => null,
            'faq_created_by' => 'Admin',
            'faq_updated_by' => null,
            'created_at' => '2026-05-25 09:00:00',
            'updated_at' => '2026-05-25 09:00:00',
            'deleted_at' => $deletedAt,
        ];
    }
}
