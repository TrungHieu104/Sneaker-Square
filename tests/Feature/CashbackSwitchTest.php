<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Enums\CustomerSegment;
use App\Models\OrderCashbackModel;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Services\CustomerSegments\Cashback;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\ShippingCarrier;
use App\Services\ShopSettings;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SegmentFixtures;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The shop decides whether segments earn money back at all. Nothing here turns
 * the switch on until a test asks, which is how a shop starts out.
 */
class CashbackSwitchTest extends TestCase
{
    use RefreshDatabase;
    use SegmentFixtures;
    use ShopFixtures;

    private const RETURN_DAYS = 7;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->app->instance(ShippingCarrier::class, new FakeCarrier);
        app(ShopSettings::class)->setReturnDays(self::RETURN_DAYS);
    }

    private function loyal(): UserModel
    {
        $customer = $this->customer();
        $this->inSegment($customer, CustomerSegment::Loyal);

        return $customer;
    }

    private function placeOrder(UserModel $user): OrderModel
    {
        return app(PlaceOrderAction::class)->execute(
            $user,
            [$this->cartLine($this->makeProduct(price: 1_000_000, slug: 'giay-'.$user->user_id), 1)],
            null,
            $this->makeAddress($user),
            ['payment' => 'COD', 'note_customer' => null],
        );
    }

    private function settingsAdmin(): UserModel
    {
        $admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web'));

        return $admin;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function offerForm(): array
    {
        return array_map(fn (array $offer) => ['discount' => $offer['discount'], 'cashback' => $offer['cashback'], 'cap_enabled' => $offer['cap'] ? '1' : null, 'cap' => $offer['cap']], ShopSettings::SEGMENT_OFFER_DEFAULTS);
    }

    public function test_mac_dinh_tat_thi_don_moi_khong_duoc_hoan(): void
    {
        $vip = $this->loyal();

        $this->assertFalse(app(ShopSettings::class)->segmentCashbackEnabled());
        $this->assertNull(app(Cashback::class)->quote((int) $vip->user_id, 1_000_000));
        $this->assertNull(OrderCashbackModel::find($this->placeOrder($vip)->order_id));
    }

    public function test_mac_dinh_tat_thi_trang_thanh_toan_khong_co_dong_hoan(): void
    {
        $product = $this->makeProduct(price: 3_000_000, slug: 'giay-dat');

        $this->actingAs($this->loyal())->withSession(['cart' => [$this->cartLine($product, 1)]])->get(route('product.checkout'))
            ->assertOk()
            ->assertDontSee('Hoàn vào ví SPay');
    }

    public function test_bat_cong_tac_thi_don_moi_duoc_hoan(): void
    {
        app(ShopSettings::class)->setSegmentCashbackEnabled(true);

        $order = $this->placeOrder($this->loyal());

        $this->assertSame(50_000, (int) OrderCashbackModel::findOrFail($order->order_id)->amount);
    }

    public function test_tat_cong_tac_van_tra_tien_cho_don_da_chot(): void
    {
        $settings = app(ShopSettings::class);
        $settings->setSegmentCashbackEnabled(true);
        $customer = $this->customer();
        $order = $this->completedOrder($customer, daysAgo: self::RETURN_DAYS + 1);
        OrderCashbackModel::create(['order_id' => $order->order_id, 'segment' => CustomerSegment::Loyal->value, 'percent' => 5, 'cap' => 100_000, 'amount' => 100_000]);

        $settings->setSegmentCashbackEnabled(false);
        $this->artisan('orders:pay-cashback')->assertSuccessful();

        $this->assertSame(100_000, (int) app(WalletService::class)->for($customer)->balance);
    }

    public function test_admin_bat_cong_tac_trong_cau_hinh(): void
    {
        $this->actingAs($this->settingsAdmin())->put(route('setting.update_segments'), ['cashback_enabled' => '1', 'offers' => $this->offerForm()])
            ->assertRedirect(route('setting.edit').'#tab-phan-khuc');

        $this->assertTrue(app(ShopSettings::class)->segmentCashbackEnabled());
    }

    public function test_tat_cong_tac_van_giu_muc_hoan_de_bat_lai(): void
    {
        $settings = app(ShopSettings::class);
        $settings->setSegmentCashbackEnabled(true);
        $form = $this->offerForm();
        $form['loyal']['cashback'] = 7;

        $this->actingAs($this->settingsAdmin())->put(route('setting.update_segments'), ['offers' => $form]);

        $this->assertFalse($settings->segmentCashbackEnabled());
        $this->assertSame(7, $settings->segmentOffer(CustomerSegment::Loyal)['cashback']);
    }

    public function test_trang_cau_hinh_hien_dung_trang_thai_cong_tac(): void
    {
        $admin = $this->settingsAdmin();

        $this->actingAs($admin)->get(route('setting.edit'))
            ->assertOk()
            ->assertSee('Hoàn tiền vào ví SPay')
            ->assertDontSee('Mức giảm là gợi ý khi gửi ưu đãi')
            ->assertDontSee('id="cashback-enabled" name="cashback_enabled" value="1" style="width: 2.75em; height: 1.5em;" checked', false);

        app(ShopSettings::class)->setSegmentCashbackEnabled(true);

        $this->actingAs($admin)->get(route('setting.edit'))
            ->assertSee('id="cashback-enabled" name="cashback_enabled" value="1" style="width: 2.75em; height: 1.5em;" checked', false);
    }
}
