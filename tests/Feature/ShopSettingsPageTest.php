<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Mail\ConfirmOrder;
use App\Models\OrderDetailModel;
use App\Models\UserModel;
use App\Services\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\AssertsTooltips;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The system configuration page: the order rules, the payment window, and
 * the shop's own contact details that used to live on a page of their own.
 */
class ShopSettingsPageTest extends TestCase
{
    use AssertsTooltips;
    use RefreshDatabase;
    use ShopFixtures;

    private const THONG_TIN = [
        'address' => '12 Lê Lợi, Quận 1, TP.Hồ Chí Minh',
        'phone' => '0901234567',
        'email' => 'lienhe@sneakersquare.test',
        'map_embed' => '<iframe src="https://www.google.com/maps/embed?pb=ban-do-moi"></iframe>',
        'fanpage_embed' => '<div class="fb-page" data-href="https://facebook.com/sneakersquare"></div>',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
    }

    private function adminWith(string ...$permissions): UserModel
    {
        $admin = $this->makeUser(email: uniqid().'@example.test', username: uniqid('ad'), role: 1);

        foreach ($permissions as $permission) {
            $admin->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $admin;
    }

    private function orderRules(int $minutes): array
    {
        return ['auto_complete_days' => 7, 'return_days' => 7, 'payment_window_minutes' => $minutes];
    }

    // ---------------------------------------------------- payment window

    public function test_admin_doi_thoi_gian_giu_hang_thi_don_moi_theo_gia_tri_moi(): void
    {
        $this->actingAs($this->adminWith('Quản trị Đơn hàng'))
            ->put(route('setting.update'), $this->orderRules(45))
            ->assertRedirect(route('setting.edit').'#tab-don-hang');

        $this->assertSame(45, app(ShopSettings::class)->paymentWindowMinutes());

        $khach = $this->makeUser();
        // Whole seconds: the column drops microseconds, so a frozen clock
        // that kept them would never compare equal.
        $this->freezeSecond();
        $order = app(PlaceOrderAction::class)->execute(
            $khach,
            [$this->cartLine($this->makeProduct(stock: 10))],
            null,
            $this->makeAddress($khach),
            ['payment' => 'redirect', 'note_customer' => null],
        );

        $this->assertTrue(now()->addMinutes(45)->equalTo($order->fresh()->order_payment_due_at));
    }

    public function test_thoi_gian_giu_hang_phai_nam_trong_gioi_han(): void
    {
        $admin = $this->adminWith('Quản trị Đơn hàng');

        foreach ([ShopSettings::MIN_PAYMENT_WINDOW - 1, ShopSettings::MAX_PAYMENT_WINDOW + 1] as $phut) {
            $this->actingAs($admin)
                ->put(route('setting.update'), $this->orderRules($phut))
                ->assertSessionHasErrors('payment_window_minutes');
        }

        $this->assertSame(ShopSettings::DEFAULT_PAYMENT_WINDOW, app(ShopSettings::class)->paymentWindowMinutes());
    }

    // ------------------------------------------------------- shop info

    public function test_luu_thong_tin_cua_hang_thi_trang_ban_hang_doi_theo(): void
    {
        $this->actingAs($this->adminWith('Quản trị Thông tin'))
            ->put(route('setting.update_general'), self::THONG_TIN)
            ->assertRedirect(route('setting.edit').'#tab-chung');

        $this->get(route('contact.page'))
            ->assertOk()
            ->assertSee('12 Lê Lợi, Quận 1, TP.Hồ Chí Minh')
            ->assertSee('lienhe@sneakersquare.test')
            ->assertSee('ban-do-moi', false);
    }

    public function test_thong_tin_cua_hang_sai_thi_bi_tu_choi(): void
    {
        $this->actingAs($this->adminWith('Quản trị Thông tin'))
            ->put(route('setting.update_general'), ['phone' => '0901 234 567', 'email' => 'khong-phai-email'] + self::THONG_TIN)
            ->assertSessionHasErrors(['phone', 'email']);

        $this->assertSame('', app(ShopSettings::class)->shopInfo()['phone']);
    }

    public function test_email_va_hoa_don_in_thong_tin_da_cau_hinh(): void
    {
        app(ShopSettings::class)->setShopInfo(self::THONG_TIN);

        $khach = $this->makeUser();
        $order = app(PlaceOrderAction::class)->execute(
            $khach,
            [$this->cartLine($this->makeProduct(stock: 10))],
            null,
            $this->makeAddress($khach),
            ['payment' => 'cod', 'note_customer' => null],
        );

        // The bill prints the name of whoever is signed in, as it is only
        // ever produced for the customer who owns the order.
        $this->actingAs($khach);
        $hoaDon = view('frontend.pages.product.pdf.print_bill', [
            'order' => $order,
            'od' => OrderDetailModel::where('order_id', $order->order_id)->get(),
        ])->render();
        $this->assertStringContainsString('12 Lê Lợi, Quận 1, TP.Hồ Chí Minh', $hoaDon);
        $this->assertStringContainsString('0901 234 567', $hoaDon);
        // Hai bản hoá đơn từng ghi hai địa chỉ và hai số khác nhau.
        $this->assertStringNotContainsString('0917403833', $hoaDon);

        $email = (new ConfirmOrder($order))->render();
        $this->assertStringContainsString('Hotline: 0901 234 567', $email);
        $this->assertStringNotContainsString('0123456789', $email);
    }

    public function test_nut_ho_tro_o_trang_dang_nhap_goi_dung_so_cua_hang(): void
    {
        app(ShopSettings::class)->setShopInfo(self::THONG_TIN);

        $this->get(route('user.login'))
            ->assertOk()
            ->assertSee('href="tel:0901234567"', false);
    }

    // ----------------------------------------------------- notes in tooltips

    public function test_huong_dan_lay_ma_ban_do_nam_trong_tooltip_con_canh_bao_ma_nhung_van_hien(): void
    {
        $page = $this->actingAs($this->adminWith('Quản trị Thông tin'))->get(route('setting.edit'))->assertOk();

        $this->assertOnlyInTooltip($page, 'Bản đồ Google Maps', 'Google Maps → Chia sẻ → Nhúng bản đồ → Sao chép HTML. Hiện ở trang Liên hệ.');
        $page->assertSee('Mã được chèn thẳng vào trang')->assertSee('Hiện ở chân trang.');
    }

    public function test_cach_tinh_tu_hoan_thanh_va_giu_hang_nam_trong_tooltip(): void
    {
        $page = $this->actingAs($this->adminWith('Quản trị Đơn hàng'))->get(route('setting.edit'))->assertOk();

        $this->assertOnlyInTooltip($page, 'Tự động hoàn thành đơn sau (ngày)',
            'Tính từ lúc GHN báo giao hàng thành công. Nếu khách không bấm "Đã nhận được hàng" trong khoảng này, đơn tự chuyển sang Thành công. Áp dụng cả cho các đơn đang chờ.');
        $this->assertOnlyInTooltip($page, 'Giữ hàng chờ thanh toán qua VNPay / MoMo (phút)',
            'Tính từ lúc khách đặt hàng. Hết thời gian mà chưa trả tiền thì đơn tự huỷ, hàng và mã giảm giá được trả lại. Chỉ áp dụng cho đơn đặt sau khi lưu; đơn đang chờ giữ hạn cũ.');
        $page->assertSee('Quá hạn này khách không gửi được yêu cầu trả hàng.');
    }

    public function test_gioi_thieu_nhac_gio_hang_va_cach_tang_ma_nam_trong_tooltip(): void
    {
        $page = $this->actingAs($this->adminWith('Quản trị Đơn hàng'))->get(route('setting.edit'))->assertOk();

        $this->assertOnlyInTooltip($page, 'Email nhắc giỏ hàng bỏ quên',
            'Chỉ gửi cho khách đã đăng nhập khi thêm vào giỏ. Mỗi giỏ được nhắc tự động tối đa hai lần, không kèm mã giảm giá, dừng ngay khi khách đặt hàng hoặc bấm huỷ nhận trong email. Hệ thống kiểm tra mỗi giờ một lần.');
        $this->assertOnlyInTooltip($page, 'Tặng mã giảm giá',
            'Mã chỉ được tặng khi admin bấm ở trang Giỏ hàng đang treo: mỗi lần tạo một mã riêng, dùng được một lần, được áp sẵn khi khách bấm nút trong email.');
        $page->assertSee('Tính từ lần cuối khách thêm hoặc bỏ sản phẩm trong giỏ.');
    }

    // ------------------------------------------------------ permissions

    public function test_nguoi_chi_quan_ly_thong_tin_chi_thay_tab_cua_minh(): void
    {
        $admin = $this->adminWith('Quản trị Thông tin');

        $this->actingAs($admin)->get(route('setting.edit'))
            ->assertOk()
            ->assertSee('data-bs-target="#tab-chung"', false)
            ->assertDontSee('data-bs-target="#tab-don-hang"', false);

        $this->actingAs($admin)->put(route('setting.update'), $this->orderRules(60))->assertForbidden();
        $this->assertSame(ShopSettings::DEFAULT_PAYMENT_WINDOW, app(ShopSettings::class)->paymentWindowMinutes());
    }

    public function test_nguoi_chi_quan_ly_don_khong_sua_duoc_thong_tin_cua_hang(): void
    {
        $admin = $this->adminWith('Quản trị Đơn hàng');

        $this->actingAs($admin)->get(route('setting.edit'))
            ->assertOk()
            ->assertDontSee('data-bs-target="#tab-chung"', false);

        $this->actingAs($admin)->put(route('setting.update_general'), self::THONG_TIN)->assertForbidden();
        $this->assertSame('', app(ShopSettings::class)->shopInfo()['address']);
    }

    public function test_trang_thong_tin_cu_khong_con(): void
    {
        $this->actingAs($this->adminWith('Quản trị Thông tin'))
            ->get('/admin/info-contact')
            ->assertNotFound();
    }
}
