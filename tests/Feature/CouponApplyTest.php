<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Models\CouponModel;
use App\Models\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * A customer typing a coupon code into the cart, and what the cart and the
 * order then take off.
 */
class CouponApplyTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private ProductModel $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->product = $this->makeProduct(price: 1_000_000, slug: 'giay-ap-ma');
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function coupon(array $fields = []): CouponModel
    {
        return CouponModel::create($fields + [
            'coupon_name' => 'Ưu đãi '.uniqid(), 'coupon_code' => 'GIAM'.strtoupper(substr(uniqid(), -6)),
            'coupon_value' => 10, 'coupon_quantity' => 5, 'coupon_used' => 0, 'coupon_condition' => 0,
            'coupon_start' => now()->subDay()->toDateString(), 'coupon_end' => now()->addDays(5)->toDateString(),
        ]);
    }

    private function apply(CouponModel $coupon, int $quantity = 2, int $claimedTotal = 2_000_000): TestResponse
    {
        return $this->withSession(['cart' => [$this->cartLine($this->product, $quantity)]])
            ->from(route('product.cart'))
            ->post(route('checkCoupon.cart'), ['coupon' => $coupon->coupon_code, 'giatri_donhang' => $claimedTotal]);
    }

    public function test_ma_hop_le_duoc_ap_vao_gio(): void
    {
        $coupon = $this->coupon();

        $this->apply($coupon)->assertSessionHas('message', 'Tuyệt quá! Bạn đã sử dụng thành công mã giảm giá 10%');

        $this->assertSame($coupon->coupon_id, session('coupon_data')->coupon_id);
    }

    public function test_ma_khong_ton_tai_het_luot_hoac_het_han_bi_tu_choi(): void
    {
        $this->withSession(['cart' => [$this->cartLine($this->product)]])
            ->post(route('checkCoupon.cart'), ['coupon' => 'KHONGCO', 'giatri_donhang' => 1_000_000])
            ->assertSessionHas('message', 'Opps!!! Mã giảm giá này không khả dụng.');

        $this->apply($this->coupon(['coupon_quantity' => 0]))->assertSessionHas('message', 'Rất tiếc! Mã giảm giá đã hết lượt sử dụng.');
        $this->apply($this->coupon(['coupon_end' => now()->subDay()->toDateString()]))->assertSessionHas('message', 'Opps!!! Mã giảm giá đã quá hạn sử dụng.');

        $this->assertNull(session('coupon_data'));
    }

    public function test_ma_giam_tien_khong_nho_hon_gia_tri_gio_bi_tu_choi(): void
    {
        $this->apply($this->coupon(['coupon_condition' => 1, 'coupon_value' => 2_000_000]))
            ->assertSessionHas('message', 'Cần đặt hàng có giá trị cao hơn để sử dụng mã giảm giá này.');

        $this->assertNull(session('coupon_data'));
    }

    // ------------------------------------------------------- minimum and cap

    public function test_ma_co_don_toi_thieu_tinh_tren_gio_cua_server_khong_tin_so_trinh_duyet_gui(): void
    {
        $coupon = $this->coupon(['coupon_min_order' => 2_500_000]);

        $this->apply($coupon, quantity: 2, claimedTotal: 9_000_000)
            ->assertSessionHas('message', 'Mã giảm giá này áp dụng cho đơn có tiền hàng từ 2.500.000 VNĐ, giỏ của bạn hiện là 2.000.000 VNĐ.');
        $this->assertNull(session('coupon_data'));

        $this->apply($coupon, quantity: 3)->assertSessionHas('message', 'Tuyệt quá! Bạn đã sử dụng thành công mã giảm giá 10%');
    }

    public function test_ma_phan_tram_khong_giam_qua_muc_toi_da(): void
    {
        $coupon = $this->coupon(['coupon_value' => 10, 'coupon_max_discount' => 150_000]);

        $this->withSession(['cart' => [$this->cartLine($this->product, 2)], 'coupon_data' => $coupon])
            ->get(route('product.cart'))
            ->assertOk()
            ->assertSee('- 150.000 VNĐ')
            ->assertSee('1.850.000')
            ->assertSee('Áp dụng: giảm tối đa 150.000 VNĐ');
    }

    public function test_gio_tut_duoi_muc_toi_thieu_thi_ma_tu_go_va_bao_ly_do(): void
    {
        $coupon = $this->coupon(['coupon_min_order' => 2_500_000]);

        $this->withSession(['cart' => [$this->cartLine($this->product, 2)], 'coupon_data' => $coupon])
            ->get(route('product.cart'))
            ->assertOk();

        $this->assertNull(session('coupon_data'));
        $this->assertStringStartsWith('Mã giảm giá đã được gỡ khỏi giỏ hàng.', session('message'));
    }

    public function test_don_dat_duoi_muc_toi_thieu_khong_duoc_giam_va_tren_muc_bi_gioi_han(): void
    {
        $user = $this->makeUser();
        $place = fn (int $quantity, CouponModel $coupon) => app(PlaceOrderAction::class)->execute(
            $user, [$this->cartLine($this->product, $quantity)], $coupon, $this->makeAddress($user), ['payment' => 'COD', 'note_customer' => null],
        );

        $this->assertSame(0, (int) $place(2, $this->coupon(['coupon_min_order' => 2_500_000]))->order_coupon_value);
        $this->assertSame(150_000, (int) $place(3, $this->coupon(['coupon_min_order' => 2_500_000, 'coupon_max_discount' => 150_000]))->order_coupon_value);
    }

    public function test_admin_dat_don_toi_thieu_va_giam_toi_da_cho_ma(): void
    {
        $admin = $this->makeUser('admin@example.test', 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Mã giảm giá', 'web'));

        $this->actingAs($admin)->post(route('coupon.store'), [
            'name' => 'Ưu đãi cuối tuần', 'code' => 'CUOITUAN', 'quantity' => 10, 'condition' => 0, 'money' => 10,
            'start_coupon' => now()->toDateString(), 'end_coupon' => now()->addDays(3)->toDateString(),
            'min_order' => 2_000_000, 'max_discount' => 200_000,
        ])->assertRedirect(route('coupon.index'));

        $coupon = CouponModel::where('coupon_code', 'CUOITUAN')->firstOrFail();
        $this->assertSame([2_000_000, 200_000], [(int) $coupon->coupon_min_order, (int) $coupon->coupon_max_discount]);
    }
}
