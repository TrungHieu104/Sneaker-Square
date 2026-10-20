<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\UserEventModel as Event;
use App\Models\UserModel;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\ShippingCarrier;
use App\Services\UserEventLog;
use Database\Seeders\UserEventSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * What a shopper does lands in user_events, under the visitor's cookie and
 * with the size and colour it concerned.
 */
class UserEventLoggingTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const VISITOR = 'abcdefghijABCDEFGHIJ0123456789abcdefghij';

    private const VNPAY_SECRET = 'bi-mat-vnpay-cho-test';

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedLookupTables();
        $this->app->instance(ShippingCarrier::class, new FakeCarrier);
        $this->customer = $this->makeUser();
        config([
            'services.vnpay.hash_secret' => self::VNPAY_SECRET,
            'services.vnpay.tmn_code' => 'TESTCODE',
            'services.vnpay.url' => 'https://vnpay.test/pay',
        ]);
    }

    private function asVisitor(): static
    {
        return $this->withCookie(UserEventLog::COOKIE, self::VISITOR);
    }

    /**
     * @return array<int, string>
     */
    private function typesLogged(): array
    {
        return Event::orderBy('event_id')->pluck('event_type')->all();
    }

    // -------------------------------------------------------- the visitor

    public function test_khach_moi_duoc_cap_ma_va_trang_san_pham_ghi_ca_luot_xem(): void
    {
        $product = $this->makeProduct(slug: 'giay-xem');

        $response = $this->get('/san-pham/giay-xem')->assertOk();

        $visitor = $response->getCookie(UserEventLog::COOKIE)?->getValue();
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{40}$/', (string) $visitor);
        $this->assertEqualsCanonicalizing([Event::VIEW_PRODUCT, Event::PAGE_VIEW], $this->typesLogged());
        $this->assertSame([$visitor], Event::distinct()->pluck('session_id')->all());
        $this->assertSame($product->pro_id, (int) Event::where('event_type', Event::VIEW_PRODUCT)->value('pro_id'));
        $this->assertSame('product.detail', Event::where('event_type', Event::PAGE_VIEW)->first()->meta['route']);
    }

    public function test_khach_quay_lai_giu_ma_cu(): void
    {
        $this->makeProduct(slug: 'giay-xem');

        $this->asVisitor()->get('/san-pham/giay-xem')->assertOk();

        $this->assertSame([self::VISITOR], Event::distinct()->pluck('session_id')->all());
    }

    public function test_bot_ajax_va_trang_admin_khong_ghi_luot_xem(): void
    {
        $this->makeProduct(slug: 'giay-xem');

        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')->get('/san-pham/giay-xem');
        $this->asVisitor()->getJson('/san-pham?sort=a-z', ['X-Requested-With' => 'XMLHttpRequest']);
        $this->asVisitor()->get('/admin');

        $this->assertNotContains(Event::PAGE_VIEW, $this->typesLogged());
        $this->assertSame(0, Event::where('event_type', Event::VIEW_PRODUCT)->count(), 'A crawler is not a shopper');
    }

    public function test_dang_nhap_thi_nhan_lai_lich_su_luc_chua_dang_nhap(): void
    {
        // LoginRequest only takes a gmail address and a password with a capital
        // letter and a digit.
        $user = $this->makeUser(email: 'khachhang@gmail.com', username: 'khachgmail');
        $user->password = Hash::make('SecretPass123!');
        $user->save();
        $this->makeProduct(slug: 'giay-xem');
        $this->asVisitor()->get('/san-pham/giay-xem');

        $this->asVisitor()->post(route('user.login_post'), [
            'email' => 'khachhang@gmail.com',
            'password' => 'SecretPass123!',
        ])->assertRedirect(route('home.page'));

        $this->assertSame(0, Event::whereNull('user_id')->count());
        $this->assertSame(2, Event::where('user_id', $user->user_id)->count());
    }

    // ------------------------------------------------- browsing the shelf

    public function test_tim_kiem_khong_ra_ket_qua_van_ghi_tu_khoa(): void
    {
        $this->asVisitor()->get('/tim-kiem?keyword=yeezy')->assertOk();

        $search = Event::where('event_type', Event::SEARCH)->firstOrFail();
        $this->assertSame(['keyword' => 'yeezy', 'results' => 0], $search->meta);
    }

    public function test_sap_xep_danh_sach_ghi_bo_loc_mot_lan(): void
    {
        $this->makeProduct(slug: 'giay-loc');

        $this->asVisitor()->get('/san-pham?sort=gia-tang')->assertOk();
        $this->asVisitor()->get('/san-pham?sort=gia-tang&page=2');

        $this->assertSame(1, Event::where('event_type', Event::FILTER)->count());
        $this->assertSame('gia-tang', Event::where('event_type', Event::FILTER)->first()->meta['sort']);
    }

    // --------------------------------------------------------------- cart

    public function test_them_gio_ghi_dung_size_mau_va_so_luong(): void
    {
        $product = $this->makeProduct(slug: 'giay-gio');
        $this->addVariant($product, colorId: 2);

        $this->asVisitor()->from('/san-pham/giay-gio')->post(route('addProduct.cart', 'giay-gio'), [
            'action' => 'stay', 'quantity' => 2, 'options-color' => 2, 'options-size' => 1,
        ]);

        $event = Event::where('event_type', Event::ADD_CART)->firstOrFail();
        $this->assertSame([$product->pro_id, 1, 2], [(int) $event->pro_id, (int) $event->size_id, (int) $event->color_id]);
        $this->assertSame(2, $event->meta['quantity']);
        $this->assertStringEndsWith('/san-pham/giay-gio', (string) $event->url, 'The page it happened on, not the endpoint');
    }

    public function test_chon_bien_the_het_hang_ghi_lan_cham_het_hang(): void
    {
        $product = $this->makeProduct(stock: 0, slug: 'giay-het');

        $this->asVisitor()->from('/san-pham/giay-het')->post(route('addProduct.cart', 'giay-het'), [
            'action' => 'stay', 'quantity' => 1, 'options-color' => 1, 'options-size' => 1,
        ]);

        $this->assertSame([Event::STOCK_OUT_HIT], $this->typesLogged());
        $this->assertSame($product->pro_id, (int) Event::value('pro_id'));
    }

    public function test_xoa_khoi_gio_ghi_dung_dong_bi_xoa(): void
    {
        $product = $this->makeProduct(slug: 'giay-xoa');

        $this->asVisitor()->withSession(['cart' => [$this->cartLine($product, 3)]])
            ->post(route('delProduct.cart', 'giay-xoa'), ['color_id' => 1, 'size_id' => 1, 'giatri_donhang' => 0]);

        $event = Event::where('event_type', Event::REMOVE_CART)->firstOrFail();
        $this->assertSame($product->pro_id, (int) $event->pro_id);
        $this->assertSame(['quantity' => 3, 'emptied_cart' => false], $event->meta);
    }

    public function test_yeu_thich_ghi_ca_them_va_bo(): void
    {
        $product = $this->makeProduct(slug: 'giay-thich');

        $this->actingAs($this->customer)->asVisitor()->post(route('product.wishlist_store'), ['product-id' => $product->pro_id]);
        $this->actingAs($this->customer)->asVisitor()->post(route('product.wishlist_store'), ['product-id' => $product->pro_id]);

        $this->assertSame(['add', 'remove'], Event::where('event_type', Event::WISHLIST)->orderBy('event_id')->get()->pluck('meta.action')->all());
    }

    // ----------------------------------------------------------- checkout

    public function test_vao_trang_thanh_toan_ghi_bat_dau_thanh_toan(): void
    {
        $product = $this->makeProduct(slug: 'giay-tt');

        $this->actingAs($this->customer)->asVisitor()
            ->withSession(['cart' => [$this->cartLine($product, 2)]])
            ->get('/thanh-toan')->assertOk();

        $event = Event::where('event_type', Event::CHECKOUT_START)->firstOrFail();
        $this->assertSame(['lines' => 1, 'value' => 2_000_000], $event->meta);
    }

    public function test_dat_don_cod_ghi_dat_hang_va_mua_theo_tung_bien_the(): void
    {
        $product = $this->makeProduct(slug: 'giay-cod');
        $this->makeAddress($this->customer);

        $this->actingAs($this->customer)->asVisitor()
            ->withSession(['cart' => [$this->cartLine($product, 2)]])
            ->post('/thanh-toan', ['payment' => 'cod'])
            ->assertRedirect(route('success.checkout'));

        $order = OrderModel::firstOrFail();
        $purchase = Event::where('event_type', Event::PURCHASE)->firstOrFail();
        $this->assertSame(1, Event::where('event_type', Event::PLACE_ORDER)->where('order_id', $order->order_id)->count());
        $this->assertSame([$order->order_id, $product->pro_id, 1, 1], [
            (int) $purchase->order_id, (int) $purchase->pro_id, (int) $purchase->size_id, (int) $purchase->color_id,
        ]);
        $this->assertSame(2, $purchase->meta['quantity']);
        $this->assertSame($this->customer->user_id, (int) $purchase->user_id);
    }

    public function test_tra_qua_cong_chi_tinh_la_mua_khi_tien_ve_va_khong_ghi_hai_lan(): void
    {
        $product = $this->makeProduct(slug: 'giay-vnpay');
        $this->makeAddress($this->customer);

        $location = $this->actingAs($this->customer)->asVisitor()
            ->withSession(['cart' => [$this->cartLine($product)]])
            ->post('/thanh-toan', ['payment' => 'redirect'])
            ->headers->get('Location');

        $this->assertSame(0, Event::where('event_type', Event::PURCHASE)->count(), 'Not a sale until the money arrives');

        parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);
        $reply = $this->vnpayReply((string) $query['vnp_TxnRef'], (int) OrderModel::firstOrFail()->order_total);

        // The gateway's own call comes with no cookie at all.
        $this->post(route('payment.ipn'), $reply);
        $this->asVisitor()->get(route('process.checkout', $reply));

        $purchases = Event::where('event_type', Event::PURCHASE)->get();
        $this->assertCount(1, $purchases);
        $this->assertSame(self::VISITOR, $purchases->first()->session_id);
    }

    public function test_danh_gia_ghi_so_sao(): void
    {
        $product = $this->makeProduct(slug: 'giay-danh-gia');
        $this->makeAddress($this->customer);
        $this->actingAs($this->customer)->withSession(['cart' => [$this->cartLine($product)]])->post('/thanh-toan', ['payment' => 'cod']);
        OrderModel::firstOrFail()->forceFill(['order_status' => OrderStatus::Completed])->save();

        $this->actingAs($this->customer)->asVisitor()->post(route('comments.store', $product->pro_id), [
            'comment_content' => 'Giày đi rất êm, đúng mô tả.',
            'rating' => 5,
        ])->assertSessionHasNoErrors();

        $review = Event::where('event_type', Event::REVIEW)->firstOrFail();
        $this->assertSame([$product->pro_id, 5], [(int) $review->pro_id, $review->meta['rating']]);
    }

    // ------------------------------------------------------------- beacon

    public function test_beacon_ghi_lua_chon_bien_the(): void
    {
        $product = $this->makeProduct(slug: 'giay-beacon');

        $this->asVisitor()->post(route('events.beacon'), [
            'type' => Event::SELECT_VARIANT, 'pro_id' => $product->pro_id, 'size_id' => 1, 'color_id' => 2,
        ])->assertNoContent();

        $event = Event::firstOrFail();
        $this->assertSame([Event::SELECT_VARIANT, 1, 2], [$event->event_type, (int) $event->size_id, (int) $event->color_id]);
    }

    public function test_beacon_khong_nhan_su_kien_phai_ghi_o_server(): void
    {
        $product = $this->makeProduct(slug: 'giay-beacon');

        $this->asVisitor()->postJson(route('events.beacon'), [
            'type' => Event::PURCHASE, 'pro_id' => $product->pro_id,
        ])->assertUnprocessable();

        $this->assertSame(0, Event::count());
    }

    // ------------------------------------------------------------- seeder

    public function test_du_lieu_mo_phong_duoc_danh_dau_va_khong_tinh_vao_luot_khach(): void
    {
        $product = $this->makeProduct(slug: 'giay-mo-phong');
        $this->addVariant($product, colorId: 2);

        $this->seed(UserEventSeeder::class);
        $simulated = Event::count();

        $this->assertGreaterThan(0, $simulated);
        $this->assertSame($simulated, Event::where('is_simulated', true)->count());
        $this->assertSame(0, Event::real()->count());

        $this->seed(UserEventSeeder::class);
        $this->assertSame($simulated, Event::count(), 'A second run replaces the simulation rather than doubling it');
    }

    /**
     * A VNPay reply for one attempt, signed the way VNPay signs one.
     *
     * @return array<string, string>
     */
    private function vnpayReply(string $code, int $amount): array
    {
        $params = [
            'vnp_Amount' => (string) ($amount * 100),
            'vnp_ResponseCode' => '00',
            'vnp_TmnCode' => 'TESTCODE',
            'vnp_TransactionNo' => '14022189',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => $code,
        ];
        ksort($params);

        $params['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($params, '', '&', PHP_QUERY_RFC1738), self::VNPAY_SECRET);

        return $params;
    }
}
