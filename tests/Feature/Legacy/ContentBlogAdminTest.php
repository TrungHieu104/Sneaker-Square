<?php

namespace Tests\Feature\Legacy;

use App\Models\CateNewsModel;
use App\Models\NewsModel;
use App\Models\TagsModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterisation of the admin blog area as it stands: articles, article
 * categories and tags, all behind the "Quản trị Bài viết" permission.
 */
class ContentBlogAdminTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const PERMISSION = 'Quản trị Bài viết';

    private const UPLOAD_DIR = 'backend/uploads/blog';

    private const TITLE = 'Cách buộc dây giày chạy bộ';

    private const SUMMARY = 'Tóm tắt đủ dài cho bài viết';

    private const CONTENT = '<p>Nội dung bài viết đủ dài</p>';

    /** @var array<int, string> */
    private array $uploadsBefore = [];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        $this->seedLookupTables();
        $this->pointMysqlRulesAtTestDatabase();
        $this->uploadsBefore = $this->uploadedFiles();
    }

    protected function tearDown(): void
    {
        foreach (array_diff($this->uploadedFiles(), $this->uploadsBefore) as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    // -------------------------------------------------------------- access

    public function test_khach_chua_dang_nhap_bi_chuyen_ve_trang_dang_nhap_admin(): void
    {
        foreach (['blog.index', 'blog-category.index', 'tags.index', 'blog.trashed', 'cate_blog.trashed', 'tag.trashed'] as $route) {
            $this->get(route($route))
                ->assertRedirect(route('admin.login'))
                ->assertSessionHas('message', 'Không được phép truy cập');
        }
    }

    public function test_tai_khoan_khach_hang_khong_vao_duoc_trang_bai_viet(): void
    {
        $this->actingAs($this->makeUser())->get(route('blog.index'))->assertRedirect(route('admin.login'));
    }

    public function test_nhan_vien_khong_co_quyen_bai_viet_bi_tu_choi(): void
    {
        $staff = $this->makeUser('nhanvien@example.test', 'nhanvien', role: 1);
        $news = $this->news();
        $tag = $this->tag('Chạy bộ');

        $this->actingAs($staff)->get(route('blog.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('blog-category.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('tags.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('blog.status', $news->news_id), ['n-status' => 0])->assertForbidden();
        $this->actingAs($staff)->delete(route('tags.destroy', $tag->tag_id))->assertForbidden();

        $this->assertSame(1, (int) $news->refresh()->news_hidden);
        $this->assertNotSoftDeleted($tag);
    }

    // -------------------------------------------------------------- articles

    public function test_danh_sach_bai_viet_hien_bai_va_tag_cua_bai(): void
    {
        $news = $this->news();
        $news->getTags()->attach($this->tag('Chạy bộ')->tag_id);

        $this->actingAs($this->admin())->get(route('blog.index'))
            ->assertOk()
            ->assertViewIs('backend.pages.blog.blogs.blog_list')
            ->assertSee(self::TITLE)
            ->assertSee('Chạy bộ');
    }

    public function test_danh_sach_bai_viet_tim_theo_tieu_de(): void
    {
        $this->news();
        $this->news('Bảo quản giày da lộn đúng cách', 'bao-quan-giay.html');

        $news = $this->actingAs($this->admin())->get(route('blog.index', ['keyword' => 'da lộn']))
            ->assertOk()
            ->viewData('news');

        $this->assertSame(['Bảo quản giày da lộn đúng cách'], collect($news->items())->pluck('news_title')->all());
    }

    public function test_trang_them_bai_viet_co_danh_muc_va_tag(): void
    {
        $this->category('Kinh nghiệm');
        $this->tag('Chạy bộ');

        $this->actingAs($this->admin())->get(route('blog.create'))
            ->assertOk()
            ->assertViewIs('backend.pages.blog.blogs.blog_create')
            ->assertViewHas('today', today()->toDateString())
            ->assertSee('Kinh nghiệm')
            ->assertSee('Chạy bộ');
    }

    public function test_them_bai_viet_thieu_du_lieu_bao_loi_tieng_viet(): void
    {
        $this->actingAs($this->admin())->post(route('blog.store'), [])
            ->assertSessionHasErrors([
                'title' => 'Vui lòng nhập tiêu đề!',
                'slug' => 'Vui lòng nhập đường dẫn!',
                'summarize' => 'Vui lòng nhập tóm tắt bài viết!',
                'content' => 'Nội dung bài viết không được để trống!',
                'img_blog' => 'Vui lòng chọn hình ảnh!',
                'cate' => 'Vui lòng chọn danh mục bài viết!',
                'post_date' => 'Vui lòng chọn ngày đăng bài viết!',
            ]);

        $this->assertDatabaseCount('news', 0);
    }

    public function test_them_bai_viet_qua_ngan_ngay_cu_va_file_sai_bi_tu_choi(): void
    {
        $category = $this->category('Kinh nghiệm');

        $this->actingAs($this->admin())->post(route('blog.store'), $this->articleForm($category, [
            'title' => 'Ngắn',
            'slug' => 'ngan',
            'summarize' => 'Ngắn',
            'content' => 'Ngắn',
            'post_date' => today()->subDay()->format('d-m-Y'),
            'img_blog' => UploadedFile::fake()->create('tai-lieu.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors([
            'title' => 'Tiêu đề quá ngắn',
            'slug' => 'Đường dẫn quá ngắn',
            'summarize' => 'Tóm tắt bài viết quá ngắn',
            'content' => 'Nội dung bài viết quá ngắn',
            'post_date' => 'Vui lòng chọn ngày đăng từ ngày hôm nay trở đi!',
            'img_blog' => 'File không được hỗ trợ!',
        ]);
    }

    public function test_them_bai_viet_trung_tieu_de_va_duong_dan_bi_tu_choi(): void
    {
        $category = $this->category('Kinh nghiệm');
        $this->news(slug: 'cach-buoc-day-giay');

        $this->actingAs($this->admin())->post(route('blog.store'), $this->articleForm($category))
            ->assertSessionHasErrors([
                'title' => 'Tiêu đề đã tồn tại, vui lòng nhập tiêu đề mới',
                'slug' => 'Tiêu đề đã tồn tại, vui lòng nhập đường dẫn mới',
            ]);
    }

    public function test_trang_sua_bai_viet_mo_dung_bai(): void
    {
        $news = $this->news();

        $this->actingAs($this->admin())->get(route('blog.edit', $news->news_id))
            ->assertOk()
            ->assertViewIs('backend.pages.blog.blogs.blog_edit')
            ->assertSee(self::TITLE);
    }

    public function test_sua_bai_viet_khong_ton_tai_quay_ve_danh_sach(): void
    {
        $this->actingAs($this->admin())->get(route('blog.edit', 999))
            ->assertRedirect('admin/blog')
            ->assertSessionHas('message', 'Không tồn tại bài viết');
    }

    public function test_cap_nhat_bai_viet_luu_noi_dung_moi_va_dong_bo_tag(): void
    {
        $old = $this->category('Kinh nghiệm');
        $new = $this->category('Tin tức', 'tin-tuc');
        $keep = $this->tag('Chạy bộ');
        $drop = $this->tag('Bóng rổ');
        $news = $this->news(category: $old);
        $news->getTags()->attach([$keep->tag_id, $drop->tag_id]);

        $this->actingAs($this->admin())->put(route('blog.update', $news->news_id), [
            'id' => $news->news_id,
            'title' => self::TITLE,
            'slug' => 'duong-dan-moi-cho-bai',
            'summarize' => 'Tóm tắt mới đủ dài cho bài',
            'content' => '<p>Nội dung mới đủ dài</p>',
            'post_date' => '01-12-2026',
            'cate' => $new->cate_news_id,
            'hidden' => '0',
            'tags' => [$keep->tag_id],
            'seo_title' => 'SEO mới',
            'img_blog' => UploadedFile::fake()->image('anh-moi.png'),
        ])
            ->assertRedirect(route('blog.index'))
            ->assertSessionHas('message', 'Chỉnh sửa thành công!');

        $news->refresh();
        $this->assertSame('duong-dan-moi-cho-bai.html', $news->news_slug);
        $this->assertSame('Tóm tắt mới đủ dài cho bài', $news->news_summarize);
        $this->assertSame('2026/12/01', $news->post_date);
        $this->assertSame($new->cate_news_id, (int) $news->cate_news_id);
        $this->assertSame(0, (int) $news->news_hidden);
        // A radio left unchecked is read as "not hot", whatever the article was.
        $this->assertSame(0, (int) $news->news_hot);
        $this->assertSame('SEO mới', $news->news_SEO_title);
        $this->assertSame('', (string) $news->news_meta_keywords);
        $this->assertStringEndsWith('.png', $news->news_img);
        $this->assertFileExists(public_path($news->news_img));
        $this->assertSame([$keep->tag_id], $news->getTags()->pluck('tags.tag_id')->all());
    }

    public function test_cap_nhat_bai_viet_bao_loi_khi_trung_tieu_de_bai_khac(): void
    {
        $this->news('Bài viết khác đã có sẵn', 'bai-khac.html');
        $news = $this->news();

        $this->actingAs($this->admin())->put(route('blog.update', $news->news_id), [
            'id' => $news->news_id,
            'title' => 'Bài viết khác đã có sẵn',
            'slug' => 'bai-khac.html',
            'summarize' => '',
            'content' => '',
        ])->assertSessionHasErrors([
            'title' => 'Tiêu đề đã tồn tại, vui lòng nhập tiêu đề mới',
            'slug' => 'Tiêu đề đã tồn tại, vui lòng nhập đường dẫn mới',
            'summarize' => 'Vui lòng nhập tóm tắt bài viết!',
            'content' => 'Nội dung bài viết không được để trống!',
        ]);

        $this->assertSame(self::TITLE, $news->refresh()->news_title);
    }

    public function test_cap_nhat_bai_viet_khong_ton_tai_quay_ve_danh_sach(): void
    {
        $this->actingAs($this->admin())->put(route('blog.update', 999), [
            'id' => 999,
            'title' => self::TITLE,
            'slug' => 'cach-buoc-day-giay',
            'summarize' => self::SUMMARY,
            'content' => self::CONTENT,
        ])
            ->assertRedirect(route('blog.index'))
            ->assertSessionHas('message', 'Không tồn tại bài viết');
    }

    public function test_an_hien_va_noi_bat_bai_viet_tra_json(): void
    {
        $news = $this->news();

        $this->actingAs($this->admin())->post(route('blog.status', $news->news_id), ['n-status' => 0])
            ->assertOk()->assertJson(['message' => 'Cập nhật thành công']);
        $this->actingAs($this->admin())->post(route('blog.hot', $news->news_id), ['n-hot' => 1])
            ->assertOk()->assertJson(['message' => 'Cập nhật thành công']);

        $news->refresh();
        $this->assertSame(0, (int) $news->news_hidden);
        $this->assertSame(1, (int) $news->news_hot);

        $this->actingAs($this->admin())->post(route('blog.status', 999), ['n-status' => 0])
            ->assertNotFound()->assertJson(['message' => 'Không tìm thấy dữ liệu']);
        $this->actingAs($this->admin())->post(route('blog.hot', 999), ['n-hot' => 1])
            ->assertNotFound()->assertJson(['message' => 'Không tìm thấy dữ liệu']);
    }

    public function test_xoa_bai_viet_dua_vao_thung_rac_roi_khoi_phuc(): void
    {
        $news = $this->news();
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('blog.destroy', $news->news_id))
            ->assertRedirect('/admin/blog')
            ->assertSessionHas('message', 'Xóa thành công');
        $this->assertSoftDeleted($news);

        $this->actingAs($admin)->get(route('blog.trashed'))
            ->assertOk()
            ->assertViewIs('backend.pages.blog.blogs.blog_trash')
            ->assertSee(self::TITLE);

        $this->actingAs($admin)->from(route('blog.trashed'))->patch(route('blog.restore', $news->news_id))
            ->assertRedirect(route('blog.trashed'))
            ->assertSessionHas('message', 'Hoàn tác thành công!');
        $this->assertNotSoftDeleted($news);

        $this->actingAs($admin)->patch(route('blog.restore', 999))->assertNotFound();
    }

    public function test_xoa_bai_viet_khong_ton_tai_quay_lai_va_bao_khong_ton_tai(): void
    {
        $this->actingAs($this->admin())->from(route('blog.index'))->delete(route('blog.destroy', 999))
            ->assertRedirect(route('blog.index'))
            ->assertSessionHas('message', 'Không tồn tại bài viết này!')
            ->assertSessionHas('iconMessage', 'info');
    }

    public function test_khoi_phuc_va_xoa_vinh_vien_tat_ca_bai_trong_thung_rac(): void
    {
        $first = $this->news();
        $second = $this->news('Bảo quản giày da lộn đúng cách', 'bao-quan-giay.html');
        $first->delete();
        $second->delete();
        $admin = $this->admin();

        $this->actingAs($admin)->from(route('blog.trashed'))->patch(route('blog.restoreAll'))
            ->assertRedirect(route('blog.trashed'))
            ->assertSessionHas('message', 'Hoàn tác thành công!');
        $this->assertSame(0, NewsModel::onlyTrashed()->count());

        $first->delete();
        $this->actingAs($admin)->from(route('blog.trashed'))->delete(route('blog.delete', $first->news_id))
            ->assertRedirect(route('blog.trashed'))
            ->assertSessionHas('message', 'Xóa thành công!');
        $this->assertModelMissing($first);
        $this->actingAs($admin)->delete(route('blog.delete', 999))->assertNotFound();

        $second->delete();
        $this->actingAs($admin)->from(route('blog.trashed'))->delete(route('blog.deleteAll'))
            ->assertRedirect(route('blog.trashed'))
            ->assertSessionHas('message', 'Xóa tất cả thành công!');
        $this->assertModelMissing($second);
    }

    // -------------------------------------------------------------- categories

    public function test_danh_sach_danh_muc_bai_viet_va_tim_kiem(): void
    {
        $this->category('Kinh nghiệm');
        $this->category('Tin tức', 'tin-tuc');

        $cates = $this->actingAs($this->admin())->get(route('blog-category.index', ['keyword' => 'Tin']))
            ->assertOk()
            ->assertViewIs('backend.pages.blog.blog_cate.blog_cate_list')
            ->assertSee('Tin tức')
            ->viewData('cateNews');

        $this->assertSame(['Tin tức'], collect($cates->items())->pluck('cate_news_name')->all());
    }

    public function test_trang_them_va_sua_danh_muc_bai_viet(): void
    {
        $category = $this->category('Kinh nghiệm');

        $this->actingAs($this->admin())->get(route('blog-category.create'))
            ->assertOk()->assertViewIs('backend.pages.blog.blog_cate.blog_cate_create');
        $this->actingAs($this->admin())->get(route('blog-category.edit', $category->cate_news_id))
            ->assertOk()->assertViewIs('backend.pages.blog.blog_cate.blog_cate_edit')->assertSee('Kinh nghiệm');
        $this->actingAs($this->admin())->get(route('blog-category.edit', 999))
            ->assertRedirect('admin/blog-category')
            ->assertSessionHas('message', 'Không tồn tại thông tin danh mục bài viết');
    }

    public function test_them_danh_muc_bai_viet_hop_le_luu_ca_anh(): void
    {
        $this->actingAs($this->admin())->post(route('blog-category.store'), [
            'name' => 'Kinh nghiệm',
            'slug' => 'kinh-nghiem',
            'sort' => '3',
            'hidden' => '1',
            'img_blog' => UploadedFile::fake()->image('danh-muc.jpg'),
        ])
            ->assertRedirect(route('blog-category.index'))
            ->assertSessionHas('message', 'Thêm mới thành công!');

        $category = CateNewsModel::where('cate_news_name', 'Kinh nghiệm')->firstOrFail();
        $this->assertSame('kinh-nghiem', $category->cate_news_slug);
        $this->assertSame(3, (int) $category->cate_news_sort);
        $this->assertSame(1, (int) $category->cate_news_hidden);
        $this->assertStringStartsWith('/'.self::UPLOAD_DIR.'/', $category->cate_news_img);
        $this->assertFileExists(public_path($category->cate_news_img));
    }

    public function test_them_danh_muc_bai_viet_khong_chon_hien_thi_thi_bi_an(): void
    {
        $this->actingAs($this->admin())->post(route('blog-category.store'), [
            'name' => 'Kinh nghiệm',
            'slug' => 'kinh-nghiem',
            'sort' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cate_news', ['cate_news_name' => 'Kinh nghiệm', 'cate_news_hidden' => 0, 'cate_news_img' => null]);
    }

    public function test_them_danh_muc_bai_viet_sai_du_lieu_bao_loi_tieng_viet(): void
    {
        $this->category('Kinh nghiệm');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('blog-category.store'), [])
            ->assertSessionHasErrors([
                'name' => 'Vui lòng nhập tên danh mục!',
                'slug' => 'Vui lòng nhập đường dẫn danh mục!',
                'sort' => 'Vui lòng nhập số thứ tự!',
            ]);

        $this->actingAs($admin)->post(route('blog-category.store'), ['name' => 'ab', 'slug' => 'ab', 'sort' => 'abc'])
            ->assertSessionHasErrors([
                'name' => 'Tên danh mục quá ngắn!',
                'slug' => 'Đường dẫn danh mục quá ngắn!',
                'sort' => 'Số thứ tự không được nhập chữ!',
            ]);

        $this->actingAs($admin)->post(route('blog-category.store'), ['name' => 'Kinh nghiệm', 'slug' => 'kinh-nghiem', 'sort' => '1.5'])
            ->assertSessionHasErrors([
                'name' => 'Tên danh mục bài viết đã có, vui lòng nhập tên mới!',
                'slug' => 'Đường dẫn danh mục bài viết đã có, vui lòng nhập đường dẫn mới!',
                'sort' => 'Vui lòng nhập số nguyên!',
            ]);

        $this->assertDatabaseCount('cate_news', 1);
    }

    public function test_cap_nhat_danh_muc_bai_viet_giu_ten_cu_thi_duoc(): void
    {
        $category = $this->category('Kinh nghiệm');

        $this->actingAs($this->admin())->put(route('blog-category.update', $category->cate_news_id), [
            'id' => $category->cate_news_id,
            'name' => 'Kinh nghiệm',
            'slug' => 'kinh-nghiem-moi',
            'sort' => '7',
            'status' => '0',
            'img_blog' => UploadedFile::fake()->image('danh-muc-moi.jpg'),
        ])
            ->assertRedirect(route('blog-category.index'))
            ->assertSessionHas('message', 'Chỉnh sửa thành công!');

        $category->refresh();
        $this->assertSame('kinh-nghiem-moi', $category->cate_news_slug);
        $this->assertSame(7, (int) $category->cate_news_sort);
        $this->assertSame(0, (int) $category->cate_news_hidden);
        $this->assertFileExists(public_path($category->cate_news_img));
    }

    public function test_cap_nhat_danh_muc_bai_viet_trung_ten_danh_muc_khac_bi_tu_choi(): void
    {
        $this->category('Tin tức', 'tin-tuc');
        $category = $this->category('Kinh nghiệm');

        $this->actingAs($this->admin())->put(route('blog-category.update', $category->cate_news_id), [
            'id' => $category->cate_news_id,
            'name' => 'Tin tức',
            'slug' => 'tin-tuc',
            'sort' => '-1',
        ])->assertSessionHasErrors([
            'name' => 'Tên danh mục đã tồn tại, vui lòng nhập tên danh mục mới',
            'slug' => 'Đường dẫn danh mục đã tồn tại, vui lòng nhập tên danh mục mới',
            'sort' => 'Vui lòng nhập thứ tự lớn hơn 0!',
        ]);

        $this->actingAs($this->admin())->put(route('blog-category.update', 999), [
            'id' => 999, 'name' => 'Không có', 'slug' => 'khong-co', 'sort' => '1',
        ])
            ->assertRedirect(route('blog-category.index'))
            ->assertSessionHas('message', 'Không tồn tại danh mục');
    }

    public function test_an_hien_danh_muc_bai_viet_tra_json(): void
    {
        $category = $this->category('Kinh nghiệm');

        $this->actingAs($this->admin())->post(route('cate_blog.status', $category->cate_news_id), ['cate-new-status' => 0])
            ->assertOk()->assertJson(['message' => 'Cập nhật thành công']);
        $this->assertSame(0, (int) $category->refresh()->cate_news_hidden);

        $this->actingAs($this->admin())->post(route('cate_blog.status', 999), ['cate-new-status' => 0])
            ->assertNotFound()->assertJson(['message' => 'Không tìm thấy dữ liệu']);
    }

    public function test_khong_xoa_duoc_danh_muc_con_bai_viet(): void
    {
        $category = $this->category('Kinh nghiệm');
        $this->news(category: $category);

        $this->actingAs($this->admin())->from(route('blog-category.index'))->delete(route('blog-category.destroy', $category->cate_news_id))
            ->assertRedirect(route('blog-category.index'))
            ->assertSessionHas('message', 'Không thể xóa danh mục đang có bài viết!');

        $this->assertNotSoftDeleted($category);
    }

    public function test_xoa_danh_muc_bai_viet_khong_ton_tai_quay_lai_va_bao_khong_ton_tai(): void
    {
        $this->actingAs($this->admin())->from(route('blog-category.index'))->delete(route('blog-category.destroy', 999))
            ->assertRedirect(route('blog-category.index'))
            ->assertSessionHas('message', 'Không tồn tại danh mục!')
            ->assertSessionHas('iconMessage', 'info');
    }

    public function test_thung_rac_danh_muc_bai_viet_xoa_khoi_phuc_va_xoa_vinh_vien(): void
    {
        $first = $this->category('Kinh nghiệm');
        $second = $this->category('Tin tức', 'tin-tuc');
        $admin = $this->admin();
        $from = route('cate_blog.trashed');

        $this->actingAs($admin)->from(route('blog-category.index'))->delete(route('blog-category.destroy', $first->cate_news_id))
            ->assertRedirect(route('blog-category.index'))
            ->assertSessionHas('message', 'Xóa thành công!');
        $this->assertSoftDeleted($first);

        $this->actingAs($admin)->get($from)
            ->assertOk()->assertViewIs('backend.pages.blog.blog_cate.blog_cate_trash')->assertSee('Kinh nghiệm');

        $this->actingAs($admin)->from($from)->patch(route('cate_blog.restore', $first->cate_news_id))
            ->assertRedirect($from)->assertSessionHas('message', 'Hoàn tác thành công!');
        $this->assertNotSoftDeleted($first);
        $this->actingAs($admin)->patch(route('cate_blog.restore', 999))->assertNotFound();

        $first->delete();
        $second->delete();
        $this->actingAs($admin)->from($from)->patch(route('cate_blog.restoreAll'))
            ->assertRedirect($from)->assertSessionHas('message', 'Hoàn tác thành công!');
        $this->assertSame(0, CateNewsModel::onlyTrashed()->count());

        $first->delete();
        $this->actingAs($admin)->from($from)->delete(route('cate_blog.delete', $first->cate_news_id))
            ->assertRedirect($from)->assertSessionHas('message', 'Xóa thành công!');
        $this->assertModelMissing($first);
        $this->actingAs($admin)->delete(route('cate_blog.delete', 999))->assertNotFound();

        $second->delete();
        $this->actingAs($admin)->from($from)->delete(route('cate_blog.deleteAll'))
            ->assertRedirect($from)->assertSessionHas('message', 'Xóa tất cả thành công!');
        $this->assertModelMissing($second);
    }

    // -------------------------------------------------------------- tags

    public function test_danh_sach_tag_va_trang_them_sua(): void
    {
        $tag = $this->tag('Chạy bộ');
        $this->tag('Bóng rổ');
        $admin = $this->admin();

        $tags = $this->actingAs($admin)->get(route('tags.index', ['keyword' => 'Chạy']))
            ->assertOk()->assertViewIs('backend.pages.blog.tags.tag_list')->assertSee('Chạy bộ')
            ->viewData('tags');
        $this->assertSame(['Chạy bộ'], collect($tags->items())->pluck('tag_content')->all());

        $this->actingAs($admin)->get(route('tags.create'))->assertOk()->assertViewIs('backend.pages.blog.tags.tag_create');
        $this->actingAs($admin)->get(route('tags.edit', $tag->tag_id))->assertOk()->assertViewIs('backend.pages.blog.tags.tag_edit')->assertSee('Chạy bộ');
        $this->actingAs($admin)->get(route('tags.edit', 999))
            ->assertRedirect('admin/tags')
            ->assertSessionHas('message', 'Không tồn tại thẻ tag này');
    }

    public function test_them_tag_hop_le_luu_tag(): void
    {
        $this->actingAs($this->admin())->post(route('tags.store'), ['contentTg' => 'Chạy bộ', 'slug' => 'chay-bo', 'hidden' => '1'])
            ->assertRedirect(route('tags.index'))
            ->assertSessionHas('message', 'Thêm mới thành công!');

        $this->assertDatabaseHas('tags', ['tag_content' => 'Chạy bộ', 'tag_slug' => 'chay-bo', 'tag_hidden' => 1]);

        $this->actingAs($this->admin())->post(route('tags.store'), ['contentTg' => 'Bóng rổ', 'slug' => 'bong-ro'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tags', ['tag_content' => 'Bóng rổ', 'tag_hidden' => 0]);
    }

    public function test_them_tag_sai_du_lieu_bao_loi_tieng_viet(): void
    {
        $this->tag('Chạy bộ');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('tags.store'), [])
            ->assertSessionHasErrors([
                'contentTg' => 'Vui lòng nhập nội dung thẻ tag!',
                'slug' => 'Vui lòng nhập đường dẫn thẻ tag!',
            ]);

        $this->actingAs($admin)->post(route('tags.store'), ['contentTg' => 'Chạy bộ', 'slug' => 'chay-bo'])
            ->assertSessionHasErrors([
                'contentTg' => 'Nội dung thẻ đã có, vui lòng nhập nội dung mới!',
                'slug' => 'Nội dung thẻ đã có, vui lòng nhập đường dẫn mới!',
            ]);

        $this->actingAs($admin)->post(route('tags.store'), ['contentTg' => str_repeat('a', 101), 'slug' => str_repeat('a', 101)])
            ->assertSessionHasErrors([
                'contentTg' => 'Nội dung thẻ quá dài!',
                'slug' => 'Đường dẫn thẻ quá dài!',
            ]);

        $this->assertDatabaseCount('tags', 1);
    }

    public function test_cap_nhat_tag_luu_noi_dung_moi(): void
    {
        $tag = $this->tag('Chạy bộ');

        $this->actingAs($this->admin())->put(route('tags.update', $tag->tag_id), [
            'id' => $tag->tag_id, 'contentTg' => 'Chạy bộ', 'slug' => 'chay-bo-dem',
        ])
            ->assertRedirect(route('tags.index'))
            ->assertSessionHas('message', 'Chỉnh sửa thành công!');

        $tag->refresh();
        $this->assertSame('chay-bo-dem', $tag->tag_slug);
        $this->assertSame(0, (int) $tag->tag_hidden);
    }

    public function test_cap_nhat_tag_sai_du_lieu_hoac_khong_ton_tai(): void
    {
        $this->tag('Bóng rổ');
        $tag = $this->tag('Chạy bộ');
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('tags.update', $tag->tag_id), ['id' => $tag->tag_id, 'contentTg' => 'Bóng rổ', 'slug' => 'ab'])
            ->assertSessionHasErrors([
                'contentTg' => 'Nội dung tag đã tồn tại, vui lòng nhập nội dung tag mới',
                'slug' => 'Đường dẫn thẻ quá ngắn!',
            ]);
        $this->actingAs($admin)->put(route('tags.update', $tag->tag_id), ['id' => $tag->tag_id])
            ->assertSessionHasErrors([
                'contentTg' => 'Vui lòng nhập nội dung tag bài viết!',
                'slug' => 'Vui lòng nhập đường dẫn thẻ tag!',
            ]);
        $this->assertSame('Chạy bộ', $tag->refresh()->tag_content);

        $this->actingAs($admin)->put(route('tags.update', 999), ['id' => 999, 'contentTg' => 'Không có', 'slug' => 'khong-co'])
            ->assertRedirect(route('tags.index'))
            ->assertSessionHas('message', 'Không tồn tại thẻ tag này!');
    }

    public function test_xoa_tag_khong_ton_tai_quay_lai_va_bao_khong_ton_tai(): void
    {
        $this->actingAs($this->admin())->from(route('tags.index'))->delete(route('tags.destroy', 999))
            ->assertRedirect(route('tags.index'))
            ->assertSessionHas('message', 'Không tồn tại thẻ tag này!')
            ->assertSessionHas('iconMessage', 'info');
    }

    public function test_an_hien_tag_tra_json(): void
    {
        $tag = $this->tag('Chạy bộ');

        $this->actingAs($this->admin())->post(route('tag.status', $tag->tag_id), ['tag-status' => 0])
            ->assertOk()->assertJson(['message' => 'Cập nhật thành công']);
        $this->assertSame(0, (int) $tag->refresh()->tag_hidden);

        $this->actingAs($this->admin())->post(route('tag.status', 999), ['tag-status' => 0])
            ->assertNotFound()->assertJson(['message' => 'Không tìm thấy dữ liệu']);
    }

    public function test_thung_rac_tag_xoa_khoi_phuc_va_xoa_vinh_vien(): void
    {
        $first = $this->tag('Chạy bộ');
        $second = $this->tag('Bóng rổ');
        $admin = $this->admin();
        $from = route('tag.trashed');

        $this->actingAs($admin)->delete(route('tags.destroy', $first->tag_id))
            ->assertRedirect('/admin/tags')
            ->assertSessionHas('message', 'Xóa thành công');
        $this->assertSoftDeleted($first);

        $this->actingAs($admin)->get($from)->assertOk()->assertViewIs('backend.pages.blog.tags.tag_trash')->assertSee('Chạy bộ');

        $this->actingAs($admin)->from($from)->patch(route('tag.restore', $first->tag_id))
            ->assertRedirect($from)->assertSessionHas('message', 'Hoàn tác thành công!');
        $this->assertNotSoftDeleted($first);
        $this->actingAs($admin)->patch(route('tag.restore', 999))->assertNotFound();

        $first->delete();
        $second->delete();
        $this->actingAs($admin)->from($from)->patch(route('tag.restoreAll'))
            ->assertRedirect($from)->assertSessionHas('message', 'Hoàn tác thành công!');
        $this->assertSame(0, TagsModel::onlyTrashed()->count());

        $first->delete();
        $this->actingAs($admin)->from($from)->delete(route('tag.delete', $first->tag_id))
            ->assertRedirect($from)->assertSessionHas('message', 'Xóa thành công!');
        $this->assertModelMissing($first);
        $this->actingAs($admin)->delete(route('tag.delete', 999))->assertNotFound();

        $second->delete();
        $this->actingAs($admin)->from($from)->delete(route('tag.deleteAll'))
            ->assertRedirect($from)->assertSessionHas('message', 'Xóa tất cả thành công!');
        $this->assertModelMissing($second);
    }

    public function test_nut_xoa_hoi_xac_nhan_bang_hop_thoai_chung(): void
    {
        $admin = $this->admin();
        $news = $this->news();
        $tag = $this->tag('Chạy bộ');
        $category = $this->category('Tin tức', 'tin-tuc');

        $this->actingAs($admin)->get(route('blog.index'))->assertSee('data-confirm="Bạn muốn xóa bài viết này?"', false);
        $this->actingAs($admin)->get(route('blog-category.index'))->assertSee('data-confirm="Bạn muốn xóa mục này?"', false);
        $this->actingAs($admin)->get(route('tags.index'))->assertSee('data-confirm="Bạn muốn xóa tag này?"', false);

        $news->delete();
        $tag->delete();
        $category->delete();
        $this->actingAs($admin)->get(route('blog.trashed'))
            ->assertSee('data-confirm="Bạn có chắc muốn xóa bài viết này vĩnh viễn?"', false)
            ->assertSee('data-confirm="Bạn có chắc muốn xóa tất cả bài viết vĩnh viễn?"', false);
        $this->actingAs($admin)->get(route('cate_blog.trashed'))
            ->assertSee('data-confirm="Bạn có chắc muốn xóa danh mục bài viết này vĩnh viễn?"', false)
            ->assertSee('data-confirm="Bạn có chắc muốn xóa tất cả danh mục bài viết vĩnh viễn?"', false);
        $this->actingAs($admin)->get(route('tag.trashed'))
            ->assertSee('data-confirm="Bạn có chắc muốn xóa thẻ tag vĩnh viễn?"', false)
            ->assertSee('data-confirm="Bạn có chắc muốn xóa tất cả thẻ tag vĩnh viễn?"', false);
    }

    // -------------------------------------------------------------- helpers

    /**
     * The store requests validate uniqueness against the connection named
     * `mysql`, which under test would reach the developer's MySQL server.
     * Here that name is made to share the in-memory SQLite connection.
     */
    private function pointMysqlRulesAtTestDatabase(): void
    {
        config(['database.connections.mysql' => config('database.connections.sqlite')]);
        DB::purge('mysql');
        DB::connection('mysql')->setPdo(DB::connection()->getPdo());
    }

    /**
     * @return array<int, string>
     */
    private function uploadedFiles(): array
    {
        return glob(public_path(self::UPLOAD_DIR.'/*')) ?: [];
    }

    public function test_xoa_vinh_vien_danh_muc_con_bai_viet_trong_thung_rac_bi_chan(): void
    {
        $admin = $this->admin();
        $category = $this->category('Tin tức', 'tin-tuc');
        $this->news(category: $category)->delete();
        $category->delete();

        $this->actingAs($admin)->delete(route('cate_blog.delete', $category->cate_news_id))
            ->assertSessionHas('message', 'Danh mục còn bài viết, kể cả trong thùng rác. Hãy xóa vĩnh viễn các bài viết đó trước!');
        $this->actingAs($admin)->delete(route('cate_blog.deleteAll'))
            ->assertSessionHas('message', 'Còn 1 danh mục có bài viết nên được giữ lại trong thùng rác.');

        $this->assertSoftDeleted($category);
    }

    private function admin(): UserModel
    {
        $admin = UserModel::where('username', 'quantri')->first()
            ?? $this->makeUser('quantri@example.test', 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate(self::PERMISSION, 'web'));

        return $admin;
    }

    private function category(string $name, string $slug = 'kinh-nghiem'): CateNewsModel
    {
        return CateNewsModel::create([
            'cate_news_name' => $name,
            'cate_news_slug' => $slug,
            'cate_news_sort' => 1,
            'cate_news_hidden' => 1,
        ]);
    }

    private function tag(string $content): TagsModel
    {
        return TagsModel::create(['tag_content' => $content, 'tag_slug' => str($content)->slug()]);
    }

    private function news(string $title = self::TITLE, string $slug = 'cach-buoc-day-giay.html', ?CateNewsModel $category = null): NewsModel
    {
        $category ??= CateNewsModel::first() ?? $this->category('Kinh nghiệm');

        return NewsModel::create([
            'news_title' => $title,
            'news_slug' => $slug,
            'news_summarize' => self::SUMMARY,
            'news_content' => self::CONTENT,
            'post_date' => today()->toDateString(),
            'cate_news_id' => $category->cate_news_id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function articleForm(CateNewsModel $category, array $overrides = []): array
    {
        return $overrides + [
            'title' => self::TITLE,
            'slug' => 'cach-buoc-day-giay',
            'summarize' => self::SUMMARY,
            'content' => self::CONTENT,
            'post_date' => today()->format('d-m-Y'),
            'cate' => $category->cate_news_id,
            'hidden' => '1',
            'hot' => '1',
            'created_by' => 'Ban biên tập',
            'img_blog' => UploadedFile::fake()->image('anh-bia.jpg'),
        ];
    }

    public function test_them_bai_viet_hop_le_luu_bai_va_nguoi_dang(): void
    {
        $category = $this->category('Kinh nghiệm');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('blog.store'), $this->articleForm($category))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $news = NewsModel::where('news_title', self::TITLE)->firstOrFail();
        $this->assertSame($admin->user_id, (int) $news->user_id);
        $this->assertSame('cach-buoc-day-giay.html', $news->news_slug);
        $this->assertSame($category->cate_news_id, (int) $news->cate_news_id);
    }
}
