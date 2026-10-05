<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Enums\OrderStatus;
use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use App\Models\OrderReturnItemModel;
use App\Models\OrderReturnModel;
use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use App\Services\Shipping\FakeCarrier;
use App\Services\Shipping\ShippingCarrier;
use App\Services\ShopSettings;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * A customer sending back an order they already received:
 * request → approve or reject → parcel comes back → received → refunded.
 */
class OrderReturnTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const SECRET = 'bi-mat-webhook';

    private UserModel $customer;

    private ProductModel $product;

    private FakeCarrier $carrier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->carrier = new FakeCarrier;
        $this->app->instance(ShippingCarrier::class, $this->carrier);
        config(['services.ghn.webhook_token' => self::SECRET, 'services.ghn.create_orders' => true]);
        Storage::fake('public');

        $this->customer = $this->makeUser();
    }

    /**
     * A delivered order the customer confirmed, with its revenue booked.
     */
    private function makeCompletedOrder(int $quantity = 2): OrderModel
    {
        $this->product = $this->makeProduct(slug: 'giay-tra-hang', stock: 10);
        $address = $this->makeAddress($this->customer);

        $order = app(PlaceOrderAction::class)->execute(
            $this->customer,
            [$this->cartLine($this->product, $quantity)],
            null,
            $address,
            ['payment' => 'COD', 'note_customer' => null],
        );

        $order->order_status = OrderStatus::Delivered;
        $order->order_shipping_code = 'LDI01';
        $order->order_shipping_status = 'delivered';
        $order->order_delivery_status = 1;
        $order->save();

        $this->actingAs($this->customer)->post(route('success.order'), ['order_code' => $order->order_code]);

        return $order->fresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function requestReturn(OrderModel $order, array $overrides = [], ?UserModel $as = null)
    {
        return $this->actingAs($as ?? $this->customer)
            ->from(route('orderBill.checkout', $order->order_code))
            ->patch(route('return.order', $order->order_code), array_merge([
                'items' => OrderDetailModel::where('order_id', $order->order_id)
                    ->pluck('quantity', 'order_details_id')
                    ->toArray(),
                'reason' => 'sai_size',
                'description' => 'Giày bị chật',
                'images' => [UploadedFile::fake()->image('giay.jpg', 400, 400)],
            ], $overrides));
    }

    private function makeOrderAdmin(): UserModel
    {
        $admin = $this->makeUser(email: 'donhang@example.test', username: 'quanlydon', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web'));

        return $admin;
    }

    private function admin(string $route, OrderModel $order, array $data = [], string $method = 'post')
    {
        return $this->actingAs($this->makeOrderAdminOnce())->{$method}(route($route, $order->order_id), $data);
    }

    private ?UserModel $admin = null;

    private function makeOrderAdminOnce(): UserModel
    {
        return $this->admin ??= $this->makeOrderAdmin();
    }

    private function stock(): int
    {
        return (int) ProductQuantityModel::where('pro_id', $this->product->pro_id)->value('quantity');
    }

    private function sales(): int
    {
        return (int) DB::table('statistical')->value('sales');
    }

    private function ghn(string $code, string $status, string $time, string $client = '')
    {
        return $this->postJson(route('webhook.ghn', ['token' => self::SECRET]), [
            'OrderCode' => $code,
            'ClientOrderCode' => $client,
            'Status' => $status,
            'Time' => $time,
        ])->assertOk();
    }

    // ------------------------------------------------------ khách gửi yêu cầu

    public function test_khach_gui_yeu_cau_tra_hang_kem_anh(): void
    {
        $order = $this->makeCompletedOrder();

        $this->requestReturn($order)->assertSessionHas('iconMessage', 'success');

        $return = OrderReturnModel::firstOrFail();
        $this->assertSame(OrderReturnModel::REQUESTED, $return->status);
        $this->assertSame('sai_size', $return->reason);
        $this->assertCount(1, $return->images);
        Storage::disk('public')->assertExists($return->images[0]);
        $this->assertSame(OrderStatus::Completed, $order->fresh()->order_status, 'Khách mới hỏi, hàng chưa đi đâu cả');
    }

    public function test_don_chua_hoan_thanh_khong_gui_duoc_yeu_cau(): void
    {
        $order = $this->makeCompletedOrder();
        $order->forceFill(['order_status' => OrderStatus::Confirmed])->save();

        $this->requestReturn($order)->assertSessionHas('iconMessage', 'error');

        $this->assertSame(0, OrderReturnModel::count());
    }

    public function test_qua_han_tra_hang_thi_khong_gui_duoc(): void
    {
        app(ShopSettings::class)->setReturnDays(3);
        $order = $this->makeCompletedOrder();

        Carbon::setTestNow(now()->addDays(4));
        $this->requestReturn($order)->assertSessionHas('iconMessage', 'error');
        Carbon::setTestNow();

        $this->assertSame(0, OrderReturnModel::count());
    }

    public function test_dang_co_yeu_cau_cho_xu_ly_thi_khong_gui_them(): void
    {
        $order = $this->makeCompletedOrder();

        $this->requestReturn($order);
        $this->requestReturn($order)->assertSessionHas('iconMessage', 'error');

        $this->assertSame(1, OrderReturnModel::count());
    }

    public function test_khong_gui_duoc_yeu_cau_cho_don_nguoi_khac(): void
    {
        $order = $this->makeCompletedOrder();
        $stranger = $this->makeUser(email: 'nguoila@example.test', username: 'nguoila');

        $this->requestReturn($order, [], $stranger)->assertNotFound();

        $this->assertSame(0, OrderReturnModel::count());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function yeuCauKhongHopLe(): array
    {
        return [
            'thiếu lý do' => [['reason' => ''], 'reason'],
            'lý do lạ' => [['reason' => 'hack'], 'reason'],
            'lý do khác mà không mô tả' => [['reason' => 'khac', 'description' => ''], 'description'],
        ];
    }

    #[DataProvider('yeuCauKhongHopLe')]
    public function test_yeu_cau_khong_hop_le_bi_tu_choi(array $data, string $field): void
    {
        $order = $this->makeCompletedOrder();

        $this->requestReturn($order, $data)->assertSessionHasErrors($field);

        $this->assertSame(0, OrderReturnModel::count());
    }

    public function test_qua_so_anh_cho_phep_bi_tu_choi(): void
    {
        $order = $this->makeCompletedOrder();
        $anh = array_map(fn ($i) => UploadedFile::fake()->image("anh{$i}.jpg"), range(1, 4));

        $this->requestReturn($order, ['images' => $anh])->assertSessionHasErrors('images');
    }

    public function test_tep_khong_phai_anh_bi_tu_choi(): void
    {
        $order = $this->makeCompletedOrder();

        $this->requestReturn($order, ['images' => [UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors('images.0');
    }

    public function test_nut_tra_hang_chi_hien_khi_con_han(): void
    {
        app(ShopSettings::class)->setReturnDays(3);
        $order = $this->makeCompletedOrder();
        $trang = route('orderBill.checkout', $order->order_code);

        $this->actingAs($this->customer)->get($trang)->assertSee('id="returnOrder"', false);

        Carbon::setTestNow(now()->addDays(4));
        $this->actingAs($this->customer)->get($trang)->assertDontSee('id="returnOrder"', false);
        Carbon::setTestNow();
    }

    public function test_da_gui_yeu_cau_thi_doi_sang_nut_xem_yeu_cau(): void
    {
        $order = $this->makeCompletedOrder();
        $trang = route('orderBill.checkout', $order->order_code);

        $this->actingAs($this->customer)->get($trang)->assertDontSee('Lịch sử trả hàng');

        $this->requestReturn($order);

        $this->actingAs($this->customer)->get($trang)
            ->assertOk()
            // The form for a second request is gone, and the one already sent
            // is now something the customer can open and read.
            ->assertDontSee('id="returnOrder"', false)
            ->assertSee('Lịch sử trả hàng (1)')
            ->assertSee('id="returnDetail"', false);
    }

    public function test_yeu_cau_vua_gui_hien_ngay_trang_thai_cho_duyet(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);

        // The panel used to wait for the shop to approve, so a customer who had
        // just sent a request saw no sign of it anywhere on the page.
        $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->assertSee('Chờ duyệt');
    }

    // ------------------------------------------- nhiều lượt trả trên một đơn

    public function test_tra_xong_mot_lan_thi_het_quyen_tra(): void
    {
        $order = $this->makeCompletedOrder(quantity: 3);
        $dong = OrderDetailModel::where('order_id', $order->order_id)->firstOrFail();

        $this->requestReturn($order, ['items' => [$dong->order_details_id => 1]]);
        $this->runThroughRefund($order);

        $order = $order->fresh();
        $this->assertSame(OrderStatus::PartiallyReturned, $order->order_status);

        // Hai đôi còn lại vẫn ở nhà khách, nhưng chuyến xe của đơn này đã đi.
        $this->assertFalse($order->canRequestReturn());
        $this->requestReturn($order, ['items' => [$dong->order_details_id => 2]])
            ->assertSessionHas('iconMessage', 'error');

        $this->assertSame(1, OrderReturnModel::count());
    }

    public function test_khong_tra_duoc_qua_so_luong_con_lai(): void
    {
        $order = $this->makeCompletedOrder(quantity: 2);
        $dong = OrderDetailModel::where('order_id', $order->order_id)->firstOrFail();

        $this->requestReturn($order, ['items' => [$dong->order_details_id => 1]]);
        $this->runThroughRefund($order);

        $this->requestReturn($order->fresh(), ['items' => [$dong->order_details_id => 2]])
            ->assertSessionHas('iconMessage', 'error');

        $this->assertSame(1, OrderReturnItemModel::query()->count());
    }

    public function test_khach_huy_yeu_cau_thi_gui_lai_duoc(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);

        $this->actingAs($this->customer)
            ->from(route('orderBill.checkout', $order->order_code))
            ->patch(route('return.cancel', $order->order_code))
            ->assertSessionHas('iconMessage', 'success');

        $this->assertSame(OrderReturnModel::CANCELLED, $order->fresh()->orderReturn->status);
        $this->assertTrue($order->fresh()->canRequestReturn());

        $this->requestReturn($order->fresh())->assertSessionHas('iconMessage', 'success');
        $this->assertSame(2, OrderReturnModel::count());
    }

    public function test_da_duyet_thi_khach_khong_huy_duoc_nua(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);
        $this->admin('returns.approve', $order);

        $this->actingAs($this->customer)
            ->from(route('orderBill.checkout', $order->order_code))
            ->patch(route('return.cancel', $order->order_code))
            ->assertSessionHas('iconMessage', 'error');

        $this->assertSame(OrderReturnModel::APPROVED, $order->fresh()->orderReturn->status);
    }

    public function test_khach_khong_huy_duoc_yeu_cau_cua_don_nguoi_khac(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);

        $nguoiLa = $this->makeUser(email: 'nguoila@example.test', username: 'nguoila');

        $this->actingAs($nguoiLa)
            ->patch(route('return.cancel', $order->order_code))
            ->assertNotFound();

        $this->assertSame(OrderReturnModel::REQUESTED, $order->fresh()->orderReturn->status);
    }

    /**
     * Walks the shop's half of a return the customer has just asked for.
     */
    private function runThroughRefund(OrderModel $order, int $amount = 100_000): void
    {
        $this->admin('returns.approve', $order);
        $this->admin('returns.receive', $order);
        $this->admin('returns.refund', $order, ['refund_amount' => $amount]);
    }

    // ------------------------------------------------------ admin xử lý

    public function test_tu_choi_thi_don_ve_thanh_cong_va_giu_doanh_thu(): void
    {
        $order = $this->makeCompletedOrder();
        $doanhThu = $this->sales();
        $this->requestReturn($order);

        $this->admin('returns.reject', $order, ['reject_reason' => 'Sản phẩm đã qua sử dụng']);

        $return = OrderReturnModel::firstOrFail();
        $this->assertSame(OrderReturnModel::REJECTED, $return->status);
        $this->assertSame('Sản phẩm đã qua sử dụng', $return->reject_reason);
        $this->assertSame(OrderStatus::Completed, $order->fresh()->order_status);
        $this->assertSame($doanhThu, $this->sales());
    }

    public function test_tu_choi_phai_co_ly_do(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);

        $this->admin('returns.reject', $order, ['reject_reason' => ''])->assertSessionHasErrors('reject_reason');

        $this->assertSame(OrderReturnModel::REQUESTED, OrderReturnModel::firstOrFail()->status);
    }

    public function test_bi_tu_choi_thi_van_gui_lai_duoc_trong_han(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);
        $this->admin('returns.reject', $order, ['reject_reason' => 'Ảnh chưa rõ, gửi lại giúp shop']);

        // A refused request holds nothing: the goods never left the customer.
        $this->requestReturn($order->fresh())->assertSessionHas('iconMessage', 'success');

        $this->assertSame(2, OrderReturnModel::count());
        $this->assertSame(OrderReturnModel::REQUESTED, $order->fresh()->orderReturn->status);
    }

    public function test_lich_su_liet_ke_tung_lan_gui(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);
        $this->admin('returns.reject', $order, ['reject_reason' => 'Ảnh chưa rõ']);
        Carbon::setTestNow(now()->addHours(3));
        $this->requestReturn($order->fresh());
        Carbon::setTestNow();

        $moi = OrderReturnModel::orderByDesc('return_id')->firstOrFail();
        $cu = OrderReturnModel::orderBy('return_id')->firstOrFail();

        $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->assertSee('Lịch sử trả hàng (2)')
            ->assertSee('2 yêu cầu đã gửi')
            // Each line is told apart by when it was sent, nothing else.
            ->assertSee($moi->created_at->format('H:i d/m/Y'))
            ->assertSee($cu->created_at->format('H:i d/m/Y'))
            // The list is what opens; a docket is a pane the same modal swaps to.
            ->assertSee('data-return-open', false)
            ->assertSee('data-return-pane="detail"', false);
    }

    public function test_admin_chi_mo_san_yeu_cau_dang_cho_xu_ly(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);
        $this->admin('returns.reject', $order, ['reject_reason' => 'Ảnh chưa rõ']);
        $cu = OrderReturnModel::orderBy('return_id')->firstOrFail();
        $this->requestReturn($order->fresh());
        $moi = OrderReturnModel::orderByDesc('return_id')->firstOrFail();

        $trang = $this->actingAs($this->makeOrderAdminOnce())
            ->get(route('orders.edit', encrypt($order->order_id)))
            ->assertOk()
            ->assertSee('2 lần');

        $html = $trang->getContent();
        $mo = fn (int $id) => '/id="tra-hang-'.$id.'"\s+class="accordion-collapse collapse show"/';

        $this->assertMatchesRegularExpression($mo($moi->return_id), $html);
        $this->assertDoesNotMatchRegularExpression($mo($cu->return_id), $html);
    }

    public function test_thao_tac_admin_nham_vao_yeu_cau_dang_cho_xu_ly(): void
    {
        $order = $this->makeCompletedOrder(quantity: 2);
        $this->requestReturn($order);
        $this->admin('returns.reject', $order, ['reject_reason' => 'Ảnh chưa rõ']);

        $this->requestReturn($order->fresh());
        $this->admin('returns.approve', $order);

        // The settled request is the first row in the table; every button here
        // must reach past it to the one the shop is actually working on.
        $this->admin('returns.shipping_code', $order, ['return_shipping_code' => 'TRAHANG001'], 'patch');

        $moi = OrderReturnModel::orderByDesc('return_id')->firstOrFail();
        $this->assertSame('TRAHANG001', $moi->fresh()->return_shipping_code);

        $this->admin('returns.receive', $order);
        $this->admin('returns.refund', $order, ['refund_amount' => $moi->fresh()->refundDue()])
            ->assertSessionHas('iconMessage', 'success');

        $this->assertSame(OrderReturnModel::REFUNDED, $moi->fresh()->status);
    }

    public function test_tron_luong_duyet_nhan_hang_hoan_tien(): void
    {
        $order = $this->makeCompletedOrder(quantity: 2);
        $kho = $this->stock();
        $this->assertGreaterThan(0, $this->sales());
        $this->requestReturn($order);

        $this->admin('returns.approve', $order);
        $this->assertSame(OrderReturnModel::APPROVED, OrderReturnModel::firstOrFail()->status);

        $this->admin('returns.receive', $order);
        $this->assertSame($kho + 2, $this->stock(), 'Nhận hàng trả thì cộng lại kho');
        $this->assertGreaterThan(0, $this->sales(), 'Chưa hoàn tiền thì chưa trừ doanh thu');

        $this->admin('returns.refund', $order, ['refund_amount' => 500_000]);

        $return = OrderReturnModel::firstOrFail();
        $this->assertSame(OrderReturnModel::REFUNDED, $return->status);
        $this->assertSame(500_000, (int) $return->refund_amount);
        $this->assertSame(0, $this->sales());
        $this->assertSame(OrderStatus::Returned, $order->fresh()->order_status);
    }

    public function test_khong_nhay_coc_buoc(): void
    {
        $order = $this->makeCompletedOrder();
        $kho = $this->stock();
        $this->requestReturn($order);

        $this->admin('returns.receive', $order)->assertSessionHas('iconMessage', 'error');
        $this->admin('returns.refund', $order, ['refund_amount' => 1])->assertSessionHas('iconMessage', 'error');

        $this->assertSame($kho, $this->stock());
        $this->assertSame(OrderReturnModel::REQUESTED, OrderReturnModel::firstOrFail()->status);
    }

    public function test_bam_nhan_hang_hai_lan_chi_cong_kho_mot_lan(): void
    {
        $order = $this->makeCompletedOrder(quantity: 2);
        $kho = $this->stock();
        $this->requestReturn($order);
        $this->admin('returns.approve', $order);

        $this->admin('returns.receive', $order);
        $this->admin('returns.receive', $order);

        $this->assertSame($kho + 2, $this->stock());
    }

    public function test_so_tien_hoan_khong_vuot_tong_don(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);
        $this->admin('returns.approve', $order);
        $this->admin('returns.receive', $order);

        $this->admin('returns.refund', $order, ['refund_amount' => (int) $order->order_total + 1])
            ->assertSessionHasErrors('refund_amount');

        $this->assertSame(OrderReturnModel::RECEIVED, OrderReturnModel::firstOrFail()->status);
    }

    public function test_khong_co_quyen_don_hang_thi_khong_duyet_duoc(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);
        $staff = $this->makeUser(email: 'nhanvien@example.test', username: 'nhanvien', role: 1);

        $this->actingAs($staff)->post(route('returns.approve', $order->order_id))->assertForbidden();
    }

    // ------------------------------------------------ vận đơn chiều về

    public function test_tao_van_don_tra_hang_lay_tai_nha_khach_giao_ve_shop(): void
    {
        config(['services.ghn.from_district_id' => 3695, 'services.ghn.from_ward_code' => '90742']);
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);
        $this->admin('returns.approve', $order);

        $this->admin('returns.book', $order)->assertSessionHas('iconMessage', 'success');

        $booked = end($this->carrier->booked);
        $yeuCau = OrderReturnModel::firstOrFail();
        // GHN keeps client_order_code unique per shop, so the request's own id
        // is what lets one order send a second parcel home.
        $this->assertSame($order->order_code.'-TH'.$yeuCau->return_id, $booked->reference);
        $this->assertSame((int) $order->order_district_id, $booked->from->districtId);
        $this->assertSame(3695, $booked->toDistrictId);
        $this->assertSame(0, $booked->codAmount);
        $this->assertNotNull(OrderReturnModel::firstOrFail()->return_shipping_code);
    }

    public function test_van_don_tra_hang_chi_khai_phan_khach_gui_tra(): void
    {
        $order = $this->makeCompletedOrder(quantity: 2);
        $this->product->update(['pro_weight' => 600]);
        $dong = OrderDetailModel::where('order_id', $order->order_id)->firstOrFail();

        $this->requestReturn($order, ['items' => [$dong->order_details_id => 1]]);
        $this->admin('returns.approve', $order);
        $this->admin('returns.book', $order)->assertSessionHas('iconMessage', 'success');

        $booked = end($this->carrier->booked);

        // One of the two pairs is coming back, so GHN must be told about one
        // pair: it prices the carriage by weight and the cover by value.
        $this->assertSame(600, $booked->weight);
        $this->assertSame(1_000_000, $booked->insuranceValue);
        $this->assertCount(1, $booked->items);
        $this->assertSame(1, $booked->items[0]['quantity']);
    }

    public function test_van_don_tra_hang_khong_de_shipper_thu_cuoc_cua_khach(): void
    {
        $order = $this->makeCompletedOrder();
        $this->bookReturnFor($order, 'sai_size');

        // Khách đã bị trừ cước vào tiền hoàn rồi, thu thêm ở cửa là thu hai lần.
        $this->assertFalse(end($this->carrier->booked)->senderPaysCarriage);
    }

    // ------------------------------------------------- phí gửi trả theo lỗi

    /**
     * @return array{0: OrderReturnModel, 1: int} the request and the booked fee
     */
    private function bookReturnFor(OrderModel $order, string $reason): array
    {
        $this->requestReturn($order, ['reason' => $reason]);
        $this->admin('returns.approve', $order);
        $this->admin('returns.book', $order);

        $return = OrderReturnModel::orderByDesc('return_id')->firstOrFail();

        return [$return, (int) $return->return_shipping_fee];
    }

    public function test_loi_cua_hang_thi_cua_hang_chiu_phi_gui_tra(): void
    {
        $order = $this->makeCompletedOrder();
        [$return, $phi] = $this->bookReturnFor($order, 'loi_san_pham');

        $this->assertGreaterThan(0, $phi, 'Vận đơn trả phải ghi lại phí');
        $this->assertTrue($return->shopAtFault());
        $this->assertSame(0, $return->buyerBorneShipping());

        $khongTruPhi = $return->refundDue();
        $return->reason = 'sai_size';
        $this->assertSame($khongTruPhi - $phi, $return->refundDue(), 'Cùng đơn đó, lỗi khách thì phải trừ phí');
    }

    public function test_loi_nguoi_mua_thi_tru_phi_gui_tra_vao_tien_hoan(): void
    {
        $order = $this->makeCompletedOrder();
        [$return, $phi] = $this->bookReturnFor($order, 'sai_size');

        $this->assertGreaterThan(0, $phi);
        $this->assertSame($phi, $return->buyerBorneShipping());

        $this->admin('returns.receive', $order);
        $this->admin('returns.refund', $order, ['refund_amount' => $return->fresh()->refundDue()]);

        // Trả cả đơn nên được hoàn tiền hàng cộng phí giao đi, trừ phí gửi trả.
        $tienHang = (int) OrderDetailModel::where('order_id', $order->order_id)
            ->selectRaw('SUM(price * quantity) as t')->value('t');
        $mongDoi = $tienHang + (int) $order->order_delivery_fee - $phi;

        $this->assertSame($mongDoi, (int) OrderReturnModel::firstOrFail()->refund_amount);
        $this->assertSame($mongDoi, app(WalletService::class)->for($this->customer)->balance);
    }

    public function test_chua_co_van_don_thi_khong_tru_phi_nao(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order, ['reason' => 'sai_size']);

        // Nhận hàng tận nơi, không qua vận đơn: không ai mất tiền cước.
        $return = OrderReturnModel::firstOrFail();
        $this->assertNull($return->return_shipping_fee);
        $this->assertSame(0, $return->buyerBorneShipping());
    }

    public function test_huy_van_don_tra_thi_bo_luon_phi(): void
    {
        $order = $this->makeCompletedOrder();
        $this->bookReturnFor($order, 'sai_size');

        $this->admin('returns.cancel_shipment', $order);

        $this->assertNull(OrderReturnModel::firstOrFail()->return_shipping_fee);
    }

    public function test_gan_ma_thu_cong_luu_duoc_phi(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order, ['reason' => 'sai_size']);
        $this->admin('returns.approve', $order);

        $this->admin('returns.shipping_code', $order, [
            'return_shipping_code' => 'LTRA77',
            'return_shipping_fee' => 25000,
        ], 'patch');

        $this->assertSame(25000, (int) OrderReturnModel::firstOrFail()->return_shipping_fee);
    }

    public function test_ma_van_don_tra_mang_so_hieu_yeu_cau(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);
        $this->admin('returns.reject', $order, ['reject_reason' => 'Ảnh chưa rõ']);
        $this->requestReturn($order->fresh());
        $this->admin('returns.approve', $order);
        $this->admin('returns.book', $order);

        $duocDuyet = OrderReturnModel::orderByDesc('return_id')->firstOrFail();
        $biTuChoi = OrderReturnModel::orderBy('return_id')->firstOrFail();

        // Mã mang số hiệu yêu cầu nên callback tìm đúng yêu cầu đã đặt vận đơn,
        // không rơi vào yêu cầu bị từ chối nằm trước nó trong bảng.
        $this->assertSame($order->order_code.'-TH'.$duocDuyet->return_id, $duocDuyet->reference());
        $this->assertNotSame($biTuChoi->reference(), $duocDuyet->reference());
        $this->assertSame(
            $duocDuyet->return_id,
            OrderReturnModel::forReference($duocDuyet->reference())?->return_id,
        );
    }

    public function test_ma_van_don_kieu_cu_tim_ve_yeu_cau_dang_chay(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);
        $this->admin('returns.reject', $order, ['reject_reason' => 'Ảnh chưa rõ']);
        $this->requestReturn($order->fresh());

        $dangChay = OrderReturnModel::orderByDesc('return_id')->firstOrFail();

        $this->assertSame(
            $dangChay->return_id,
            OrderReturnModel::forReference($order->order_code.'-TH')?->return_id,
        );
    }

    public function test_chua_duyet_thi_khong_tao_duoc_van_don_tra_hang(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);

        $this->admin('returns.book', $order)->assertSessionHas('iconMessage', 'error');

        $this->assertNull(OrderReturnModel::firstOrFail()->return_shipping_code);
    }

    public function test_webhook_van_don_tra_hang_khong_dung_vao_van_don_giao_di(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);
        $this->admin('returns.approve', $order);
        $this->admin('returns.shipping_code', $order, ['return_shipping_code' => 'LTRA01'], 'patch');

        $this->ghn('LTRA01', 'picked', '2026-09-24T03:00:00.000Z');
        $this->ghn('LTRA01', 'delivered', '2026-09-25T03:00:00.000Z');

        $fresh = $order->fresh();
        $this->assertSame('delivered', OrderReturnModel::firstOrFail()->return_shipping_status);
        $this->assertSame('LDI01', $fresh->order_shipping_code);
        $this->assertSame(OrderStatus::Returning, $fresh->order_status, 'Kiện trả về tới shop chưa phải là shop đã kiểm hàng');
        $this->assertTrue($fresh->previousShipments()->isEmpty(), 'Vận đơn trả hàng không lẫn vào lịch sử vận đơn giao đi');
    }

    public function test_van_don_tra_tao_tren_trang_ghn_duoc_gan_qua_ma_don(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);
        $this->admin('returns.approve', $order);

        $this->ghn('LTRA02', 'ready_to_pick', '2026-09-24T03:00:00.000Z', $order->order_code.'-TH');

        $this->assertSame('LTRA02', OrderReturnModel::firstOrFail()->return_shipping_code);
    }

    public function test_admin_huy_van_don_tra_hang_roi_tao_lai_duoc(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);
        $this->admin('returns.approve', $order);
        $this->admin('returns.book', $order);
        $maCu = OrderReturnModel::firstOrFail()->return_shipping_code;

        $this->admin('returns.cancel_shipment', $order)->assertSessionHas('iconMessage', 'success');

        $this->assertContains($maCu, $this->carrier->cancelled);
        $this->assertNull(OrderReturnModel::firstOrFail()->return_shipping_code);

        $this->admin('returns.book', $order)->assertSessionHas('iconMessage', 'success');
        $this->assertNotNull(OrderReturnModel::firstOrFail()->return_shipping_code);
    }

    public function test_go_ma_van_don_tra_hang_khong_goi_ghn(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);
        $this->admin('returns.approve', $order);
        $this->admin('returns.shipping_code', $order, ['return_shipping_code' => 'LTRA09'], 'patch');

        // The parcel is already gone at GHN; asking again would only fail.
        $this->admin('returns.detach_shipment', $order, [], 'delete')
            ->assertSessionHas('iconMessage', 'success');

        $this->assertSame([], $this->carrier->cancelled);
        $this->assertNull(OrderReturnModel::firstOrFail()->return_shipping_code);
    }

    public function test_huy_van_don_tra_hang_nha_ma(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);
        $this->admin('returns.approve', $order);
        $this->admin('returns.shipping_code', $order, ['return_shipping_code' => 'LTRA03'], 'patch');

        $this->ghn('LTRA03', 'cancel', '2026-09-24T03:00:00.000Z');

        $this->assertNull(OrderReturnModel::firstOrFail()->return_shipping_code);
    }

    // ------------------------------------------------------ hiển thị

    public function test_admin_thay_yeu_cau_va_nut_duyet(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);

        $this->actingAs($this->makeOrderAdminOnce())
            ->get(route('orders.edit', encrypt($order->order_id)))
            ->assertOk()
            ->assertSee('Yêu cầu trả hàng')
            ->assertSee('Sai size, không vừa')
            ->assertSee(route('returns.approve', $order->order_id))
            ->assertDontSee('Xác nhận hoàn tiền');
    }

    public function test_khach_thay_trang_thai_yeu_cau(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);
        $this->admin('returns.approve', $order);

        $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->assertSee('Yêu cầu trả hàng: Đã duyệt, chờ nhận hàng trả');
    }

    public function test_khach_thay_ly_do_bi_tu_choi(): void
    {
        $order = $this->makeCompletedOrder();
        $this->requestReturn($order);
        $this->admin('returns.reject', $order, ['reject_reason' => 'Sản phẩm đã qua sử dụng']);

        $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->assertSee('Lý do từ chối: Sản phẩm đã qua sử dụng');
    }

    public function test_tra_xong_mot_lan_thi_mat_nut_gui_yeu_cau(): void
    {
        $order = $this->makeCompletedOrder(quantity: 2);
        $dong = OrderDetailModel::where('order_id', $order->order_id)->firstOrFail();

        $moNut = '/data-bs-toggle="modal"\s+data-bs-target="#returnOrder"/';
        $trang = route('orderBill.checkout', $order->order_code);

        $this->assertMatchesRegularExpression(
            $moNut,
            $this->actingAs($this->customer)->get($trang)->getContent(),
        );

        $this->requestReturn($order, ['items' => [$dong->order_details_id => 1]]);
        $this->admin('returns.approve', $order);
        $this->admin('returns.receive', $order);
        $this->admin('returns.refund', $order, [
            'refund_amount' => OrderReturnModel::firstOrFail()->refundDue(),
        ]);

        // Đôi còn lại vẫn ở nhà khách nhưng đơn đã dùng hết lượt trả của mình.
        $this->assertDoesNotMatchRegularExpression(
            $moNut,
            $this->actingAs($this->customer)->get($trang)->getContent(),
        );
    }

    public function test_o_gui_yeu_cau_dan_sang_trang_chinh_sach(): void
    {
        $order = $this->makeCompletedOrder();

        $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->assertSee('Bạn có thể gửi yêu cầu đến hết', false)
            ->assertSee(route('policy.return'), false)
            // Chọn lý do nào thì ai chịu phí, nói ngay tại chỗ chọn.
            ->assertSee('data-fault="khach"', false)
            ->assertSee('cửa hàng chịu phí gửi trả', false);
    }

    public function test_trang_chinh_sach_tra_hang_doc_cau_hinh_that(): void
    {
        $this->actingAs($this->makeOrderAdminOnce())
            ->put(route('setting.update'), ['auto_complete_days' => 5, 'return_days' => 10, 'payment_window_minutes' => 30]);

        $this->get(route('policy.return'))
            ->assertOk()
            ->assertSee('10 ngày', false)
            ->assertSee('5 ngày', false)
            // Bảng ai chịu phí dựng từ chính danh sách lý do trong mã nguồn.
            ->assertSee('Sản phẩm lỗi, hư hỏng')
            ->assertSee('Sai size, không vừa');
    }

    public function test_admin_cau_hinh_so_ngay_tra_hang(): void
    {
        $this->actingAs($this->makeOrderAdminOnce())
            ->put(route('setting.update'), ['auto_complete_days' => 7, 'return_days' => 15, 'payment_window_minutes' => 30]);

        $this->assertSame(15, app(ShopSettings::class)->returnDays());
    }
}
