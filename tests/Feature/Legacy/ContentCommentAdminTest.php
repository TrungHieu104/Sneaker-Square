<?php

namespace Tests\Feature\Legacy;

use App\Models\CommentModel;
use App\Models\ProductModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterisation of the admin screen for product reviews: listing, hiding,
 * trashing and restoring them.
 */
class ContentCommentAdminTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const PERMISSION = 'Quản trị Sản phẩm (Bình luận)';

    private const DELETED = 'Xóa bình luận thành công!';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        $this->seedLookupTables();
    }

    public function test_khach_chua_dang_nhap_bi_chuyen_ve_dang_nhap_admin(): void
    {
        $this->get(route('comment.index'))->assertRedirect(route('admin.login'));
        $this->get(route('comment.trashed'))->assertRedirect(route('admin.login'));
    }

    public function test_nhan_vien_khong_co_quyen_binh_luan_bi_tu_choi(): void
    {
        $staff = $this->makeUser('nhanvien@example.test', 'nhanvien', role: 1);
        $comment = $this->comment('Giày đẹp, đi êm chân');

        $this->actingAs($staff)->get(route('comment.index'))->assertForbidden();
        $this->actingAs($staff)->put(route('comment.update', $comment->comment_id), ['comment_hidden' => 1])->assertForbidden();
        $this->actingAs($staff)->delete(route('comment.destroy', $comment->comment_id))->assertForbidden();

        $this->assertSame(0, (int) $comment->refresh()->comment_hidden);
        $this->assertNotSoftDeleted($comment);
    }

    public function test_danh_sach_binh_luan_hien_nguoi_viet_va_san_pham(): void
    {
        $customer = $this->makeUser();
        $this->comment('Giày đẹp, đi êm chân', user: $customer);
        $this->comment('Giao hàng hơi chậm', guestName: 'Trần Thị B');
        $this->comment('Không rõ ai viết');

        $this->actingAs($this->admin())->get(route('comment.index'))
            ->assertOk()
            ->assertViewIs('backend.pages.product.comment.comment_list')
            ->assertSee('Giày đẹp, đi êm chân')
            ->assertSee($customer->name)
            ->assertSee('Trần Thị B')
            ->assertSee('Không xác định')
            ->assertSee($this->product()->pro_name);
    }

    public function test_danh_sach_binh_luan_mac_dinh_moi_nhat_truoc(): void
    {
        $old = $this->comment('Bình luận cũ', date: now()->subDays(3));
        $new = $this->comment('Bình luận mới', date: now()->subDay());

        $default = $this->actingAs($this->admin())->get(route('comment.index'))
            ->assertOk()
            ->assertViewHas('orderBy', 'comment_date')
            ->assertViewHas('orderType', 'desc');
        $this->assertSame([$new->comment_id, $old->comment_id], collect($default->viewData('allComments')->items())->pluck('comment_id')->all());

    }

    public function test_danh_sach_binh_luan_sap_xep_theo_cot_tren_tieu_de_bang(): void
    {
        $first = $this->comment('A - đi êm chân', date: now()->subDays(3));
        $second = $this->comment('B - giao hàng nhanh', date: now()->subDay());
        $admin = $this->admin();

        $sorted = $this->actingAs($admin)->get(route('comment.index', ['sort-by' => 'comment_content', 'sort-type' => 'desc']))
            ->assertOk()
            ->assertViewHas('orderBy', 'comment_content')
            ->assertViewHas('orderType', 'asc');
        $this->assertSame([$first->comment_id, $second->comment_id], collect($sorted->viewData('allComments')->items())->pluck('comment_id')->all());

        $this->actingAs($admin)->get(route('comment.index', ['sort-by' => 'khong_co_cot']))
            ->assertOk()
            ->assertViewHas('orderBy', 'comment_date');
    }

    public function test_danh_sach_binh_luan_tim_theo_noi_dung(): void
    {
        $this->comment('Giày đẹp, đi êm chân');
        $this->comment('Giao hàng hơi chậm');

        $found = $this->actingAs($this->admin())->get(route('comment.index', ['keyword' => 'chậm']))
            ->assertOk()
            ->viewData('allComments');

        $this->assertSame(['Giao hàng hơi chậm'], collect($found->items())->pluck('comment_content')->all());
    }

    public function test_an_hien_binh_luan_tra_json(): void
    {
        $comment = $this->comment('Giày đẹp, đi êm chân');

        $this->actingAs($this->admin())->put(route('comment.update', $comment->comment_id), ['comment_hidden' => 1])
            ->assertOk()->assertJson(['message' => 'Cập nhật thành công']);
        $this->assertSame(1, (int) $comment->refresh()->comment_hidden);

        $this->actingAs($this->admin())->put(route('comment.update', 999), ['comment_hidden' => 1])
            ->assertNotFound()->assertJson(['message' => 'Không tìm thấy dữ liệu']);
    }

    public function test_xoa_binh_luan_khong_ton_tai_tra_404(): void
    {
        $this->actingAs($this->admin())->delete(route('comment.destroy', 999))->assertNotFound();
    }

    public function test_thung_rac_binh_luan_xoa_khoi_phuc_va_xoa_vinh_vien(): void
    {
        $first = $this->comment('Giày đẹp, đi êm chân', user: $this->makeUser());
        $second = $this->comment('Giao hàng hơi chậm');
        $admin = $this->admin();
        $from = route('comment.trashed');

        $this->actingAs($admin)->delete(route('comment.destroy', $first->comment_id))
            ->assertRedirect(route('comment.index'))
            ->assertSessionHas('message', self::DELETED);
        $this->assertSoftDeleted($first);

        $this->actingAs($admin)->get($from)
            ->assertOk()
            ->assertViewIs('backend.pages.product.comment.comment_trash')
            ->assertSee('Giày đẹp, đi êm chân')
            ->assertDontSee('Giao hàng hơi chậm');

        $this->actingAs($admin)->from($from)->patch(route('comment.restore', $first->comment_id))
            ->assertRedirect($from)->assertSessionHas('message', 'Khôi phục bình luận thành công!');
        $this->assertNotSoftDeleted($first);
        $this->actingAs($admin)->patch(route('comment.restore', 999))->assertNotFound();

        $first->delete();
        $second->delete();
        $this->actingAs($admin)->patch(route('comment.restore.all'))
            ->assertRedirect(route('comment.index'))
            ->assertSessionHas('message', 'Khôi phục tất cả bình luận thành công!');
        $this->assertSame(0, CommentModel::onlyTrashed()->count());

        $first->delete();
        $this->actingAs($admin)->from($from)->delete(route('comment.delete', $first->comment_id))
            ->assertRedirect($from)->assertSessionHas('message', self::DELETED);
        $this->assertModelMissing($first);
        $this->actingAs($admin)->delete(route('comment.delete', 999))->assertNotFound();

        $second->delete();
        $this->actingAs($admin)->delete(route('comment.delete.all'))
            ->assertRedirect(route('comment.index'))
            ->assertSessionHas('message', 'Xóa tất cả bình luận thành công!');
        $this->assertModelMissing($second);
    }

    public function test_nut_xoa_hoi_xac_nhan_bang_hop_thoai_chung(): void
    {
        $this->comment('Giày đẹp, đi êm chân');
        $this->comment('Giao hàng hơi chậm')->delete();

        $this->actingAs($this->admin())->get(route('comment.index'))
            ->assertSee('data-confirm="Bạn muốn đưa đánh giá này vào thùng rác?"', false);
        $this->actingAs($this->admin())->get(route('comment.trashed'))
            ->assertSee('data-confirm="Bạn có chắc muốn xóa tất cả đánh giá vĩnh viễn?"', false)
            ->assertSee('data-confirm="Bạn có chắc muốn xóa đánh giá này vĩnh viễn?"', false);
    }

    // -------------------------------------------------------------- helpers

    private function admin(): UserModel
    {
        $admin = UserModel::where('username', 'quantri')->first()
            ?? $this->makeUser('quantri@example.test', 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate(self::PERMISSION, 'web'));

        return $admin;
    }

    private function product(): ProductModel
    {
        return ProductModel::where('pro_slug', 'nike-air')->first() ?? $this->makeProduct();
    }

    private function comment(string $content, ?UserModel $user = null, ?string $guestName = null, mixed $date = null): CommentModel
    {
        return CommentModel::create([
            'comment_content' => $content,
            'comment_date' => $date ?? now(),
            'rating' => 5,
            'pro_id' => $this->product()->pro_id,
            'user_id' => $user?->user_id,
            'comment_name' => $guestName,
        ]);
    }
}
