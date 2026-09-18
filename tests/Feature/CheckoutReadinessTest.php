<?php

namespace Tests\Feature;

use App\Models\OrderModel;
use App\Models\UserModel;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\Shipment;
use App\Services\Shipping\ShippingCarrier;
use App\Services\Shipping\ShippingQuote;
use App\Services\Shipping\ShippingUnavailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * What the checkout page promises before the order is placeable.
 *
 * Every parcel leaves on a GHN service, so there is no flat fee to show while
 * the destination is unknown. A number printed there reads as final, and the
 * shop cannot honour one it never asked the carrier for.
 */
class CheckoutReadinessTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedLookupTables();
        $this->app->instance(ShippingCarrier::class, new FakeCarrier);
        $this->customer = $this->makeUser();
    }

    private function breakCarrier(): void
    {
        $this->app->instance(ShippingCarrier::class, new class extends FakeCarrier
        {
            public function quote(Shipment $shipment): ShippingQuote
            {
                throw new ShippingUnavailable('Hết thời gian chờ GHN.');
            }
        });
    }

    // ------------------------------------------------ phí khi chưa đủ điều kiện

    public function test_chua_co_dia_chi_thi_an_han_dong_phi_van_chuyen(): void
    {
        $product = $this->makeProduct();

        $response = $this->actingAs($this->customer)
            ->withSession(['cart' => [$this->cartLine($product)]])
            ->get('/thanh-toan');

        $response->assertOk();
        $response->assertDontSee('id="totalFee"', false);
        $response->assertDontSee('30.000 VNĐ');
    }

    public function test_chua_co_dia_chi_thi_thanh_tien_chi_gom_tien_hang(): void
    {
        $product = $this->makeProduct(price: 1_000_000);

        $response = $this->actingAs($this->customer)
            ->withSession(['cart' => [$this->cartLine($product)]])
            ->get('/thanh-toan');

        $response->assertSee('1.000.000 VNĐ', false);
    }

    public function test_co_dia_chi_thi_hien_phi_do_hang_van_chuyen_bao(): void
    {
        $product = $this->makeProduct();
        $this->makeAddress($this->customer);

        $response = $this->actingAs($this->customer)
            ->withSession(['cart' => [$this->cartLine($product)]])
            ->get('/thanh-toan');

        $response->assertSee('27.000 VNĐ', false);
        $response->assertSee('id="totalFee"', false);
    }

    // --------------------------------------------------------- nút đặt hàng

    public function test_chua_co_dia_chi_thi_khoa_nut_dat_hang(): void
    {
        $product = $this->makeProduct();

        $response = $this->actingAs($this->customer)
            ->withSession(['cart' => [$this->cartLine($product)]])
            ->get('/thanh-toan');

        $response->assertSee('id="btn-dat-hang" disabled', false);
        $response->assertSee('Vui lòng thêm địa chỉ nhận hàng trước khi đặt hàng.');
    }

    public function test_hang_van_chuyen_hong_thi_khoa_nut_dat_hang(): void
    {
        $product = $this->makeProduct();
        $this->makeAddress($this->customer);
        $this->breakCarrier();

        $response = $this->actingAs($this->customer)
            ->withSession(['cart' => [$this->cartLine($product)]])
            ->get('/thanh-toan');

        $response->assertSee('id="btn-dat-hang" disabled', false);
        $response->assertDontSee('id="totalFee"', false);
        $response->assertSee('Chưa tính được phí vận chuyển cho địa chỉ này, vui lòng kiểm tra lại địa chỉ nhận hàng.');
    }

    public function test_du_dieu_kien_thi_mo_nut_dat_hang(): void
    {
        $product = $this->makeProduct();
        $this->makeAddress($this->customer);

        $response = $this->actingAs($this->customer)
            ->withSession(['cart' => [$this->cartLine($product)]])
            ->get('/thanh-toan');

        $response->assertDontSee('id="btn-dat-hang" disabled', false);
        $response->assertSee('id="btn-dat-hang"', false);
    }

    // ------------------------------------------- server vẫn tự chặn của nó

    public function test_hang_van_chuyen_hong_thi_khong_ghi_don(): void
    {
        $product = $this->makeProduct();
        $this->makeAddress($this->customer);
        $this->breakCarrier();

        $response = $this->actingAs($this->customer)
            ->withSession(['cart' => [$this->cartLine($product)]])
            ->post('/thanh-toan', ['payment' => 'cod']);

        $response->assertRedirect();
        $response->assertSessionHas('message', 'Chưa tính được phí vận chuyển cho địa chỉ này, vui lòng kiểm tra lại địa chỉ nhận hàng!');
        $this->assertSame(0, OrderModel::count());
    }
}
