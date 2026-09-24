<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\OrderReturnModel;
use App\Models\UserModel;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\ShippingCarrier;
use App\Services\Shipping\ShippingUnavailable;
use App\Services\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The local-only page that plays GHN's part: every status, including those
 * the sandbox refuses, fed through the same tracker the webhook uses.
 */
class GhnSimulatorTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $admin;

    private FakeCarrier $carrier;

    private OrderModel $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->carrier = new FakeCarrier;
        $this->app->instance(ShippingCarrier::class, $this->carrier);
        config(['services.ghn.simulator' => true]);

        $customer = $this->makeUser();
        $this->order = app(PlaceOrderAction::class)->execute(
            $customer,
            [$this->cartLine($this->makeProduct(slug: 'giay-gia-lap'))],
            null,
            $this->makeAddress($customer),
            ['payment' => 'COD', 'note_customer' => null],
        );
        $this->order->order_status = OrderStatus::Confirmed;
        $this->order->order_shipping_code = 'LSIM01';
        $this->order->save();

        $this->admin = $this->makeUser(email: 'donhang@example.test', username: 'quanlydon', role: 1);
        $this->admin->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web'));
    }

    private function send(string $status, string $code = 'LSIM01')
    {
        return $this->actingAs($this->admin)->post(route('ghn_simulator.send'), ['ma' => $code, 'status' => $status]);
    }

    public function test_trang_liet_ke_van_don_va_moi_trang_thai(): void
    {
        $this->actingAs($this->admin)
            ->get(route('ghn_simulator.index'))
            ->assertOk()
            ->assertSee('LSIM01')
            ->assertSee('value="delivered"', false)
            ->assertSee('value="returned"', false)
            ->assertSee('switch-status/storing');
    }

    public function test_trang_thai_ghn_cho_goi_thi_co_them_nut_ghn_that(): void
    {
        $page = $this->actingAs($this->admin)->get(route('ghn_simulator.index'));

        $page->assertSee(route('ghn_simulator.real'), false);
        $page->assertSee('GHN thật');
    }

    public function test_trang_thai_co_api_nhung_sandbox_khong_toi_duoc_thi_ghi_ro(): void
    {
        $this->actingAs($this->admin)
            ->get(route('ghn_simulator.index'))
            ->assertSee('Có API, chưa tới được')
            ->assertSee('GHN chỉ nhận khi vận đơn đã qua bước lấy hàng');
    }

    public function test_nut_ghn_that_goi_api_chuyen_trang_thai_va_khong_tu_ghi_hanh_trinh(): void
    {
        $this->actingAs($this->admin)
            ->post(route('ghn_simulator.real'), ['ma' => 'LSIM01', 'status' => 'storing'])
            ->assertRedirect(route('ghn_simulator.index', ['ma' => 'LSIM01']));

        $this->assertSame([['code' => 'LSIM01', 'status' => 'storing']], $this->carrier->switched);
        $this->assertSame(0, $this->order->shipmentEvents()->count());
        $this->assertNull($this->order->fresh()->order_shipping_status);
    }

    public function test_nut_ghn_that_tu_choi_trang_thai_ghn_khong_cho_shop_goi(): void
    {
        $this->actingAs($this->admin)
            ->post(route('ghn_simulator.real'), ['ma' => 'LSIM01', 'status' => 'delivered'])
            ->assertSessionHasErrors('status');

        $this->assertSame([], $this->carrier->switched);
    }

    public function test_ghn_tu_choi_thi_bao_loi_cho_admin(): void
    {
        $this->app->instance(ShippingCarrier::class, new class extends FakeCarrier
        {
            public function switchStatus(string $code, string $status): void
            {
                throw new ShippingUnavailable('GHN từ chối yêu cầu: khong the chuyen trang thai');
            }
        });

        $this->actingAs($this->admin)
            ->post(route('ghn_simulator.real'), ['ma' => 'LSIM01', 'status' => 'return'])
            ->assertSessionHas('message', 'GHN từ chối yêu cầu: khong the chuyen trang thai');
    }

    public function test_gui_giao_hang_thanh_cong_cap_nhat_don_nhu_webhook(): void
    {
        $this->send('delivering')->assertRedirect(route('ghn_simulator.index', ['ma' => 'LSIM01']));
        $this->send('delivered');

        $fresh = $this->order->fresh();
        $this->assertSame('delivered', $fresh->order_shipping_status);
        $this->assertNotNull($fresh->order_delivered_at);
        $this->assertTrue($fresh->isAwaitingReceipt());
    }

    public function test_thong_bao_co_dau_ngoac_kep_khong_bi_escape_thanh_html(): void
    {
        $this->send('picking');

        $page = $this->actingAs($this->admin)->get(route('ghn_simulator.index', ['ma' => 'LSIM01']));

        $page->assertDontSee('&quot;', false);
        $page->assertSee('JSON.parse', false);
    }

    public function test_bam_lien_tiep_trong_cung_mot_giay_van_ghi_du_cac_chang(): void
    {
        foreach (['picking', 'picked', 'transporting', 'delivering', 'delivered'] as $status) {
            $this->send($status);
        }

        $this->assertSame(5, $this->order->shipmentEvents()->count());
        $this->assertSame('delivered', $this->order->fresh()->order_shipping_status);
    }

    public function test_chay_tu_hoan_thanh_gia_dinh_da_qua_so_ngay(): void
    {
        app(ShopSettings::class)->setAutoCompleteDays(3);
        $this->send('delivered');

        $this->actingAs($this->admin)->post(route('ghn_simulator.auto_complete'), ['ma' => 'LSIM01', 'days_later' => 2]);
        $this->assertSame(OrderStatus::Delivered, $this->order->fresh()->order_status);

        $this->actingAs($this->admin)->post(route('ghn_simulator.auto_complete'), ['ma' => 'LSIM01', 'days_later' => 4]);
        $this->assertSame(OrderStatus::Completed, $this->order->fresh()->order_status);
    }

    public function test_gui_cho_van_don_tra_hang_khong_dung_don_giao_di(): void
    {
        $this->order->forceFill(['order_status' => OrderStatus::Returned, 'order_shipping_status' => 'delivered'])->save();
        $return = OrderReturnModel::create([
            'order_id' => $this->order->order_id,
            'status' => OrderReturnModel::APPROVED,
            'reason' => 'sai_size',
            'refund_info' => 'VCB 0123',
        ]);
        $return->return_shipping_code = 'LTRA99';
        $return->save();

        $this->send('picked', 'LTRA99');

        $this->assertSame('picked', $return->fresh()->return_shipping_status);
        $this->assertSame('delivered', $this->order->fresh()->order_shipping_status);
    }

    public function test_ma_van_don_la_bi_tu_choi(): void
    {
        $this->send('delivered', 'KHONGCO')->assertSessionHasErrors('ma');
    }

    public function test_trang_thai_la_bi_tu_choi(): void
    {
        $this->send('bay_ve_troi')->assertSessionHasErrors('status');
    }

    public function test_tat_co_thi_trang_khong_ton_tai(): void
    {
        config(['services.ghn.simulator' => false]);

        $this->actingAs($this->admin)->get(route('ghn_simulator.index'))->assertNotFound();
        $this->send('delivered')->assertNotFound();
        $this->assertNull($this->order->fresh()->order_shipping_status);
    }

    public function test_production_khong_bao_gio_bat(): void
    {
        $this->app['env'] = 'production';

        $this->actingAs($this->admin)->get(route('ghn_simulator.index'))->assertNotFound();
        $this->send('delivered');
        $this->assertNull($this->order->fresh()->order_shipping_status);
    }

    public function test_khong_co_quyen_don_hang_thi_bi_chan(): void
    {
        $staff = $this->makeUser(email: 'nhanvien@example.test', username: 'nhanvien', role: 1);

        $this->actingAs($staff)->get(route('ghn_simulator.index'))->assertForbidden();
    }
}
