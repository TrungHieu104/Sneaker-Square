<?php

namespace Tests\Feature;

use App\Actions\PlaceOrderAction;
use App\Enums\OrderStatus;
use App\Models\CouponModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Pins down the payment callback fixes.
 *
 * The route these tests hit had no authentication and no signature check. Two
 * requests were enough to rob the shop:
 *
 *   /kiem-tra-trang-thai-dat-hang?vnp_TxnRef=<code>
 *       marked any order paid — free goods.
 *
 *   /kiem-tra-trang-thai-dat-hang?vnp_ResponseCode=24&vnp_TxnRef=<code>
 *       hard-deleted any order, anyone's.
 *
 * Neither needed a session, so a passer-by could do it.
 */
class PaymentCallbackTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private const VNPAY_SECRET = 'vnpay-test-secret';

    private const MOMO_SECRET = 'momo-test-secret';

    private const MOMO_ACCESS_KEY = 'momo-access-key';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedLookupTables();

        config([
            'services.vnpay.hash_secret' => self::VNPAY_SECRET,
            'services.momo.secret_key' => self::MOMO_SECRET,
            'services.momo.access_key' => self::MOMO_ACCESS_KEY,
        ]);
    }

    // ---------------------------------------------------------- fixtures

    private function placeOrder(int $price = 1_000_000, int $quantity = 2, int $stock = 10, ?CouponModel $coupon = null): OrderModel
    {
        $user = $this->makeUser();
        $product = $this->makeProduct(price: $price, stock: $stock);

        return app(PlaceOrderAction::class)->execute(
            $user,
            [$this->cartLine($product, $quantity)],
            $coupon,
            $this->makeAddress($user),
            ['payment' => 'redirect', 'note_customer' => null],
        );
    }

    /**
     * A VNPay reply, signed the way VNPay signs one.
     *
     * @return array<string, string>
     */
    private function vnpayCallback(OrderModel $order, string $responseCode = '00', ?int $amount = null): array
    {
        $params = [
            'vnp_Amount' => (string) (($amount ?? (int) $order->order_total) * 100),
            'vnp_BankCode' => 'NCB',
            'vnp_OrderInfo' => 'Thanh toan hoa don ' . $order->order_code,
            'vnp_ResponseCode' => $responseCode,
            'vnp_TmnCode' => 'TESTCODE',
            'vnp_TransactionNo' => '14022189',
            'vnp_TransactionStatus' => $responseCode,
            'vnp_TxnRef' => $order->order_code,
        ];

        ksort($params);

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = urlencode($key) . '=' . urlencode($value);
        }

        $params['vnp_SecureHash'] = hash_hmac('sha512', implode('&', $pairs), self::VNPAY_SECRET);

        return $params;
    }

    /**
     * A MoMo reply, signed the way MoMo signs one.
     *
     * @return array<string, string>
     */
    private function momoCallback(OrderModel $order, int $resultCode = 0, ?int $amount = null): array
    {
        $params = [
            'partnerCode' => 'MOMOTEST',
            'orderId' => $order->order_code,
            'requestId' => $order->order_code . '-1',
            'amount' => (string) ($amount ?? (int) $order->order_total),
            'orderInfo' => 'Thanh toan hoa don',
            'orderType' => 'momo_wallet',
            'transId' => '2547844248',
            'resultCode' => (string) $resultCode,
            'message' => 'Successful.',
            'payType' => 'qr',
            'responseTime' => '1712345678901',
            'extraData' => '',
        ];

        $raw = 'accessKey=' . self::MOMO_ACCESS_KEY
            . '&amount=' . $params['amount']
            . '&extraData=' . $params['extraData']
            . '&message=' . $params['message']
            . '&orderId=' . $params['orderId']
            . '&orderInfo=' . $params['orderInfo']
            . '&orderType=' . $params['orderType']
            . '&partnerCode=' . $params['partnerCode']
            . '&payType=' . $params['payType']
            . '&requestId=' . $params['requestId']
            . '&responseTime=' . $params['responseTime']
            . '&resultCode=' . $params['resultCode']
            . '&transId=' . $params['transId'];

        $params['signature'] = hash_hmac('sha256', $raw, self::MOMO_SECRET);

        return $params;
    }

    // ------------------------------------------------- forged callbacks

    public function test_ma_don_hang_khong_du_de_danh_dau_da_thanh_toan(): void
    {
        $order = $this->placeOrder();

        // Exactly the request that used to work: the order code and nothing else.
        $this->get(route('process.checkout', ['vnp_TxnRef' => $order->order_code]))
            ->assertRedirect(route('failed.checkout'));

        $this->assertSame(0, (int) $order->fresh()->order_payment_status, 'Đơn hàng không được đánh dấu đã thanh toán khi thiếu chữ ký');
        $this->assertSame(OrderStatus::New->value, (int) $order->fresh()->order_status, 'Và cũng không được đụng vào trạng thái đơn');
    }

    public function test_callback_thanh_cong_nhung_thieu_chu_ky_bi_tu_choi(): void
    {
        $order = $this->placeOrder();

        // Everything a genuine success carries — the right order code, the right
        // amount, response code 00 — with the signature simply left off.
        $params = $this->vnpayCallback($order);
        unset($params['vnp_SecureHash']);

        $this->get(route('process.checkout', $params))
            ->assertRedirect(route('failed.checkout'));

        $this->assertSame(0, (int) $order->fresh()->order_payment_status, 'Chỉ chữ ký mới chứng minh được phản hồi đến từ cổng thanh toán');
    }

    public function test_chu_ky_bi_sua_thi_callback_bi_tu_choi(): void
    {
        $order = $this->placeOrder();

        $params = $this->vnpayCallback($order);
        // Signed for the real total, then rewritten to 1 VND.
        $params['vnp_Amount'] = '100';

        $this->get(route('process.checkout', $params))
            ->assertRedirect(route('failed.checkout'));

        $this->assertSame(0, (int) $order->fresh()->order_payment_status);
    }

    public function test_so_tien_khong_khop_don_hang_thi_bi_tu_choi(): void
    {
        $order = $this->placeOrder();

        // Correctly signed, but for a tenth of what the order costs. Only the
        // comparison against order_total catches this one.
        $params = $this->vnpayCallback($order, amount: (int) $order->order_total / 10);

        $this->get(route('process.checkout', $params))
            ->assertRedirect(route('failed.checkout'));

        $this->assertSame(0, (int) $order->fresh()->order_payment_status);
    }

    public function test_khong_the_huy_don_cua_nguoi_khac_bang_ma_loi_gia(): void
    {
        $order = $this->placeOrder(quantity: 2, stock: 10);
        $stockAfterOrder = 8;

        // The old hard-delete request, unsigned.
        $this->get(route('process.checkout', [
            'vnp_ResponseCode' => '24',
            'vnp_TxnRef' => $order->order_code,
        ]));

        $this->assertNotNull(OrderModel::find($order->order_id), 'Đơn hàng phải còn nguyên');
        $this->assertSame(OrderStatus::New->value, (int) $order->fresh()->order_status);
        $this->assertSame($stockAfterOrder, $this->stockOf(ProductModel::first()), 'Tồn kho không được đụng tới');
    }

    // ------------------------------------------------ genuine callbacks

    public function test_callback_hop_le_danh_dau_don_da_thanh_toan(): void
    {
        $order = $this->placeOrder();

        $this->get(route('process.checkout', $this->vnpayCallback($order)))
            ->assertRedirect(route('success.checkout'));

        $paid = $order->fresh();

        $this->assertSame(1, (int) $paid->order_payment_status);
        $this->assertNotNull($paid->order_payment_time);
    }

    public function test_callback_lap_lai_khong_ghi_de_thoi_diem_thanh_toan(): void
    {
        $order = $this->placeOrder();
        $params = $this->vnpayCallback($order);

        $this->get(route('process.checkout', $params));
        $firstTime = $order->fresh()->order_payment_time;

        // Move the clock on, so a second write would be visible rather than
        // landing on the same timestamp by coincidence.
        $this->travel(10)->minutes();

        // The browser redirect and the IPN both describe the same payment.
        $this->get(route('process.checkout', $params));

        $this->assertSame(
            (string) $firstTime,
            (string) $order->fresh()->order_payment_time,
            'Lần gọi thứ hai không được ghi đè thời điểm thanh toán'
        );
    }

    // ------------------------------------------- cancelling and stock

    public function test_huy_thanh_toan_hoan_lai_ton_kho_va_luot_ma_giam_gia(): void
    {
        $coupon = CouponModel::create([
            'coupon_name' => 'Giảm 10%',
            'coupon_code' => 'GIAM10',
            'coupon_value' => 10,
            'coupon_condition' => 0,
            'coupon_quantity' => 5,
            'coupon_used' => 0,
            'coupon_start' => now()->subDay()->toDateString(),
            'coupon_end' => now()->addDays(30)->toDateString(),
        ]);

        $order = $this->placeOrder(quantity: 2, stock: 10, coupon: $coupon);
        $product = ProductModel::first();

        $this->assertSame(8, $this->stockOf($product), 'Đặt hàng phải trừ kho trước đã');
        $this->assertSame(4, (int) $coupon->fresh()->coupon_quantity);

        // The cart is already gone by this point — which is exactly why restoring
        // stock from the session could never have worked.
        session()->forget('cart');

        $this->get(route('process.checkout', $this->vnpayCallback($order, responseCode: '24')))
            ->assertRedirect(route('failed.checkout'));

        $this->assertSame(10, $this->stockOf($product), 'Hủy thanh toán phải hoàn lại tồn kho');
        $this->assertSame(OrderStatus::Cancelled->value, (int) $order->fresh()->order_status);
        $this->assertSame(5, (int) $coupon->fresh()->coupon_quantity, 'Lượt dùng mã giảm giá phải được trả lại');
        $this->assertSame(0, (int) $coupon->fresh()->coupon_used);
    }

    public function test_huy_hai_lan_khong_hoan_kho_hai_lan(): void
    {
        $order = $this->placeOrder(quantity: 2, stock: 10);
        $product = ProductModel::first();
        $params = $this->vnpayCallback($order, responseCode: '24');

        $this->get(route('process.checkout', $params));
        $this->get(route('process.checkout', $params));

        $this->assertSame(10, $this->stockOf($product), 'Tồn kho chỉ được hoàn đúng một lần');
    }

    public function test_callback_that_bai_khong_huy_duoc_don_da_thanh_toan(): void
    {
        $order = $this->placeOrder();
        $this->get(route('process.checkout', $this->vnpayCallback($order)));

        $this->assertSame(1, (int) $order->fresh()->order_payment_status);

        // A correctly signed failure arriving after the payment settled must not
        // undo it — that would be a way to void a purchase for free.
        $this->get(route('process.checkout', $this->vnpayCallback($order, responseCode: '24')));

        $this->assertSame(1, (int) $order->fresh()->order_payment_status);
        $this->assertNotSame(OrderStatus::Cancelled->value, (int) $order->fresh()->order_status);
    }

    // --------------------------------------------------------- MoMo IPN

    public function test_ipn_momo_hop_le_duoc_chap_nhan(): void
    {
        $order = $this->placeOrder();

        $this->post(route('payment.ipn'), $this->momoCallback($order))
            ->assertOk()
            ->assertJson(['resultCode' => 0]);

        $this->assertSame(1, (int) $order->fresh()->order_payment_status);
    }

    public function test_ipn_momo_sai_chu_ky_bi_tu_choi(): void
    {
        $order = $this->placeOrder();

        $params = $this->momoCallback($order);
        $params['signature'] = str_repeat('0', 64);

        $this->post(route('payment.ipn'), $params)
            ->assertStatus(400);

        $this->assertSame(0, (int) $order->fresh()->order_payment_status);
    }

    public function test_ghe_tham_url_tra_ve_ma_khong_co_tham_so_thi_ve_trang_san_pham(): void
    {
        $this->get(route('process.checkout'))
            ->assertRedirect(route('product.page'));
    }

    public function test_nguoi_dung_khong_the_huy_don_cua_nguoi_khac(): void
    {
        $order = $this->placeOrder(quantity: 2, stock: 10);
        $intruder = $this->makeUser(email: 'ke-gian@example.test', username: 'kegian');

        $this->actingAs($intruder)
            ->patch(route('cancelOrder', $order->order_code), ['inputCancelOrder' => 'đổi ý']);

        $this->assertSame(OrderStatus::New->value, (int) $order->fresh()->order_status, 'Đơn của người khác phải giữ nguyên');
        $this->assertSame(8, $this->stockOf(ProductModel::first()));
    }

    public function test_chu_don_hang_huy_duoc_don_va_duoc_hoan_kho(): void
    {
        $order = $this->placeOrder(quantity: 2, stock: 10);
        $owner = UserModel::find($order->user_id);

        $this->actingAs($owner)
            ->patch(route('cancelOrder', $order->order_code), ['inputCancelOrder' => 'đổi ý']);

        $this->assertSame(OrderStatus::Cancelled->value, (int) $order->fresh()->order_status);
        $this->assertSame(10, $this->stockOf(ProductModel::first()));
    }
}
