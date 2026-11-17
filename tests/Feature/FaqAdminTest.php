<?php

namespace Tests\Feature;

use App\Models\FaqGroupModel;
use App\Models\FaqModel;
use App\Models\UserModel;
use Database\Seeders\FaqSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\ResponseCache\Facades\ResponseCache;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The admin screens for the storefront FAQ: groups and the questions in them.
 */
class FaqAdminTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const PERMISSION = 'Quản trị Chính sách & FAQ';

    private const QUESTION = 'Shop giao hàng bằng đơn vị nào?';

    // -------------------------------------------------------------- access

    public function test_admin_khong_co_quyen_khong_vao_duoc_cau_hoi_va_nhom(): void
    {
        $faq = $this->question($this->group('Giao hàng'), self::QUESTION);
        $admin = $this->admin(withPermission: false);

        $this->actingAs($admin)->get(route('faq.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('faq_group.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('faq.status', $faq->faq_id), ['faq-status' => 0])->assertForbidden();
        $this->assertSame(1, (int) $faq->refresh()->faq_hidden);
    }

    // -------------------------------------------------------------- groups

    public function test_them_nhom_luon_dung_cuoi_danh_sach(): void
    {
        $this->group('Giao hàng', position: 4);

        $this->actingAs($this->admin())->get(route('faq_group.index'))->assertOk()->assertDontSee('name="position"', false);

        $this->actingAs($this->admin())->post(route('faq_group.store'), ['name' => ' Bảo hành ', 'position' => '1', 'status' => '1'])
            ->assertRedirect(route('faq_group.index'))
            ->assertSessionHas('message', 'Thêm nhóm thành công');

        $this->assertDatabaseHas('faq_groups', ['group_name' => 'Bảo hành', 'group_position' => 5, 'group_hidden' => 1]);
    }

    public function test_ten_nhom_trung_bi_tu_choi_nhung_sua_giu_ten_cu_thi_duoc(): void
    {
        $group = $this->group('Giao hàng');

        $this->actingAs($this->admin())->post(route('faq_group.store'), ['name' => 'Giao hàng', 'status' => '1'])
            ->assertSessionHasErrors(['name' => 'Tên nhóm đã tồn tại!']);

        $this->actingAs($this->admin())->put(route('faq_group.update', $group->group_id), ['name' => 'Giao hàng', 'position' => 9, 'status' => '0'])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, (int) $group->refresh()->group_position);
        $this->assertSame(0, (int) $group->group_hidden);
    }

    public function test_man_nhom_dem_so_cau_hoi_va_mo_form_sua(): void
    {
        $group = $this->group('Giao hàng');
        $this->question($group, self::QUESTION);
        $this->question($group, 'Phí giao hàng bao nhiêu?');

        $this->actingAs($this->admin())->get(route('faq_group.index'))->assertOk()->assertSee('Nhóm còn câu hỏi, hãy chuyển hoặc xoá các câu hỏi trước')->assertDontSee('Còn 2 câu hỏi');
        $this->actingAs($this->admin())->get(route('faq_group.edit', $group->group_id))->assertOk()->assertSee('Sửa nhóm');
    }

    public function test_nut_xoa_nhom_con_cau_hoi_bi_tat_nhom_rong_bam_duoc(): void
    {
        $this->question($this->group('Giao hàng'), self::QUESTION);
        $this->group('Bảo hành');

        $html = $this->actingAs($this->admin())->get(route('faq_group.index'))->assertOk()->getContent();

        preg_match_all('/<button [^>]*row-action-delete[^>]*>/', $html, $buttons);
        $this->assertCount(2, $buttons[0]);
        $this->assertSame(1, count(preg_grep('/disabled/', $buttons[0])));
    }

    public function test_khong_xoa_duoc_nhom_con_cau_hoi_xoa_duoc_nhom_rong(): void
    {
        $full = $this->group('Giao hàng');
        $this->question($full, self::QUESTION);
        $empty = $this->group('Bảo hành');

        $this->actingAs($this->admin())->delete(route('faq_group.destroy', $full->group_id))
            ->assertSessionHas('message', 'Nhóm còn câu hỏi, hãy chuyển hoặc xoá các câu hỏi trước');
        $this->assertDatabaseHas('faq_groups', ['group_id' => $full->group_id]);

        $this->actingAs($this->admin())->delete(route('faq_group.destroy', $empty->group_id))
            ->assertSessionHas('message', 'Đã xoá nhóm');
        $this->assertDatabaseMissing('faq_groups', ['group_id' => $empty->group_id]);
    }

    // ----------------------------------------------------------- questions

    public function test_them_cau_hoi_ghi_nguoi_tao_va_dung_cuoi_nhom(): void
    {
        $group = $this->group('Giao hàng');
        $this->question($group, 'Phí giao hàng bao nhiêu?', position: 3);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('faq.create'))->assertOk()->assertSee('Giao hàng');

        $this->actingAs($admin)->post(route('faq.store'), [
            'question' => self::QUESTION,
            'group_id' => $group->group_id,
            'answer' => '<p>Shop giao qua GHN.</p>',
            'position' => '',
            'status' => '1',
        ])->assertRedirect(route('faq.index'))->assertSessionHas('message', 'Thêm câu hỏi thành công');

        $this->assertDatabaseHas('faqs', [
            'faq_question' => self::QUESTION,
            'faq_answer' => '<p>Shop giao qua GHN.</p>',
            'faq_position' => 4,
            'faq_hidden' => 1,
            'faq_created_by' => $admin->name,
        ]);
    }

    public function test_cau_hoi_thieu_noi_dung_hoac_nhom_khong_ton_tai_bi_tu_choi(): void
    {
        $this->actingAs($this->admin())->post(route('faq.store'), ['question' => 'Hỏi', 'group_id' => 999, 'answer' => '', 'status' => '1'])
            ->assertSessionHasErrors([
                'question' => 'Câu hỏi quá ngắn!',
                'group_id' => 'Nhóm không tồn tại!',
                'answer' => 'Vui lòng nhập câu trả lời!',
            ]);

        $this->assertSame(0, FaqModel::count());
    }

    public function test_sua_cau_hoi_doi_nhom_thi_dung_cuoi_nhom_moi_va_ghi_nguoi_sua(): void
    {
        $faq = $this->question($this->group('Giao hàng'), self::QUESTION);
        $other = $this->group('Đặt hàng & thanh toán');
        $this->question($other, 'Shop nhận những hình thức thanh toán nào?');
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('faq.edit', $faq->faq_id))->assertOk()->assertSee(self::QUESTION);

        $this->actingAs($admin)->put(route('faq.update', $faq->faq_id), [
            'question' => 'Đơn giao bằng hãng nào?',
            'group_id' => $other->group_id,
            'answer' => '<p>GHN.</p>',
            'status' => '0',
        ])->assertRedirect(route('faq.index'));

        $faq->refresh();
        $this->assertSame('Đơn giao bằng hãng nào?', $faq->faq_question);
        $this->assertSame($other->group_id, (int) $faq->group_id);
        $this->assertSame(2, (int) $faq->faq_position);
        $this->assertSame(0, (int) $faq->faq_hidden);
        $this->assertSame($admin->name, $faq->faq_updated_by);
    }

    public function test_danh_sach_loc_theo_nhom_va_tu_khoa(): void
    {
        $shipping = $this->group('Giao hàng', position: 2);
        $payment = $this->group('Đặt hàng & thanh toán', position: 1);
        $this->question($shipping, 'Phí giao hàng bao nhiêu?', position: 2);
        $this->question($shipping, self::QUESTION, position: 1);
        $this->question($payment, 'Shop nhận những hình thức thanh toán nào?');

        $this->actingAs($this->admin())->get(route('faq.index'))
            ->assertOk()
            ->assertSeeInOrder(['Shop nhận những hình thức thanh toán nào?', self::QUESTION, 'Phí giao hàng bao nhiêu?']);

        $this->actingAs($this->admin())->get(route('faq.index', ['group' => $shipping->group_id]))
            ->assertSee(self::QUESTION)
            ->assertDontSee('Shop nhận những hình thức thanh toán nào?');

        $this->actingAs($this->admin())->get(route('faq.index', ['keyword' => 'thanh toán']))
            ->assertSee('Shop nhận những hình thức thanh toán nào?')
            ->assertDontSee(self::QUESTION);
    }

    public function test_bat_tat_hien_thi_va_xoa_cau_hoi(): void
    {
        $faq = $this->question($this->group('Giao hàng'), self::QUESTION);

        $this->actingAs($this->admin())->post(route('faq.status', $faq->faq_id), ['faq-status' => 0])
            ->assertOk()
            ->assertJson(['message' => 'Cập nhật thành công']);
        $this->assertSame(0, (int) $faq->refresh()->faq_hidden);

        $this->actingAs($this->admin())->delete(route('faq.destroy', $faq->faq_id))->assertSessionHas('message', 'Đã xoá câu hỏi');
        $this->assertDatabaseMissing('faqs', ['faq_id' => $faq->faq_id]);
    }

    public function test_doi_cau_hoi_hay_nhom_thi_xoa_cache_storefront(): void
    {
        $group = $this->group('Giao hàng');
        $faq = $this->question($group, self::QUESTION);
        ResponseCache::spy();

        $this->actingAs($this->admin())->post(route('faq.status', $faq->faq_id), ['faq-status' => 0]);
        $this->actingAs($this->admin())->put(route('faq_group.update', $group->group_id), ['name' => 'Vận chuyển', 'status' => '1']);

        ResponseCache::shouldHaveReceived('clear')->twice();
    }

    // ------------------------------------------------------------ ordering

    public function test_keo_tha_nhom_luu_thu_tu_moi_va_xoa_cache_storefront(): void
    {
        $first = $this->group('Đặt hàng & thanh toán', position: 1);
        $second = $this->group('Giao hàng', position: 2);
        $third = $this->group('Đổi trả & hoàn tiền', position: 3);
        ResponseCache::spy();

        $this->actingAs($this->admin())->postJson(route('faq_group.reorder'), ['ids' => [$third->group_id, $first->group_id, $second->group_id]])
            ->assertOk()
            ->assertJson(['message' => 'Đã lưu thứ tự nhóm']);

        $this->assertSame([2, 3, 1], [(int) $first->refresh()->group_position, (int) $second->refresh()->group_position, (int) $third->refresh()->group_position]);
        ResponseCache::shouldHaveReceived('clear');
    }

    public function test_keo_tha_nhom_voi_danh_sach_da_cu_thi_khong_luu(): void
    {
        $first = $this->group('Đặt hàng & thanh toán', position: 1);
        $second = $this->group('Giao hàng', position: 2);
        $this->group('Đổi trả & hoàn tiền', position: 3);

        $this->actingAs($this->admin())->postJson(route('faq_group.reorder'), ['ids' => [$second->group_id, $first->group_id]])
            ->assertStatus(409)
            ->assertJson(['message' => 'Danh sách đã thay đổi, hãy tải lại trang rồi kéo lại.']);
        $this->actingAs($this->admin())->postJson(route('faq_group.reorder'), ['ids' => [$second->group_id, $second->group_id]])
            ->assertUnprocessable();

        $this->assertSame(1, (int) $first->refresh()->group_position);
        $this->assertSame(2, (int) $second->refresh()->group_position);
    }

    public function test_keo_tha_cau_hoi_chi_trong_mot_nhom(): void
    {
        $shipping = $this->group('Giao hàng');
        $a = $this->question($shipping, self::QUESTION, position: 1);
        $b = $this->question($shipping, 'Phí giao hàng bao nhiêu?', position: 2);
        $other = $this->question($this->group('Bảo hành'), 'Bảo hành bao lâu?');

        $this->actingAs($this->admin())->postJson(route('faq.reorder', $shipping->group_id), ['ids' => [$b->faq_id, $a->faq_id]])
            ->assertOk()
            ->assertJson(['message' => 'Đã lưu thứ tự câu hỏi']);
        $this->assertSame(2, (int) $a->refresh()->faq_position);
        $this->assertSame(1, (int) $b->refresh()->faq_position);

        $this->actingAs($this->admin())->postJson(route('faq.reorder', $shipping->group_id), ['ids' => [$b->faq_id, $other->faq_id]])
            ->assertStatus(409);
        $this->assertSame(1, (int) $other->refresh()->faq_position);
    }

    public function test_chi_bat_keo_tha_khi_loc_mot_nhom_va_hien_du_cau_cua_nhom(): void
    {
        $shipping = $this->group('Giao hàng');
        foreach (range(1, 12) as $i) {
            $this->question($shipping, 'Câu hỏi giao hàng số '.$i, position: $i);
        }

        $this->actingAs($this->admin())->get(route('faq.index', ['group' => $shipping->group_id]))
            ->assertOk()
            ->assertSee('data-sortable-url', false)
            ->assertSee('Câu hỏi giao hàng số 12');

        $this->actingAs($this->admin())->get(route('faq.index'))
            ->assertOk()
            ->assertDontSee('data-sortable-url', false);
    }

    public function test_admin_khong_co_quyen_khong_keo_tha_duoc(): void
    {
        $group = $this->group('Giao hàng');

        $this->actingAs($this->admin(withPermission: false))->postJson(route('faq_group.reorder'), ['ids' => [$group->group_id]])->assertForbidden();
    }

    public function test_seeder_tao_sau_nhom_ba_muoi_ba_cau_va_chay_lai_khong_nhan_doi(): void
    {
        $this->seed(FaqSeeder::class);
        $this->seed(FaqSeeder::class);

        $this->assertSame(6, FaqGroupModel::count());
        $this->assertSame(33, FaqModel::count());
        $this->assertSame(['Đặt hàng & thanh toán', 'Giao hàng', 'Đổi trả & hoàn tiền', 'Size & sản phẩm', 'Tài khoản & ví SPay', 'Mã giảm giá & ưu đãi'],
            FaqGroupModel::orderBy('group_position')->pluck('group_name')->all());
    }

    private function admin(bool $withPermission = true): UserModel
    {
        $admin = UserModel::where('email', 'admin@example.test')->first()
            ?? $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $permission = Permission::findOrCreate(self::PERMISSION, 'web');

        if ($withPermission) {
            $admin->givePermissionTo($permission);
        }

        return $admin;
    }

    private function group(string $name, int $position = 1, bool $visible = true): FaqGroupModel
    {
        return FaqGroupModel::create(['group_name' => $name, 'group_position' => $position, 'group_hidden' => $visible ? 1 : 0]);
    }

    private function question(FaqGroupModel $group, string $question, int $position = 1, bool $visible = true): FaqModel
    {
        return FaqModel::create([
            'group_id' => $group->group_id,
            'faq_question' => $question,
            'faq_answer' => '<p>Trả lời cho: '.$question.'</p>',
            'faq_position' => $position,
            'faq_hidden' => $visible ? 1 : 0,
        ]);
    }
}
