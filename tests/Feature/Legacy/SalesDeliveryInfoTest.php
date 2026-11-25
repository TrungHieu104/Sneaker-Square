<?php

namespace Tests\Feature\Legacy;

use App\Models\DeliveryInfoModel;
use App\Models\UserModel;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\ShippingCarrier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Characterisation of a customer's saved delivery addresses, pinned before a
 * refactor. Only a customer's own addresses are exercised here.
 */
class SalesDeliveryInfoTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    /** FakeCarrier for the 1200g parcel the controller prices: base fee plus two kilos. */
    private const INDICATIVE_FEE = 32000;

    private const NOT_FOUND = 'Không tồn tại địa chỉ này!';

    private const SWITCHED = 'Đổi địa chỉ nhận hàng thành công!';

    private FakeCarrier $carrier;

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Http::fake();
        config(['services.ghn.token' => '']);
        $this->carrier = new FakeCarrier;
        $this->app->instance(ShippingCarrier::class, $this->carrier);

        $this->customer = $this->makeUser();
    }

    // -------------------------------------------------------------- store

    public function test_dia_chi_dau_tien_thanh_mac_dinh_va_luu_phi_tham_khao(): void
    {
        $this->actingAs($this->customer)->from(route('user.delivery'))->post(route('diachi.store'), $this->payload())
            ->assertRedirect(route('user.delivery'))
            ->assertSessionHas('message', 'Thêm mới thành công!')
            ->assertSessionHas('iconMessage', 'success');

        $saved = DeliveryInfoModel::where('user_id', $this->customer->user_id)->firstOrFail();
        $this->assertSame('Nguyễn Văn B', $saved->info_name);
        $this->assertSame('0987654321', $saved->info_phone);
        $this->assertSame('khachmoi@gmail.com', $saved->info_email);
        $this->assertSame('2 Võ Văn Ngân', $saved->info_address);
        $this->assertSame('Hà Nội', $saved->info_province);
        $this->assertSame('Quận Ba Đình', $saved->info_district);
        $this->assertSame('Phường Tân Định', $saved->info_ward);
        $this->assertSame(1, (int) $saved->info_default);
        $this->assertSame(self::INDICATIVE_FEE, (int) $saved->info_delivery_fee);
        $this->assertSame(1200, $this->carrier->quoted[0]->weight);
        $this->assertSame(1442, $this->carrier->quoted[0]->toDistrictId);
    }

    public function test_da_co_dia_chi_mac_dinh_thi_dia_chi_moi_khong_mac_dinh(): void
    {
        $existing = $this->makeAddress($this->customer);

        $this->actingAs($this->customer)->post(route('diachi.store'), $this->payload())->assertSessionHasNoErrors();

        $new = DeliveryInfoModel::where('user_id', $this->customer->user_id)->latest('info_id')->firstOrFail();
        $this->assertNotSame($existing->info_id, $new->info_id);
        $this->assertSame(0, (int) $new->info_default);
        $this->assertSame(1, (int) $existing->refresh()->info_default);
    }

    public function test_them_dia_chi_thieu_thong_tin_bi_tu_choi_bang_tieng_viet(): void
    {
        $this->actingAs($this->customer)->post(route('diachi.store'), [])
            ->assertSessionHasErrors([
                'info_name' => 'Bạn chưa nhập họ và tên',
                'info_phone' => 'Bạn chưa nhập số điện thoại',
                'info_email' => 'Bạn chưa nhập email',
                'info_address' => 'Bạn chưa nhập địa chỉ giao hàng',
                'info_province' => 'Vui lòng chọn Tỉnh, Thành phố',
                'info_district' => 'Vui lòng chọn Quận, Huyện',
                'info_ward' => 'Vui lòng chọn Phường, Xã',
                'info_district_id' => 'Vui lòng chọn lại Quận, Huyện',
                'info_ward_code' => 'Vui lòng chọn lại Phường, Xã',
            ]);

        $this->assertSame(0, DeliveryInfoModel::count());
        $this->assertSame([], $this->carrier->quoted);
    }

    public function test_email_khong_phai_gmail_hoac_sai_dinh_dang_bi_tu_choi(): void
    {
        $this->actingAs($this->customer)->post(route('diachi.store'), $this->payload(['info_email' => 'khach@yahoo.com']))
            ->assertSessionHasErrors(['info_email' => 'Email sai định dạng']);
        $this->actingAs($this->customer)->post(route('diachi.store'), $this->payload(['info_email' => 'khong-phai-email@gmail.com@']))
            ->assertSessionHasErrors(['info_email' => 'Nhập email chưa đúng']);

        $this->assertSame(0, DeliveryInfoModel::count());
    }

    // ------------------------------------------------------------- update

    public function test_sua_dia_chi_cua_minh_luu_va_tinh_lai_phi(): void
    {
        $address = $this->makeAddress($this->customer, shippingFee: 1);

        $this->actingAs($this->customer)->from(route('user.delivery'))
            ->put(route('diachi.update', $address->info_id), $this->payload([
                'info_name' => 'Trần Thị C',
                'info_district_id' => 3695,
            ]))
            ->assertRedirect(route('user.delivery'))
            ->assertSessionHas('message', 'Cập nhật địa chỉ nhận hàng thành công!');

        $address->refresh();
        $this->assertSame('Trần Thị C', $address->info_name);
        $this->assertSame('2 Võ Văn Ngân', $address->info_address);
        $this->assertSame(3695, (int) $address->info_district_id);
        $this->assertSame(self::INDICATIVE_FEE, (int) $address->info_delivery_fee);
        $this->assertSame(1, (int) $address->info_default);
    }

    public function test_sua_dia_chi_thieu_thong_tin_bi_tu_choi_va_giu_nguyen(): void
    {
        $address = $this->makeAddress($this->customer);

        $this->actingAs($this->customer)->put(route('diachi.update', $address->info_id), $this->payload(['info_name' => '', 'info_ward_code' => '']))
            ->assertSessionHasErrors([
                'info_name' => 'Bạn chưa nhập họ và tên',
                'info_ward_code' => 'Vui lòng chọn lại Phường, Xã',
            ]);

        $this->assertSame('Nguyễn Văn A', $address->refresh()->info_name);
    }

    public function test_sua_dia_chi_khong_ton_tai_bao_loi(): void
    {
        $this->actingAs($this->customer)->put(route('diachi.update', 999), $this->payload())
            ->assertSessionHas('message', self::NOT_FOUND)
            ->assertSessionHas('iconMessage', 'error');

        $this->assertSame(0, DeliveryInfoModel::count());
    }

    // ------------------------------------------------------------- delete

    public function test_xoa_dia_chi_cua_minh(): void
    {
        $address = $this->makeAddress($this->customer);

        $this->actingAs($this->customer)->delete(route('diachi.destroy', $address->info_id))
            ->assertSessionHas('message', 'Xóa địa chỉ nhận hàng thành công!');

        $this->assertDatabaseMissing('delivery_info', ['info_id' => $address->info_id]);
    }

    public function test_xoa_dia_chi_khong_ton_tai_bao_loi(): void
    {
        $this->actingAs($this->customer)->delete(route('diachi.destroy', 999))
            ->assertSessionHas('message', self::NOT_FOUND)
            ->assertSessionHas('iconMessage', 'error');
    }

    // ------------------------------------------------------ default address

    public function test_chon_dia_chi_khac_lam_mac_dinh_luc_thanh_toan(): void
    {
        $first = $this->makeAddress($this->customer);
        $second = $this->makeAddress($this->customer);
        $second->info_default = 0;
        $second->save();

        $this->actingAs($this->customer)->post(route('deliInfoSelected'), ['selectInfoDefault' => $second->info_id])
            ->assertSessionHas('message', self::SWITCHED);

        $this->assertSame(0, (int) $first->refresh()->info_default);
        $this->assertSame(1, (int) $second->refresh()->info_default);
    }

    public function test_dat_dia_chi_mac_dinh_trong_tai_khoan(): void
    {
        $first = $this->makeAddress($this->customer);
        $second = $this->makeAddress($this->customer);
        $second->info_default = 0;
        $second->save();

        $this->actingAs($this->customer)->from(route('user.delivery'))
            ->post(route('deliInfoDefault'), ['info_id' => $second->info_id, 'info_default' => 1])
            ->assertRedirect(route('user.delivery'))
            ->assertSessionHas('message', self::SWITCHED);

        $this->assertSame(0, (int) $first->refresh()->info_default);
        $this->assertSame(1, (int) $second->refresh()->info_default);
    }

    public function test_chon_lai_dia_chi_dang_mac_dinh_van_giu_mac_dinh(): void
    {
        $current = $this->makeAddress($this->customer);
        $other = $this->makeAddress($this->customer);
        $other->info_default = 0;
        $other->save();

        $this->actingAs($this->customer)->post(route('deliInfoSelected'), ['selectInfoDefault' => $current->info_id])
            ->assertSessionHas('message', self::SWITCHED);
        $this->actingAs($this->customer)->post(route('deliInfoDefault'), ['info_id' => $current->info_id, 'info_default' => 1])
            ->assertSessionHas('message', self::SWITCHED);

        $this->assertSame(1, (int) $current->refresh()->info_default);
        $this->assertSame(0, (int) $other->refresh()->info_default);
    }

    public function test_ma_dia_chi_dang_mang_bi_tu_choi(): void
    {
        $address = $this->makeAddress($this->customer);

        $this->actingAs($this->customer)->post(route('deliInfoSelected'), ['selectInfoDefault' => [$address->info_id]])
            ->assertSessionHas('message', self::NOT_FOUND);
        $this->actingAs($this->customer)->post(route('deliInfoDefault'), ['info_id' => [$address->info_id], 'info_default' => 1])
            ->assertSessionHas('message', self::NOT_FOUND);
    }

    public function test_khach_chua_dang_nhap_khong_dat_duoc_dia_chi_mac_dinh(): void
    {
        $address = $this->makeAddress($this->customer);
        $address->info_default = 0;
        $address->save();

        $this->post(route('deliInfoDefault'), ['info_id' => $address->info_id, 'info_default' => 1])
            ->assertRedirect(route('user.login'));

        $this->assertSame(0, (int) $address->refresh()->info_default);
    }

    public function test_trang_dia_chi_giao_hang_liet_ke_dia_chi_cua_khach(): void
    {
        $this->makeAddress($this->customer);
        $other = $this->makeUser(email: 'khac@example.test', username: 'khachkhac');
        $theirs = $this->makeAddress($other);
        $theirs->info_address = '99 Đường Của Người Khác';
        $theirs->save();

        $this->actingAs($this->customer)->get(route('user.delivery'))
            ->assertOk()
            ->assertSee('1 Võ Văn Ngân')
            ->assertDontSee('99 Đường Của Người Khác');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'info_name' => 'Nguyễn Văn B',
            'info_phone' => '0987654321',
            'info_email' => 'khachmoi@gmail.com',
            'info_address' => '2 Võ Văn Ngân',
            'info_province' => 'Hà Nội',
            'info_district' => 'Quận Ba Đình',
            'info_ward' => 'Phường Tân Định',
            'info_district_id' => 1442,
            'info_ward_code' => '20110',
        ], $overrides);
    }

    // -------------------------------------------------------------- ownership

    public function test_khach_chua_dang_nhap_khong_sua_xoa_hay_chon_duoc_dia_chi(): void
    {
        $address = $this->makeAddress($this->customer);

        $this->delete(route('diachi.destroy', $address->info_id))->assertRedirect(route('user.login'));
        $this->put(route('diachi.update', $address->info_id), $this->payload())->assertRedirect(route('user.login'));
        $this->post(route('diachi.store'), $this->payload())->assertRedirect(route('user.login'));
        $this->post(route('deliInfoSelected'), ['selectInfoDefault' => $address->info_id])->assertRedirect(route('user.login'));

        $this->assertSame('Nguyễn Văn A', DeliveryInfoModel::findOrFail($address->info_id)->info_name);
    }

    public function test_khach_khong_sua_xoa_hay_chon_duoc_dia_chi_cua_nguoi_khac(): void
    {
        $theirs = $this->makeAddress($this->makeUser('nguoikhac@example.test', 'nguoikhac'));
        $this->makeAddress($this->customer);

        $this->actingAs($this->customer)->delete(route('diachi.destroy', $theirs->info_id))
            ->assertSessionHas('message', self::NOT_FOUND);
        $this->actingAs($this->customer)->put(route('diachi.update', $theirs->info_id), $this->payload())
            ->assertSessionHas('message', self::NOT_FOUND);
        $this->actingAs($this->customer)->post(route('deliInfoSelected'), ['selectInfoDefault' => $theirs->info_id])
            ->assertSessionHas('message', self::NOT_FOUND);
        $this->actingAs($this->customer)->post(route('deliInfoDefault'), ['info_id' => $theirs->info_id, 'info_default' => 1])
            ->assertSessionHas('message', self::NOT_FOUND);

        $theirs->refresh();
        $this->assertSame('Nguyễn Văn A', $theirs->info_name);
        $this->assertSame(1, (int) $theirs->info_default);
    }
}
