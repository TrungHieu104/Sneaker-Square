<?php

namespace Tests\Feature\Legacy;

use App\Mail\ContactMail;
use App\Models\ContactFormModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Characterises the storefront contact form: what a valid message stores, the
 * confirmation mail it sends back to the sender, and every validation message.
 */
class ContactFormSubmitTest extends TestCase
{
    use RefreshDatabase;

    private const SENDER = 'khachhang@gmail.com';

    protected function setUp(): void
    {
        parent::setUp();

        // The dev .env holds a real SMTP account and the storefront carries a
        // reCAPTCHA widget; neither may be reached from a test.
        Mail::fake();
        Http::fake();
        Http::preventStrayRequests();
    }

    /**
     * @return array<string, string>
     */
    private function validMessage(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nguyễn Văn An',
            'email' => self::SENDER,
            'phone' => '0912345678',
            'title' => 'Hỏi về size giày',
            'content' => 'Chân 26cm thì nên chọn size nào?',
        ], $overrides);
    }

    private function submit(array $data)
    {
        return $this->from(route('contact.page'))->post(route('contact-form.sto'), $data);
    }

    public function test_trang_lien_he_hien_form_gui_thong_tin(): void
    {
        $this->get(route('contact.page'))
            ->assertOk()
            ->assertSee(route('contact-form.sto'), false)
            ->assertSee('name="content"', false);
    }

    public function test_loi_kiem_tra_giu_lai_noi_dung_da_nhap(): void
    {
        $this->followingRedirects()
            ->submit($this->validMessage(['phone' => '123']))
            ->assertSee('Số điện thoại gồm 10 chữ số, bắt đầu bằng 0')
            ->assertSee('value="Nguyễn Văn An"', false)
            ->assertSee('value="123"', false)
            ->assertSee('Chân 26cm thì nên chọn size nào?');
    }

    public function test_noi_dung_da_nhap_khong_lot_sang_nguoi_xem_sau(): void
    {
        $this->followingRedirects()->submit($this->validMessage(['phone' => '123']));

        $this->flushSession();

        $this->get(route('contact.page'))
            ->assertOk()
            ->assertDontSee('Nguyễn Văn An')
            ->assertDontSee(self::SENDER);
    }

    public function test_gui_lien_he_hop_le_luu_vao_bang_va_quay_lai_trang_lien_he(): void
    {
        $this->submit($this->validMessage())
            ->assertRedirect(route('contact.page'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('message', 'Đã gửi thông tin')
            ->assertSessionHas('iconMessage', 'success');

        $this->assertDatabaseCount('contact_forms', 1);
        $this->assertDatabaseHas('contact_forms', [
            'name' => 'Nguyễn Văn An',
            'email' => self::SENDER,
            'phone' => '0912345678',
            'title' => 'Hỏi về size giày',
            'content' => 'Chân 26cm thì nên chọn size nào?',
            'status' => 0,
        ]);
    }

    public function test_gui_lien_he_thi_mail_xac_nhan_den_dia_chi_nguoi_gui(): void
    {
        $this->submit($this->validMessage());

        $stored = ContactFormModel::sole();

        // ContactMail is ShouldQueue, so send() queues it rather than sending.
        Mail::assertQueued(ContactMail::class, function (ContactMail $mail) use ($stored) {
            return $mail->hasTo(self::SENDER)
                && $mail->contact->is($stored)
                && $mail->contact->content === 'Chân 26cm thì nên chọn size nào?';
        });
        Mail::assertQueuedCount(1);
    }

    public function test_mail_lien_he_co_tieu_de_va_chao_dung_ten_nguoi_gui(): void
    {
        $contact = ContactFormModel::create($this->validMessage());

        $mail = new ContactMail($contact);

        $mail->assertHasSubject('Gửi liên hệ thành công');
        $mail->assertSeeInHtml('Nguyễn Văn An');
        $this->assertSame([], $mail->attachments());
    }

    public function test_bo_trong_cac_truong_bat_buoc_bao_loi_tieng_viet(): void
    {
        $this->submit([])
            ->assertRedirect(route('contact.page'))
            ->assertSessionHasErrors([
                'name' => 'Bạn chưa nhập họ và tên',
                'email' => 'Bạn chưa nhập email',
                'phone' => 'Bạn chưa nhập số điện thoại',
                'title' => 'Vui lòng nhập tiêu đề',
                'content' => 'Vui lòng nhập nội dung',
            ]);

        $this->assertDatabaseCount('contact_forms', 0);
        Mail::assertNothingQueued();
        Mail::assertNothingSent();
    }

    public function test_ho_ten_qua_ngan_hoac_qua_dai_bi_tu_choi(): void
    {
        $this->submit($this->validMessage(['name' => 'Anh']))
            ->assertSessionHasErrors(['name' => 'Họ và tên quá ngắn']);

        $this->submit($this->validMessage(['name' => str_repeat('a', 31)]))
            ->assertSessionHasErrors(['name' => 'Họ và tên quá dài']);

        $this->assertDatabaseCount('contact_forms', 0);
    }

    public function test_ho_ten_dung_5_va_30_ky_tu_duoc_chap_nhan(): void
    {
        $this->submit($this->validMessage(['name' => str_repeat('a', 5)]))->assertSessionHasNoErrors();
        $this->submit($this->validMessage(['name' => str_repeat('b', 30)]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('contact_forms', 2);
    }

    public function test_email_sai_dinh_dang_bao_loi(): void
    {
        $this->submit($this->validMessage(['email' => 'khong-phai-email']))
            ->assertSessionHasErrors(['email' => 'Nhập email chưa đúng']);

        $this->assertDatabaseCount('contact_forms', 0);
    }

    public function test_email_khong_phai_gmail_bi_tu_choi(): void
    {
        $this->submit($this->validMessage(['email' => 'khach@example.test']))
            ->assertSessionHasErrors(['email' => 'Bạn nhập email sai định dạng']);

        $this->assertDatabaseCount('contact_forms', 0);
        Mail::assertNothingQueued();
    }

    public function test_tieu_de_qua_255_ky_tu_bi_tu_choi(): void
    {
        $this->submit($this->validMessage(['title' => str_repeat('t', 256)]))
            ->assertSessionHasErrors(['title' => 'Tiêu đề quá dài']);

        $this->assertDatabaseCount('contact_forms', 0);
    }

    public function test_so_dien_thoai_bat_buoc_va_dung_dinh_dang(): void
    {
        foreach (['' => 'Bạn chưa nhập số điện thoại', '091234567' => 'Số điện thoại gồm 10 chữ số, bắt đầu bằng 0', '1912345678' => 'Số điện thoại gồm 10 chữ số, bắt đầu bằng 0', '09123456ab' => 'Số điện thoại gồm 10 chữ số, bắt đầu bằng 0'] as $phone => $message) {
            $this->submit($this->validMessage(['phone' => (string) $phone]))->assertSessionHasErrors(['phone' => $message]);
        }

        $this->assertSame(0, ContactFormModel::count());
        Mail::assertNothingQueued();
        Mail::assertNothingSent();
    }
}
