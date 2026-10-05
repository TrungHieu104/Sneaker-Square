<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Every public storefront page opens. Each controller shares the header,
 * menu and footer itself, so a page whose controller stops doing so fails
 * on render rather than in any test of its own.
 */
class StorefrontPagesTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    public static function pages(): array
    {
        return [
            'trang chủ' => ['home.page'],
            'sản phẩm' => ['product.page'],
            'sản phẩm hot' => ['product.hot'],
            'sản phẩm sale' => ['product.sale'],
            'bài viết' => ['blog.page'],
            'tìm bài viết' => ['news.search'],
            'tìm kiếm' => ['search.frontend'],
            'giới thiệu' => ['about.page'],
            'liên hệ' => ['contact.page'],
            'chính sách trả hàng' => ['policy.return'],
            'đăng nhập' => ['user.login'],
            'đăng ký' => ['user.register'],
            'quên mật khẩu' => ['user.forgot'],
            'yêu thích' => ['product.wishlist'],
            'đặt hàng thất bại' => ['failed.checkout'],
        ];
    }

    #[DataProvider('pages')]
    public function test_trang_cong_khai_mo_duoc(string $route): void
    {
        $this->seedLookupTables();
        $this->makeProduct(slug: 'giay-trang-chu');

        // A listing with nothing to show redirects instead, which is fine here:
        // what this guards against is a page failing to render.
        $this->assertLessThan(400, $this->get(route($route, ['keyword' => 'giay']))->status());
    }

    public function test_trang_san_pham_va_gio_hang_mo_duoc(): void
    {
        $this->seedLookupTables();
        $product = $this->makeProduct(slug: 'giay-mo-trang');

        $this->get(route('product.detail', $product->pro_slug))->assertOk();
        $this->withSession(['cart' => [$this->cartLine($product)]])->get(route('product.cart'))->assertOk();
    }
}
