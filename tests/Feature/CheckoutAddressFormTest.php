<?php

namespace Tests\Feature;

use App\Models\DeliveryInfoModel;
use App\Models\UserModel;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\ShippingCarrier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The contract between the checkout form and the two scripts that drive it.
 *
 * The carrier service is chosen on the server now, so the form has no field for
 * it; a validator still looking for one blocks every submit with a TypeError,
 * and that failure is invisible outside the browser console.
 */
class CheckoutAddressFormTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->app->instance(ShippingCarrier::class, new FakeCarrier);
        $this->customer = $this->makeUser();
    }

    private function validator(): string
    {
        return (string) file_get_contents(public_path('frontend/js/validate_product_checkout.js'));
    }

    /**
     * The rule the browser applies to a name, lifted out of the script so it
     * can be exercised here rather than merely asserted to exist.
     */
    private function nameRule(): string
    {
        preg_match('#return /(.+)/\.test\(removeAscent#', $this->validator(), $found);

        return '/'.($found[1] ?? 'KHONG_TIM_THAY').'/';
    }

    // -------------------------------------------- hợp đồng giữa form và script

    public function test_form_thanh_toan_khong_con_o_chon_dich_vu_van_chuyen(): void
    {
        $product = $this->makeProduct();

        $response = $this->actingAs($this->customer)
            ->withSession(['cart' => [$this->cartLine($product)]])
            ->get('/thanh-toan');

        $response->assertOk();
        $response->assertDontSee('service-select');
    }

    public function test_script_kiem_tra_khong_con_tim_o_chon_dich_vu(): void
    {
        $this->assertStringNotContainsString('service-select', $this->validator());
    }

    public function test_script_kiem_tra_khong_gan_de_class_name(): void
    {
        // Reassigning className drops addr-pick__native, the class that keeps the
        // native select hidden behind its search box.
        $this->assertDoesNotMatchRegularExpression('/\.className\s*=/', $this->validator());
    }

    public function test_moi_o_dia_chi_deu_co_nut_tim_kiem(): void
    {
        $product = $this->makeProduct();

        $response = $this->actingAs($this->customer)
            ->withSession(['cart' => [$this->cartLine($product)]])
            ->get('/thanh-toan');

        foreach (['province-select', 'district-select', 'ward-select'] as $field) {
            $response->assertSee($field, false);
        }

        $this->assertStringContainsString('addr-pick__input', (string) file_get_contents(
            public_path('frontend/ajax/delivery.js')
        ));
    }

    // ------------------------------------------------------- luật họ và tên

    public function test_ho_ten_mot_tu_van_la_chu(): void
    {
        $this->assertMatchesRegularExpression($this->nameRule(), 'hieu');
        $this->assertMatchesRegularExpression($this->nameRule(), 'trung hieu');
    }

    public function test_ho_ten_co_so_hoac_ky_tu_dac_biet_thi_khong_phai_chu(): void
    {
        $this->assertDoesNotMatchRegularExpression($this->nameRule(), 'hieu123');
        $this->assertDoesNotMatchRegularExpression($this->nameRule(), '!!!');
        $this->assertDoesNotMatchRegularExpression($this->nameRule(), '');
    }

    public function test_luat_ho_ten_duoc_dung_theo_chieu_phu_dinh(): void
    {
        // isValidName() answers "đây là chữ"; the branch it guards is the error
        // branch, so it has to be negated there.
        $this->assertStringContainsString('!isValidName(lnameValue)', $this->validator());
    }

    // ------------------------------------------------------- lưu địa chỉ mới

    public function test_them_dia_chi_khong_can_gui_dich_vu_van_chuyen(): void
    {
        $response = $this->actingAs($this->customer)->post(route('diachi.store'), [
            'info_name' => 'Nguyễn Văn Kiểm Thử',
            'info_phone' => '0912345678',
            'info_email' => 'kiemthu@gmail.com',
            'info_address' => '12 Võ Văn Ngân',
            'info_province' => 'Hồ Chí Minh',
            'info_district' => 'Thành Phố Thủ Đức',
            'info_ward' => 'Phường Linh Chiểu',
            'info_district_id' => 3695,
            'info_ward_code' => '90742',
        ]);

        $response->assertRedirect();

        $address = DeliveryInfoModel::where('user_id', $this->customer->user_id)->firstOrFail();

        $this->assertSame(3695, (int) $address->info_district_id);
        $this->assertSame('90742', $address->info_ward_code);
        $this->assertSame(1, (int) $address->info_default);
    }

    public function test_dia_chi_dau_tien_duoc_dat_lam_mac_dinh_va_co_phi_tham_khao(): void
    {
        $this->actingAs($this->customer)->post(route('diachi.store'), [
            'info_name' => 'Nguyễn Văn Kiểm Thử',
            'info_phone' => '0912345678',
            'info_email' => 'kiemthu@gmail.com',
            'info_address' => '12 Võ Văn Ngân',
            'info_province' => 'Hồ Chí Minh',
            'info_district' => 'Thành Phố Thủ Đức',
            'info_ward' => 'Phường Linh Chiểu',
            'info_district_id' => 3695,
            'info_ward_code' => '90742',
        ]);

        $address = DeliveryInfoModel::where('user_id', $this->customer->user_id)->firstOrFail();

        $this->assertGreaterThan(0, (int) $address->info_delivery_fee);
    }
}
