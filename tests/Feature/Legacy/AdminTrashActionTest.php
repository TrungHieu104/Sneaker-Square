<?php

namespace Tests\Feature\Legacy;

use App\Models\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Restoring and purging from the admin trash screens change data, so they
 * answer a CSRF-protected form only: a link, a prefetch or an <img> pointing at
 * one of these URLs no longer empties a trash.
 */
class AdminTrashActionTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    /**
     * @return array<string, array{string}>
     */
    public static function trashActions(): array
    {
        $names = [
            'product.restore', 'product.restore.all', 'product.delete', 'product.delete.all',
            'image.restore', 'image.restore.all', 'image.delete', 'image.delete.all',
            'comment.restore', 'comment.restore.all', 'comment.delete', 'comment.delete.all',
            'product-category.restore', 'product-category.restore.all', 'product-category.delete', 'product-category.delete.all',
            'blog.restore', 'blog.restoreAll', 'blog.delete', 'blog.deleteAll',
            'cate_blog.restore', 'cate_blog.restoreAll', 'cate_blog.delete', 'cate_blog.deleteAll',
            'tag.restore', 'tag.restoreAll', 'tag.delete', 'tag.deleteAll',
            'coupon.restore', 'coupon.restoreAll', 'coupon.delete', 'coupon.delete.all',
            'page.restore', 'page.restoreAll', 'page.delete', 'page.delete.all',
            'promotion.restore', 'promotion.restoreAll', 'promotion.delete', 'promotion.delete.all',
            'cate_slide.restore', 'cate_slide.restoreAll', 'cate_slide.delete', 'cate_slide.delete.all',
            'menu.restore', 'menu.restoreAll', 'menu.delete', 'menus.delete.all',
            'contact-form.restore', 'contact-form.restoreAll', 'contact-form.forceDelete', 'contact-form.delete.all',
        ];

        return array_combine($names, array_map(fn ($name) => [$name], $names));
    }

    #[DataProvider('trashActions')]
    public function test_thao_tac_thung_rac_khong_nhan_get(string $name): void
    {
        $route = Route::getRoutes()->getByName($name);
        $this->assertNotNull($route);
        $uri = route($name, array_fill_keys($route->parameterNames(), '1'));

        $this->assertNotContains('GET', $route->methods());
        // With no GET route left, the site's fallback answers it.
        $this->get($uri)->assertNotFound();
    }

    public function test_trang_thung_rac_gui_form_co_csrf(): void
    {
        $this->seedLookupTables();
        $admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Sản phẩm', 'web'));
        $product = $this->makeProduct(slug: 'giay-trong-thung-rac');
        $product->delete();

        $html = $this->actingAs($admin)->get(route('product.trashed'))->assertOk()->getContent();

        $this->assertStringContainsString('action="'.route('product.restore', $product->pro_id).'" method="post"', $html);
        $this->assertStringContainsString('action="'.route('product.restore.all').'" method="post"', $html);
        $this->assertStringContainsString('name="_method" value="PATCH"', $html);
        $this->assertStringContainsString('name="_method" value="DELETE"', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringNotContainsString('href="'.route('product.delete', $product->pro_id).'"', $html);
        $this->assertSoftDeleted(ProductModel::withTrashed()->find($product->pro_id));
    }

    public function test_xoa_vinh_vien_chi_ap_dung_cho_ban_ghi_trong_thung_rac(): void
    {
        $this->seedLookupTables();
        $admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Sản phẩm', 'web'));
        $product = $this->makeProduct(slug: 'giay-dang-ban');

        $this->actingAs($admin)->delete(route('product.delete', $product->pro_id))->assertNotFound();

        $this->assertNotNull(ProductModel::find($product->pro_id));
    }

    public function test_view_quan_tri_khong_dung_hop_thoai_cua_trinh_duyet(): void
    {
        $offenders = collect(File::allFiles(resource_path('views/backend')))
            ->filter(fn ($file) => preg_match('/\bconfirm\(|\balert\(/', preg_replace('/\{\{--.*?--\}\}/s', '', $file->getContents())))
            ->map(fn ($file) => $file->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame([], $offenders);
    }
}
