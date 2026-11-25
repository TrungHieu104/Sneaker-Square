<?php

namespace Tests\Feature\Legacy;

use App\Models\CateNewsModel;
use App\Models\NewsModel;
use App\Models\TagsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterisation of the public blog: the listing, one category, one tag,
 * an article and the search box.
 */
class ContentBlogPageTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const VISIBLE = 'Cách buộc dây giày chạy bộ';

    private const HIDDEN = 'Bài viết đang bị ẩn đi';

    private const FUTURE = 'Bài viết hẹn giờ đăng sau';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_trang_blog_chi_liet_ke_bai_dang_hien_va_da_den_ngay(): void
    {
        $category = $this->category('Kinh nghiệm', 'kinh-nghiem');
        $this->category('Danh mục ẩn', 'danh-muc-an', visible: false);
        $this->news(self::VISIBLE, $category, views: 5);
        $this->news(self::HIDDEN, $category, visible: false);
        $this->news(self::FUTURE, $category, postDate: today()->addDays(3)->toDateString());
        $this->tag('Chạy bộ');
        $this->tag('Tag ẩn', visible: false);

        $response = $this->get(route('blog.page'))
            ->assertOk()
            ->assertViewIs('frontend.pages.blog.blog_page')
            ->assertViewHas('getOneCateNews', null)
            ->assertSee(self::VISIBLE)
            ->assertDontSee(self::HIDDEN)
            ->assertDontSee(self::FUTURE);

        $this->assertSame([self::VISIBLE], collect($response->viewData('listNews')->items())->pluck('news_title')->all());
        $this->assertSame([self::VISIBLE], $response->viewData('hotNews')->pluck('news_title')->all());
        $this->assertSame(['Chạy bộ'], $response->viewData('tags')->pluck('tag_content')->all());

        $sidebar = $response->viewData('cateNews');
        $this->assertSame(['Kinh nghiệm'], $sidebar->pluck('cate_news_name')->all());
        $this->assertSame(3, (int) $sidebar->first()->get_news_in_cate_count);
    }

    public function test_trang_danh_muc_liet_ke_bai_cua_danh_muc_va_bai_noi_bat_o_danh_muc_khac(): void
    {
        $tips = $this->category('Kinh nghiệm', 'kinh-nghiem');
        $other = $this->category('Tin tức', 'tin-tuc');
        $this->news(self::VISIBLE, $tips);
        $this->news(self::HIDDEN, $tips, visible: false);
        $this->news('Ra mắt mẫu giày mới tháng này', $other);

        $response = $this->get(route('cate.news', 'kinh-nghiem'))
            ->assertOk()
            ->assertSee('Kinh nghiệm');

        $this->assertSame($tips->cate_news_id, $response->viewData('getOneCateNews')->cate_news_id);
        $this->assertSame([self::VISIBLE], $response->viewData('getOneCateNews')->getNewsInCate->pluck('news_title')->all());
        $this->assertSame(['Ra mắt mẫu giày mới tháng này'], $response->viewData('hotNews')->pluck('news_title')->all());
    }

    public function test_danh_muc_bi_an_mo_trang_chung(): void
    {
        $this->category('Danh mục ẩn', 'danh-muc-an', visible: false);

        $this->get(route('cate.news', 'danh-muc-an'))
            ->assertOk()
            ->assertViewHas('getOneCateNews', null);
    }

    public function test_danh_muc_khong_ton_tai_quay_ve_trang_blog(): void
    {
        $this->get(route('cate.news', 'khong-co'))
            ->assertRedirect(route('blog.page'))
            ->assertSessionHas('message', 'Không tồn tại mục bài viết này!')
            ->assertSessionHas('iconMessage', 'info');
    }

    public function test_chi_tiet_bai_viet_tang_luot_xem_va_hien_tag_dang_hien(): void
    {
        $category = $this->category('Kinh nghiệm', 'kinh-nghiem');
        $news = $this->news(self::VISIBLE, $category, views: 7);
        $related = $this->news('Chọn tất chạy bộ phù hợp', $category);
        $news->getTags()->attach([$this->tag('Chạy bộ')->tag_id, $this->tag('Tag ẩn', visible: false)->tag_id]);

        $response = $this->get(route('news.detail', $news->news_slug))
            ->assertOk()
            ->assertViewIs('frontend.pages.blog.blog_detail')
            ->assertSee(self::VISIBLE)
            ->assertViewHas('meta_title', 'SEO '.self::VISIBLE)
            ->assertViewHas('meta_desc', 'Mô tả SEO')
            ->assertViewHas('meta_keywords', 'giày, chạy bộ')
            ->assertViewHas('img', 'backend/uploads/blog/anh.jpg')
            ->assertViewHas('url_canonical', route('news.detail', $news->news_slug));

        $this->assertSame(8, (int) $news->refresh()->views);
        $this->assertSame([$related->news_id], $response->viewData('relatedNews')->pluck('news_id')->all());
        $this->assertSame(['Chạy bộ'], $response->viewData('getTags')->pluck('tag_content')->all());
    }

    public function test_chi_tiet_bai_khong_ton_tai_hoac_chua_den_ngay_quay_ve_blog(): void
    {
        $future = $this->news(self::FUTURE, postDate: today()->addDay()->toDateString());

        $this->get(route('news.detail', 'khong-co.html'))
            ->assertRedirect(route('blog.page'))
            ->assertSessionHas('message', 'Bài viết này không tồn tại trong blog của Sneaker Square!');

        $this->get(route('news.detail', $future->news_slug))
            ->assertRedirect(route('blog.page'))
            ->assertSessionHas('message', 'Bài viết này không tồn tại trong blog của Sneaker Square!');
    }

    public function test_chi_tiet_bai_bi_an_quay_ve_blog_va_khong_tang_luot_xem(): void
    {
        $hidden = $this->news(self::HIDDEN, visible: false, views: 2);

        $this->get(route('news.detail', $hidden->news_slug))
            ->assertRedirect(route('blog.page'))
            ->assertSessionHas('message', 'Không tìm thấy bài viết trong blog của Sneaker Square!');

        $this->assertSame(2, (int) $hidden->refresh()->views);
    }

    public function test_trang_tag_liet_ke_bai_cua_tag(): void
    {
        $tag = $this->tag('Chạy bộ');
        $tagged = $this->news(self::VISIBLE);
        $this->news('Bài không gắn tag nào cả');
        $hidden = $this->news(self::HIDDEN, visible: false);
        $tagged->getTags()->attach($tag->tag_id);
        $hidden->getTags()->attach($tag->tag_id);

        $response = $this->get(route('tags.news', 'chay-bo'))
            ->assertOk()
            ->assertViewIs('frontend.pages.blog.blog_tag')
            ->assertSee('Chạy bộ');

        $this->assertSame([self::VISIBLE], $response->viewData('getOneTag')->getNews->pluck('news_title')->all());
        $this->assertCount(2, $response->viewData('listNews')->items());
        $this->assertCount(2, $response->viewData('hotNews'));
        $this->assertSame(2, (int) $response->viewData('tags')->first()->get_news_count);
    }

    public function test_tag_bi_an_mo_trang_tag_rong(): void
    {
        $this->tag('Tag ẩn', visible: false);

        $this->get(route('tags.news', 'tag-an'))
            ->assertOk()
            ->assertViewHas('getOneTag', null);
    }

    public function test_tag_khong_ton_tai_quay_ve_blog(): void
    {
        $this->get(route('tags.news', 'khong-co'))
            ->assertRedirect(route('blog.page'))
            ->assertSessionHas('message', 'Không tồn tại tag này!');
    }

    public function test_tim_bai_viet_theo_tieu_de_tom_tat_hoac_noi_dung(): void
    {
        $this->news(self::VISIBLE);
        $this->news('Bảo quản giày da lộn', summary: 'Mẹo giữ giày luôn mới');
        $this->news(self::HIDDEN, visible: false);

        $response = $this->get(route('news.search', ['keywords' => ' <b>giày</b> ']))
            ->assertOk()
            ->assertViewIs('frontend.pages.blog.blog_search')
            ->assertViewHas('keywords', 'giày');

        $this->assertEqualsCanonicalizing(
            [self::VISIBLE, 'Bảo quản giày da lộn'],
            collect($response->viewData('listNews')->items())->pluck('news_title')->all(),
        );

        $byContent = $this->get(route('news.search', ['keywords' => 'Mẹo giữ']))->assertOk();
        $this->assertSame(['Bảo quản giày da lộn'], collect($byContent->viewData('listNews')->items())->pluck('news_title')->all());
    }

    public function test_tim_bai_viet_khong_nhap_tu_khoa_quay_lai_trang_truoc(): void
    {
        $this->from(route('blog.page'))->get(route('news.search', ['keywords' => '  ']))
            ->assertRedirect(route('blog.page'))
            ->assertSessionHas('message', 'Vui lòng nhập từ khóa');
    }

    // -------------------------------------------------------------- helpers

    private function category(string $name, string $slug, bool $visible = true): CateNewsModel
    {
        return CateNewsModel::create([
            'cate_news_name' => $name,
            'cate_news_slug' => $slug,
            'cate_news_sort' => CateNewsModel::count() + 1,
            'cate_news_hidden' => $visible ? 1 : 0,
        ]);
    }

    private function tag(string $content, bool $visible = true): TagsModel
    {
        return TagsModel::create([
            'tag_content' => $content,
            'tag_slug' => Str::slug($content),
            'tag_hidden' => $visible ? 1 : 0,
        ]);
    }

    private function news(
        string $title,
        ?CateNewsModel $category = null,
        bool $visible = true,
        ?string $postDate = null,
        int $views = 0,
        string $summary = 'Tóm tắt bài viết',
    ): NewsModel {
        $category ??= CateNewsModel::first() ?? $this->category('Kinh nghiệm', 'kinh-nghiem');

        return NewsModel::create([
            'news_title' => $title,
            'news_slug' => Str::slug($title).'.html',
            'news_summarize' => $summary,
            'news_content' => '<p>Nội dung bài viết</p>',
            'news_hidden' => $visible ? 1 : 0,
            'news_img' => NewsModel::count() === 0 ? 'backend/uploads/blog/anh.jpg' : null,
            'news_SEO_title' => 'SEO '.$title,
            'news_meta_keywords' => 'giày, chạy bộ',
            'news_meta_description' => 'Mô tả SEO',
            'views' => $views,
            'post_date' => $postDate ?? today()->toDateString(),
            'cate_news_id' => $category->cate_news_id,
        ]);
    }
}
