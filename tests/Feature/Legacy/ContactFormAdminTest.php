<?php

namespace Tests\Feature\Legacy;

use App\Mail\ContactMail;
use App\Models\ContactFormModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterises the staff screens for contact messages: who may open them, the
 * three handling states, soft delete, the trash and permanent deletion.
 */
class ContactFormAdminTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const PERMISSION = 'Khách hàng liên hệ';

    private const DELETED = 'Xóa thành công';

    private const EMPTY_TRASH = 'Không có dữ liệu trong thùng rác!';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Http::fake();
        Http::preventStrayRequests();
    }

    private function staff(bool $withPermission = true): UserModel
    {
        $staff = $this->makeUser('quantri@example.test', 'quantri', role: 1);
        $permission = Permission::findOrCreate(self::PERMISSION, 'web');

        if ($withPermission) {
            $staff->givePermissionTo($permission);
        }

        return $staff;
    }

    private function message(string $title = 'Hỏi về size giày', int $status = 0): ContactFormModel
    {
        $contact = ContactFormModel::create([
            'name' => 'Nguyễn Văn An',
            'email' => 'khachhang@gmail.com',
            'phone' => '0912345678',
            'title' => $title,
            'content' => 'Chân 26cm thì nên chọn size nào?',
        ]);
        $contact->status = $status;
        $contact->save();

        return $contact;
    }

    // -------------------------------------------------------------- access

    public function test_khach_chua_dang_nhap_bi_dua_ve_trang_dang_nhap_admin(): void
    {
        $this->get(route('contact-form.index'))
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('message', 'Không được phép truy cập');
    }

    public function test_tai_khoan_khach_hang_khong_vao_duoc_danh_sach_lien_he(): void
    {
        $customer = $this->makeUser();

        $this->actingAs($customer)->get(route('contact-form.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_nhan_vien_khong_co_quyen_lien_he_bi_chan_moi_thao_tac(): void
    {
        $contact = $this->message();
        $contact->delete();
        $other = $this->message('Câu hỏi khác');
        $staff = $this->staff(withPermission: false);

        $this->actingAs($staff)->get(route('contact-form.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('contact-form-trashed'))->assertForbidden();
        $this->actingAs($staff)->put(route('contact.handle', $other->id))->assertForbidden();
        $this->actingAs($staff)->delete(route('contact-form.destroy', $other->id))->assertForbidden();
        $this->actingAs($staff)->delete(route('contact-form.forceDelete', $contact->id))->assertForbidden();
        $this->actingAs($staff)->delete(route('contact-form.delete.all'))->assertForbidden();

        $this->assertSame(0, (int) $other->refresh()->status);
        $this->assertSoftDeleted('contact_forms', ['id' => $contact->id]);
        $this->assertNotSoftDeleted('contact_forms', ['id' => $other->id]);
    }

    // -------------------------------------------------------------- listing

    public function test_danh_sach_lien_he_hien_tin_nhan_moi_nhat_truoc(): void
    {
        $old = $this->message('Tin cũ');
        $old->created_at = now()->subDay();
        $old->save();
        $this->message('Tin mới');

        $this->actingAs($this->staff())->get(route('contact-form.index'))
            ->assertOk()
            ->assertSeeInOrder(['Tin mới', 'Tin cũ'])
            ->assertSee('khachhang@gmail.com')
            ->assertSee('Chân 26cm thì nên chọn size nào?');
    }

    public function test_danh_sach_lien_he_rong_thi_bao_khong_co_lien_lac(): void
    {
        $this->actingAs($this->staff())->get(route('contact-form.index'))
            ->assertOk()
            ->assertSee('Không có liên lạc nào mới');
    }

    public function test_danh_sach_lien_he_phan_trang_10_tin_moi_trang(): void
    {
        foreach (range(1, 11) as $i) {
            $this->message('Tin số '.$i);
        }

        $response = $this->actingAs($this->staff())->get(route('contact-form.index'))->assertOk();

        $this->assertCount(10, $response->viewData('contact'));
        $this->assertSame(11, $response->viewData('contact')->total());
    }

    // -------------------------------------------------------------- answer

    public function test_doi_trang_thai_da_xu_ly_dang_xu_ly_chua_xu_ly(): void
    {
        $contact = $this->message();
        $staff = $this->staff();

        $this->actingAs($staff)->from(route('contact-form.index'))->put(route('contact.processing', $contact->id))
            ->assertRedirect(route('contact-form.index'))
            ->assertSessionHas('message', 'Đang xử lý')
            ->assertSessionHas('iconMessage', 'warning');
        $this->assertSame(1, (int) $contact->refresh()->status);

        $this->actingAs($staff)->from(route('contact-form.index'))->put(route('contact.handle', $contact->id))
            ->assertRedirect(route('contact-form.index'))
            ->assertSessionHas('message', 'Đã xử lý')
            ->assertSessionHas('iconMessage', 'success');
        $this->assertSame(2, (int) $contact->refresh()->status);

        $this->actingAs($staff)->from(route('contact-form.index'))->put(route('contact.no_process', $contact->id))
            ->assertRedirect(route('contact-form.index'))
            ->assertSessionHas('message', 'Chưa xử lý')
            ->assertSessionHas('iconMessage', 'error');
        $this->assertSame(0, (int) $contact->refresh()->status);
    }

    public function test_them_lien_he_tu_trang_admin_luu_va_gui_mail_nhu_storefront(): void
    {
        $this->actingAs($this->staff())->from(route('contact-form.index'))->post(route('contact-form.store'), [
            'name' => 'Trần Thị Bình',
            'email' => 'binh@gmail.com',
            'phone' => '0987654321',
            'title' => 'Đổi hàng',
            'content' => 'Tôi muốn đổi size.',
        ])
            ->assertRedirect(route('contact-form.index'))
            ->assertSessionHas('message', 'Đã gửi thông tin');

        $this->assertDatabaseHas('contact_forms', ['email' => 'binh@gmail.com', 'title' => 'Đổi hàng', 'status' => 0]);
        Mail::assertQueued(ContactMail::class, fn (ContactMail $mail) => $mail->hasTo('binh@gmail.com'));
    }

    public function test_them_lien_he_tu_admin_sai_du_lieu_bao_loi_tieng_viet(): void
    {
        $this->actingAs($this->staff())->post(route('contact-form.store'), ['email' => 'binh@yahoo.com'])
            ->assertSessionHasErrors([
                'name' => 'Bạn chưa nhập họ và tên',
                'email' => 'Bạn nhập email sai định dạng',
                'title' => 'Vui lòng nhập tiêu đề',
                'content' => 'Vui lòng nhập nội dung',
            ]);

        $this->assertDatabaseCount('contact_forms', 0);
        Mail::assertNothingQueued();
    }

    // -------------------------------------------------------------- delete

    public function test_xoa_lien_he_chuyen_vao_thung_rac(): void
    {
        $contact = $this->message();

        $this->actingAs($this->staff())->from(route('contact-form.index'))->delete(route('contact-form.destroy', $contact->id))
            ->assertRedirect(route('contact-form.index'))
            ->assertSessionHas('message', self::DELETED)
            ->assertSessionHas('iconMessage', 'success');

        $this->assertSoftDeleted('contact_forms', ['id' => $contact->id]);
    }

    public function test_xoa_lien_he_khong_ton_tai_bao_loi(): void
    {
        $this->actingAs($this->staff())->from(route('contact-form.index'))->delete(route('contact-form.destroy', 999))
            ->assertRedirect(route('contact-form.index'))
            ->assertSessionHas('message', 'Không có liên hệ')
            ->assertSessionHas('iconMessage', 'error');
    }

    public function test_thung_rac_chi_hien_lien_he_da_xoa(): void
    {
        $this->message('Tin còn giữ');
        $this->message('Tin đã xoá')->delete();

        $this->actingAs($this->staff())->get(route('contact-form-trashed'))
            ->assertOk()
            ->assertSee('Tin đã xoá')
            ->assertDontSee('Tin còn giữ');
    }

    public function test_khoi_phuc_mot_lien_he_tu_thung_rac(): void
    {
        $contact = $this->message();
        $contact->delete();

        $this->actingAs($this->staff())->from(route('contact-form-trashed'))->patch(route('contact-form.restore', $contact->id))
            ->assertRedirect(route('contact-form-trashed'))
            ->assertSessionHas('message', 'Khôi phục thành công');

        $this->assertNotSoftDeleted('contact_forms', ['id' => $contact->id]);
    }

    public function test_khoi_phuc_hoac_xoa_vinh_vien_lien_he_khong_ton_tai_tra_404(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->patch(route('contact-form.restore', 999))->assertNotFound();
        $this->actingAs($staff)->delete(route('contact-form.forceDelete', 999))->assertNotFound();
    }

    public function test_xoa_vinh_vien_mot_lien_he(): void
    {
        $contact = $this->message();
        $contact->delete();

        $this->actingAs($this->staff())->from(route('contact-form-trashed'))->delete(route('contact-form.forceDelete', $contact->id))
            ->assertRedirect(route('contact-form-trashed'))
            ->assertSessionHas('message', self::DELETED);

        $this->assertDatabaseMissing('contact_forms', ['id' => $contact->id]);
    }

    public function test_khoi_phuc_tat_ca_lien_he_trong_thung_rac(): void
    {
        $this->message('Tin một')->delete();
        $this->message('Tin hai')->delete();

        $this->actingAs($this->staff())->from(route('contact-form-trashed'))->patch(route('contact-form.restoreAll'))
            ->assertRedirect(route('contact-form-trashed'))
            ->assertSessionHas('message', 'Hoàn tác thành công!')
            ->assertSessionHas('iconMessage', 'success');

        $this->assertSame(0, ContactFormModel::onlyTrashed()->count());
        $this->assertSame(2, ContactFormModel::count());
    }

    public function test_xoa_tat_ca_chi_xoa_lien_he_trong_thung_rac(): void
    {
        $kept = $this->message('Tin còn giữ');
        $this->message('Tin một')->delete();
        $this->message('Tin hai')->delete();

        $this->actingAs($this->staff())->from(route('contact-form-trashed'))->delete(route('contact-form.delete.all'))
            ->assertRedirect(route('contact-form-trashed'))
            ->assertSessionHas('message', 'Xóa liên hệ thành công!');

        $this->assertSame(0, ContactFormModel::withTrashed()->whereKeyNot($kept->id)->count());
        $this->assertNotSoftDeleted('contact_forms', ['id' => $kept->id]);
    }

    public function test_thung_rac_rong_thi_khoi_phuc_va_xoa_tat_ca_bao_khong_co_du_lieu(): void
    {
        $this->message();
        $staff = $this->staff();

        $this->actingAs($staff)->patch(route('contact-form.restoreAll'))
            ->assertSessionHas('message', self::EMPTY_TRASH)
            ->assertSessionHas('iconMessage', 'info');
        $this->actingAs($staff)->delete(route('contact-form.delete.all'))
            ->assertSessionHas('message', self::EMPTY_TRASH)
            ->assertSessionHas('iconMessage', 'info');

        $this->assertSame(1, ContactFormModel::count());
    }
}
