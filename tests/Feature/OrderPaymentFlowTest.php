<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Enums\OrderStatus;
use App\Mail\ConfirmOrder;
use App\Models\OrderModel;
use App\Models\PaymentAttemptModel;
use App\Models\UserModel;
use App\Models\WalletTransactionModel;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Paying for an order after it was placed: trying again, paying another way,
 * and running out of time.
 *
 * VNPay and MoMo each refuse a transaction code they have already seen, so
 * every trip to a gateway carries its own attempt code. Most of what can go
 * wrong here is money arriving for an attempt the order no longer needs, and
 * every such case must end with the money in the customer's wallet.
 */
class OrderPaymentFlowTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const VNPAY_SECRET = 'bi-mat-vnpay-cho-test';

    private const MOMO_SECRET = 'bi-mat-momo-cho-test';

    private const MOMO_ACCESS_KEY = 'khoa-truy-cap-momo';

    private const MOMO_ENDPOINT = 'https://momo.test/v2/gateway/api/create';

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedLookupTables();

        config([
            'services.vnpay.hash_secret' => self::VNPAY_SECRET,
            'services.vnpay.tmn_code' => 'TESTCODE',
            'services.vnpay.url' => 'https://vnpay.test/pay',
            'services.momo.secret_key' => self::MOMO_SECRET,
            'services.momo.access_key' => self::MOMO_ACCESS_KEY,
            'services.momo.partner_code' => 'MOMOTEST',
            'services.momo.endpoint' => self::MOMO_ENDPOINT,
        ]);

        $this->customer = $this->makeUser();
    }

    // ---------------------------------------------------------- fixtures

    private function placeOrder(string $payment = 'redirect', ?UserModel $user = null): OrderModel
    {
        $user ??= $this->customer;

        return app(PlaceOrderAction::class)->execute(
            $user,
            [$this->cartLine($this->makeProduct(slug: 'giay-'.uniqid(), stock: 10))],
            null,
            $this->makeAddress($user),
            ['payment' => $payment, 'note_customer' => null],
        );
    }

    /**
     * Opens an attempt through the page's own button and returns the code
     * VNPay was sent.
     */
    private function payAgain(OrderModel $order): string
    {
        $location = $this->actingAs($this->customer)
            ->post(route('order.pay', $order->order_code))
            ->assertRedirect()
            ->headers->get('Location');

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        return (string) $query['vnp_TxnRef'];
    }

    /**
     * A VNPay reply for one attempt, signed the way VNPay signs one.
     *
     * @return array<string, string>
     */
    private function vnpayReply(string $code, int $amount, string $responseCode = '00', string $transactionNo = '14022189'): array
    {
        $params = [
            'vnp_Amount' => (string) ($amount * 100),
            'vnp_BankCode' => 'NCB',
            'vnp_OrderInfo' => 'Thanh toan',
            'vnp_ResponseCode' => $responseCode,
            'vnp_TmnCode' => 'TESTCODE',
            'vnp_TransactionNo' => $transactionNo,
            'vnp_TransactionStatus' => $responseCode,
            'vnp_TxnRef' => $code,
        ];

        ksort($params);

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = urlencode($key).'='.urlencode($value);
        }

        $params['vnp_SecureHash'] = hash_hmac('sha512', implode('&', $pairs), self::VNPAY_SECRET);

        return $params;
    }

    /**
     * Registered per test: the first matching fake wins, so one set up for
     * every test would shadow a test's own failing reply.
     *
     * @param  array<string, mixed>  $reply
     */
    private function fakeMomo(array $reply): void
    {
        Http::fake([self::MOMO_ENDPOINT => Http::response($reply)]);
    }

    private function changeTo(OrderModel $order, string $method, ?UserModel $as = null)
    {
        return $this->actingAs($as ?? $this->customer)
            ->patch(route('order.change_payment', $order->order_code), ['payment' => $method]);
    }

    private function walletBalance(): int
    {
        return app(WalletService::class)->for($this->customer)->balance;
    }

    private function topUp(int $amount): void
    {
        $wallets = app(WalletService::class);
        $wallets->credit($wallets->for($this->customer), $amount, WalletTransactionModel::TYPE_TOPUP, 'Nạp', WalletService::REF_TOPUP, 1);
    }

    // ------------------------------------------------------- paying again

    public function test_moi_lan_thanh_toan_lai_co_ma_giao_dich_rieng(): void
    {
        $order = $this->placeOrder();

        $lan1 = $this->payAgain($order);
        $lan2 = $this->payAgain($order);

        // VNPay từ chối mã đã thấy, nên dùng lại mã cũ là không thanh toán được.
        $this->assertNotSame($lan1, $lan2);
        $this->assertStringStartsWith($order->order_code.PaymentAttemptModel::SEPARATOR, $lan1);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $lan1, 'vnp_TxnRef chỉ được chứa chữ và số');
    }

    public function test_link_vnpay_het_han_dung_luc_don_bi_huy(): void
    {
        $order = $this->placeOrder();

        $location = $this->actingAs($this->customer)
            ->post(route('order.pay', $order->order_code))
            ->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame(
            $order->fresh()->order_payment_due_at->setTimezone('Asia/Ho_Chi_Minh')->format('YmdHis'),
            $query['vnp_ExpireDate'],
        );
    }

    public function test_tra_tien_bang_ma_lan_thu_moi_danh_dau_don_da_tra(): void
    {
        $order = $this->placeOrder();
        $ma = $this->payAgain($order);

        $this->get(route('process.checkout', $this->vnpayReply($ma, (int) $order->order_total)))
            ->assertRedirect(route('success.checkout'));

        $this->assertSame(1, (int) $order->fresh()->order_payment_status);
        $this->assertSame(PaymentAttemptModel::PAID, PaymentAttemptModel::where('code', $ma)->value('status'));
        Mail::assertQueued(ConfirmOrder::class);
    }

    public function test_huy_o_vnpay_roi_van_thanh_toan_lai_duoc(): void
    {
        $order = $this->placeOrder();
        $ma = $this->payAgain($order);

        $this->get(route('process.checkout', $this->vnpayReply($ma, (int) $order->order_total, '24')))
            ->assertRedirect(route('orderBill.checkout', $order->order_code));

        $this->assertSame(OrderStatus::New, $order->fresh()->order_status);
        $this->assertSame(PaymentAttemptModel::FAILED, PaymentAttemptModel::where('code', $ma)->value('status'));

        $this->assertNotSame($ma, $this->payAgain($order));
    }

    public function test_khong_thanh_toan_lai_duoc_khi_da_qua_han(): void
    {
        $order = $this->placeOrder();
        $this->travel(31)->minutes();

        $this->actingAs($this->customer)
            ->post(route('order.pay', $order->order_code))
            ->assertRedirect(route('orderBill.checkout', $order->order_code));

        $this->assertSame(0, PaymentAttemptModel::count());
    }

    public function test_cong_thanh_toan_khong_ket_noi_duoc_thi_van_giu_don(): void
    {
        $this->fakeMomo(['resultCode' => 99, 'message' => 'Lỗi hệ thống']);
        $order = $this->placeOrder('payUrl');

        $this->actingAs($this->customer)
            ->post(route('order.pay', $order->order_code))
            ->assertRedirect(route('orderBill.checkout', $order->order_code));

        $this->assertSame(OrderStatus::New, $order->fresh()->order_status, 'Cổng lỗi không phải lý do huỷ đơn của khách');
        $this->assertSame(PaymentAttemptModel::FAILED, PaymentAttemptModel::value('status'));
    }

    // ---------------------------------------------------- switching method

    public function test_doi_sang_cod_thi_bo_han_va_khong_bi_huy_khi_het_gio(): void
    {
        $order = $this->placeOrder();

        $this->changeTo($order, 'cod')->assertRedirect(route('orderBill.checkout', $order->order_code));

        $fresh = $order->fresh();
        $this->assertSame('cod', $fresh->order_payment);
        $this->assertNull($fresh->order_payment_due_at);
        Mail::assertQueued(ConfirmOrder::class);

        $this->travel(2)->hours();
        $this->artisan('orders:expire-unpaid');

        $this->assertSame(OrderStatus::New, $order->fresh()->order_status, 'Đơn COD không có hạn thanh toán');
    }

    public function test_doi_sang_spay_du_tien_thi_tru_vi_va_da_tra(): void
    {
        $order = $this->placeOrder();
        $this->topUp(10_000_000);

        $this->changeTo($order, 'wallet')->assertRedirect(route('orderBill.checkout', $order->order_code));

        $fresh = $order->fresh();
        $this->assertSame('wallet', $fresh->order_payment);
        $this->assertSame(1, (int) $fresh->order_payment_status);
        $this->assertSame(10_000_000 - (int) $order->order_total, $this->walletBalance());
    }

    public function test_doi_sang_spay_khong_du_tien_thi_khong_doi_gi(): void
    {
        $order = $this->placeOrder();
        $this->topUp(1_000);

        $this->changeTo($order, 'wallet')
            ->assertRedirect(route('orderBill.checkout', $order->order_code))
            ->assertSessionHas('message', 'Số dư SPay không đủ để thanh toán đơn này.');

        $fresh = $order->fresh();
        $this->assertSame('redirect', $fresh->order_payment);
        $this->assertSame(0, (int) $fresh->order_payment_status);
        $this->assertSame(1_000, $this->walletBalance());
    }

    public function test_doi_tu_vnpay_sang_momo_khong_gia_han_giu_hang(): void
    {
        $this->fakeMomo(['payUrl' => 'https://momo.test/pay/abc', 'resultCode' => 0]);
        $order = $this->placeOrder();
        $han = $order->fresh()->order_payment_due_at;
        $this->travel(10)->minutes();

        $this->changeTo($order, 'payUrl')->assertRedirect('https://momo.test/pay/abc');

        $fresh = $order->fresh();
        $this->assertSame('payUrl', $fresh->order_payment);
        // Đổi qua lại giữa các cổng mà được gia hạn thì hàng bị giữ mãi.
        $this->assertTrue($han->equalTo($fresh->order_payment_due_at));
    }

    public function test_doi_tu_cod_sang_vnpay_thi_bat_dau_han_moi(): void
    {
        $order = $this->placeOrder('cod');
        $this->travel(2)->hours();

        $this->changeTo($order, 'redirect')->assertRedirect();

        $fresh = $order->fresh();
        $this->assertSame('redirect', $fresh->order_payment);
        $this->assertTrue($fresh->order_payment_due_at->isFuture(), 'Vừa chuyển sang cổng thì phải có hạn mới');

        $this->artisan('orders:expire-unpaid');
        $this->assertSame(OrderStatus::New, $order->fresh()->order_status);
    }

    public function test_khong_doi_duoc_don_cua_nguoi_khac(): void
    {
        $order = $this->placeOrder();
        $nguoiKhac = $this->makeUser(email: 'khac@example.test', username: 'nguoikhac');

        $this->changeTo($order, 'cod', $nguoiKhac)->assertNotFound();
        $this->actingAs($nguoiKhac)->post(route('order.pay', $order->order_code))->assertNotFound();

        $this->assertSame('redirect', $order->fresh()->order_payment);
    }

    public function test_khong_doi_duoc_don_cua_hang_da_xac_nhan(): void
    {
        $order = $this->placeOrder('cod');
        $order->forceFill(['order_status' => OrderStatus::Confirmed])->save();

        $this->changeTo($order, 'redirect');

        $this->assertSame('cod', $order->fresh()->order_payment, 'Đơn đã giao cho GHN thu hộ thì không đổi được');
    }

    public function test_khong_chon_duoc_phuong_thuc_la(): void
    {
        $order = $this->placeOrder();

        $this->changeTo($order, 'tien-mat-tuoi')->assertSessionHasErrors('payment');
    }

    // ------------------------------------------- money that cannot be used

    public function test_tra_tien_hai_lan_thi_lan_sau_hoan_vao_vi(): void
    {
        $order = $this->placeOrder();
        $tongTien = (int) $order->order_total;
        $lan1 = $this->payAgain($order);
        $lan2 = $this->payAgain($order);

        $this->get(route('process.checkout', $this->vnpayReply($lan1, $tongTien, transactionNo: '111')));
        $this->get(route('process.checkout', $this->vnpayReply($lan2, $tongTien, transactionNo: '222')))
            ->assertRedirect(route('orderBill.checkout', $order->order_code));

        $this->assertSame(1, (int) $order->fresh()->order_payment_status);
        $this->assertSame($tongTien, $this->walletBalance(), 'Khoản trả thừa phải về ví');
        $this->assertSame(PaymentAttemptModel::REFUNDED, PaymentAttemptModel::where('code', $lan2)->value('status'));
    }

    public function test_cong_bao_lai_khoan_da_hoan_thi_khong_hoan_them(): void
    {
        $order = $this->placeOrder();
        $tongTien = (int) $order->order_total;
        $lan1 = $this->payAgain($order);
        $lan2 = $this->payAgain($order);
        $this->get(route('process.checkout', $this->vnpayReply($lan1, $tongTien, transactionNo: '111')));

        $traThua = $this->vnpayReply($lan2, $tongTien, transactionNo: '222');
        $this->get(route('process.checkout', $traThua));
        $this->post(route('payment.ipn'), $traThua);

        $this->assertSame($tongTien, $this->walletBalance(), 'Redirect và IPN cùng báo một khoản chỉ được hoàn một lần');
    }

    public function test_tien_ve_cho_lan_thu_sau_khi_don_het_han_thi_hoan_vao_vi(): void
    {
        $order = $this->placeOrder();
        $ma = $this->payAgain($order);

        $this->travel(31)->minutes();
        $this->artisan('orders:expire-unpaid');
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->order_status);

        $this->get(route('process.checkout', $this->vnpayReply($ma, (int) $order->order_total)));

        $this->assertSame((int) $order->order_total, $this->walletBalance());
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->order_status, 'Không được mở lại đơn đã trả hàng về kho');
    }

    public function test_doi_sang_cod_roi_tien_vnpay_cu_ve_thi_shipper_khong_thu_nua(): void
    {
        $order = $this->placeOrder();
        $ma = $this->payAgain($order);
        $this->changeTo($order, 'cod');

        // Khách để quên tab VNPay rồi vẫn trả tiền trên đó.
        $this->get(route('process.checkout', $this->vnpayReply($ma, (int) $order->order_total)));

        $fresh = $order->fresh();
        $this->assertSame(1, (int) $fresh->order_payment_status, 'Tiền đã về thì đơn là đã trả');
        $this->assertSame('redirect', $fresh->order_payment, 'Ghi đúng cổng đã nhận tiền để GHN không thu hộ lần nữa');
        $this->assertSame(0, $this->walletBalance());
    }

    public function test_don_cod_da_giao_ghn_thi_tien_vnpay_cu_ve_duoc_hoan_vao_vi(): void
    {
        $order = $this->placeOrder();
        $ma = $this->payAgain($order);
        $this->changeTo($order, 'cod');

        // Cửa hàng đã xác nhận và tạo vận đơn: GHN được dặn thu đủ tiền ở cửa.
        $order->fresh()->forceFill([
            'order_status' => OrderStatus::ReadyToShip,
            'order_shipping_code' => 'GHNTEST01',
        ])->save();

        $this->get(route('process.checkout', $this->vnpayReply($ma, (int) $order->order_total)))
            ->assertRedirect(route('orderBill.checkout', $order->order_code))
            ->assertSessionHas('message', fn ($m) => str_contains($m, 'hoàn vào SPay'));

        $fresh = $order->fresh();
        $this->assertSame(0, (int) $fresh->order_payment_status, 'Shipper vẫn thu tiền mặt, nên đơn không được coi là đã trả');
        $this->assertSame('cod', $fresh->order_payment);
        $this->assertSame((int) $order->order_total, $this->walletBalance());
        $this->assertSame(PaymentAttemptModel::REFUNDED, PaymentAttemptModel::where('code', $ma)->value('status'));
    }

    // ---------------------------------------------------------- the page

    public function test_trang_don_chua_tra_hien_han_va_hai_nut(): void
    {
        $order = $this->placeOrder();

        $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->assertSee('Chưa thanh toán · hạn', false)
            ->assertSee(route('order.pay', $order->order_code), false)
            ->assertSee('data-bs-target="#changePayment"', false);
    }

    public function test_trang_don_da_tra_khong_con_nut_thanh_toan(): void
    {
        $order = $this->placeOrder();
        $order->forceFill(['order_payment_status' => 1])->save();

        $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->assertDontSee(route('order.pay', $order->order_code), false)
            ->assertDontSee('data-bs-target="#changePayment"', false);
    }

    public function test_buoc_thanh_toan_chi_ghi_da_thanh_toan_khong_ghi_phuong_thuc(): void
    {
        $order = $this->placeOrder();
        $this->topUp(10_000_000);
        $this->changeTo($order, 'wallet');

        // The method is printed once, under the total; the step only says when.
        $this->actingAs($this->customer)
            ->get(route('orderBill.checkout', $order->order_code))
            ->assertOk()
            ->assertSee('Đã thanh toán')
            ->assertDontSee('Đã trừ từ SPay');
    }
}
