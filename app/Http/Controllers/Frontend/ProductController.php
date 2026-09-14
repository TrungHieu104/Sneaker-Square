<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\CancelOrderAction;
use App\Actions\PlaceOrderAction;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Services\CartPricingService;
use App\Services\CartService;
use App\Services\OrderMailer;
use App\Services\Payment\InvalidPaymentCallbackException;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Throwable;
use Carbon\Traits\Timestamp;
use Illuminate\Http\Request;
use App\Http\Requests\Frontend\CheckoutRequest;
use App\Http\Requests\Frontend\CouponCheckRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\CategoryModel as Category;
use App\Models\LikeModel;
use App\Models\ProductModel as Product;
use App\Models\ProductQuantityModel as Quantity;
use App\Models\PromotionModel as Promotion;
use App\Models\ContactModel as Contact;
use App\Models\FaqModel as Faq;
use App\Models\CouponModel as Coupon;
use App\Models\MenuModel as Menu;
use App\Models\DeliveryInfoModel as Info;
use App\Models\OrderDetailModel as OrderDetail;
use App\Models\OrderModel as Order;
use App\Models\SizeModel as Size;
use App\Models\ColorModel as Color;
use Illuminate\Support\Facades\App;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Barryvdh\DomPDF\PDF;

use function PHPUnit\Framework\isEmpty;

// use App;

class ProductController extends Controller
{
    public $keyword;

    /**
     * The value `order.order_payment` holds for cash on delivery.
     *
     * The two other values it can hold are the gateway names, so the payment
     * method the customer picked is enough to find the gateway again later.
     */
    private const PAYMENT_ON_DELIVERY = 'cod';

    public function __construct(
        Request $request,
        private readonly PaymentGatewayManager $gateways,
        private readonly CancelOrderAction $cancelOrder,
        private readonly OrderMailer $mailer,
        private readonly CartService $cart,
    ) {
        $keyword = $request->input('keyword');
        $slide = Promotion::where('cate_slide_id', 1)->where('promotion_hidden', 1)->get();
        $contact = Contact::where('contact_hidden', 1)->limit(1)->get();
        $faq = Faq::where('faq_hidden', 1)->where('faq_about', 0)->orderBy('faq_id', 'desc')->get();
        $data = Menu::where('menu_hidden', 1)->orderBy('menu_position', 'asc')->get();
        $menu = $this->data_tree($data);
        View::share(compact('slide', 'contact', 'faq', 'menu', 'keyword'));
    }

    function data_tree($data, $parent_id = 0, $level = 0)
    {
        $result = [];
        foreach ($data as $item) {
            if ($item['menu_parent_id'] == $parent_id) {
                $item['level'] = $level;
                $result[] = $item;
                $child = $this->data_tree($data, $item['menu_id'], $level + 1);
                $result = array_merge($result, $child);
            }
        }
        return $result;
    }

    public function index(Request $request)
    {
        $url = Route::getFacadeRoot()->current()->uri;
        $getAllCate = Category::withCount('getProductsInCate')
            ->where('cate_hidden', 1)->orderBy('cate_sort', 'asc')->get();
        $getAllProduct = Product::withRatingSummary()->withPriceRange()->where('pro_hidden', 1)->whereDate('pro_date', '<=', date("Y-m-d"));
        $getAccessories = Category::with([
            // The accessories block on product_page.blade.php draws stars for
            // every product in these categories, so load the ratings with them.
            'getProductsInCate' => fn ($products) => $products->withRatingSummary()->withPriceRange(),
        ])->where('cate_parent_id', 6)->where('cate_hidden', 1)->get();
        $getOneCate = Category::where(['cate_slug' => $request->route('cate_slug'), 'cate_hidden' => 1])->first();
        $getHotProduct = Product::withRatingSummary()->withPriceRange()->where('pro_hot', 1)->where('pro_hidden', 1)->whereDate('pro_date', '<=', date("Y-m-d"))->get();
        $getSaleProduct = Product::withRatingSummary()->withPriceRange()->onSale()->where('pro_hidden', 1)->whereDate('pro_date', '<=', date("Y-m-d"))->get();

        if ($getOneCate) {
            $getAllProduct = $getOneCate->getProductsInCate()->withRatingSummary()->withPriceRange()->where('pro_hidden', 1);
        } elseif (url()->current() == route('product.hot')) {
            $getAllProduct = $getAllProduct->where('pro_hot', 1);
        } elseif (url()->current() == route('product.sale')) {
            $getAllProduct = $getAllProduct->onSale();
        } else {
            $getAllProduct = $getAllProduct;
        }

        $keyword = $request->input('keyword');
        $keyword = trim(strip_tags($keyword));
        if ($keyword) {
            $getAllProduct->where(function ($query) use ($keyword) {
                $query->where('pro_name', 'like', '%' . $keyword . '%');
            });
        }

        if (isset($request['sort']) && !empty($request['sort'])) {
            if ($request['sort'] == "gia-giam") {
                $getAllProduct->where('pro_hidden', 1)->orderBySellingPrice('desc');
            } else if ($request['sort'] == "gia-tang") {
                $getAllProduct->where('pro_hidden', 1)->orderBySellingPrice('asc');
            } else if ($request['sort'] == "moi-nhat") {
                $getAllProduct->where('pro_hidden', 1)->orderBy('created_at', 'DESC');
            } else if ($request['sort'] == "cu-nhat") {
                $getAllProduct->where('pro_hidden', 1)->orderBy('created_at', 'ASC');
            } else if ($request['sort'] == "a-z") {
                $getAllProduct->where('pro_hidden', 1)->orderBy('pro_name', 'ASC');
            } else if ($request['sort'] == "z-a") {
                $getAllProduct->where('pro_hidden', 1)->orderBy('pro_name', 'DESC');
            }
        }

        $reqSort = $request['sort'];
        if ($getAllProduct->count() === 0) {
            Session::flash('iconMessage', 'info');
            return redirect()->back()->with('message', 'Không tìm thấy sản phẩm nào.');
        }
        $getAllProduct = $getAllProduct->paginate(9);
        if ($request->ajax()) {
            return response()->json([
                'view' => (String) View::make('frontend.pages.product.product_filter')
                    ->with(compact('getAllCate', 'getAllProduct', 'url', 'reqSort', 'getOneCate', 'getAccessories', 'keyword'))
            ]);
        } else {
            return view('frontend.pages.product.product_page', compact('getAllCate', 'getAllProduct', 'getHotProduct', 'getSaleProduct', 'url', 'reqSort', 'getOneCate', 'getAccessories', 'keyword'));
        }
    }

    public function detail(string $proSlug = '')
    {
        $proId = Product::where('pro_slug', $proSlug)
            ->where('pro_hidden', 1)
            ->whereDate('pro_date', '<=', date('Y-m-d'))
            ->value('pro_id');

        if ($proId == null) {
            Session::flash('iconMessage', 'info');
            return redirect()->route('product.page')->with('message', 'Sản phẩm không tồn tại');
        }

        // The page prints the category, the gallery and every visible review with
        // its author. Loaded here in four queries rather than one per review.
        $detailProduct = Product::withRatingSummary()->withPriceRange()
            ->with([
                'getCate',
                'getImages',
                'getQuantities',
                'getComments' => fn ($comments) => $comments->where('comment_hidden', 1)->with('getUsers'),
            ])
            ->where('pro_id', $proId)
            ->first();
        $detailProduct->pro_views++;
        $detailProduct->save();

        $getColor = Quantity::select('pro_id', 'products_quantity.color_id', 'color')
            ->where('pro_id', $proId)
            ->join('color', 'products_quantity.color_id', '=', 'color.color_id')
            ->groupBy('pro_id', 'products_quantity.color_id', 'color')
            ->get();

        $getSize = Quantity::select('pro_id', 'products_quantity.size_id', 'size')
            ->where('pro_id', $proId)
            ->join('size', 'products_quantity.size_id', '=', 'size.size_id')
            ->groupBy('pro_id', 'products_quantity.size_id', 'size')
            ->get();

        $relatedProduct = Product::withRatingSummary()->withPriceRange()->where('cate_id', $detailProduct->cate_id)
            ->where('pro_id', '!=', $detailProduct->pro_id)
            ->where('pro_hidden', 1)
            ->orderBy('pro_views', 'desc')
            ->limit(5)
            ->get();

        $hotProduct = Product::withRatingSummary()->withPriceRange()->where('pro_hot', 1)
            ->where('pro_hidden', 1)
            ->orderBy('pro_date', 'desc')
            ->limit(5)
            ->get();

        $hasPurchased = false;
        if (Auth::check()) {
            $hasPurchased = DB::table('order')
                ->join('order_details', 'order.order_id', '=', 'order_details.order_id')
                ->where('order.user_id', Auth::id())
                ->where('order_details.pro_id', $proId)
                ->where('order.order_status', 10)
                ->exists();
        }

        $likeStatus = LikeModel::where('pro_id', $proId)->where('user_id', Auth::id())->first();

        // Both keyed "colour-size": one for the script that swaps the displayed
        // price, one for the script that greys out the sizes a colour has run out
        // of.
        $variantPrices = [];
        $variantStock = [];
        foreach ($detailProduct->getQuantities as $variant) {
            $key = $variant->color_id . '-' . $variant->size_id;

            $variantPrices[$key] = [
                'price' => $variant->sellingPrice($detailProduct),
                'list' => $variant->listPrice($detailProduct),
            ];
            $variantStock[$key] = (int) $variant->quantity;
        }

        return view('frontend.pages.product.product_detail', compact('detailProduct', 'getColor', 'getSize', 'relatedProduct', 'hotProduct', 'likeStatus', 'hasPurchased', 'variantPrices', 'variantStock'));
    }

    public function cart(Request $request)
    {
        $get = $this->checkProduct($request);
        $cart = $request->session()->get('cart');
        $coupon_data = $request->session()->get('coupon_data');

        if (!is_array($cart) || empty($cart)) {
            $request->session()->forget('coupon_data');
            return redirect('/gio-hang-trong');
        }

        if (isset($get['isRemove']) && $get['isRemove']) {
            Session::flash('iconMessage', 'error');
            return redirect()->back()->with('message', 'Opps!!! Sản phẩm trong giỏ hàng của bạn vừa bị ẩn, hãy mua sản phẩm khác !');
        }

        // Shown on the cart itself rather than bounced back to wherever the
        // customer came from: the quantities have just been trimmed and this is
        // the page where that can be seen.
        if (isset($get['isntEnough']) && $get['isntEnough']) {
            Session::flash('iconMessage', 'warning');
            Session::flash('message', 'Số lượng trong giỏ đã được điều chỉnh theo số lượng còn trong kho!');
        }

        return view('frontend.pages.product.product_cart', compact('cart', 'coupon_data'));
    }


    public function addPro(Request $request, string $proSlug = '')
    {
        $quantity = $request['quantity'];
        $color_id = $request['options-color'];
        $size_id = $request['options-size'];

        $pro_slug_db = Product::where('pro_slug', $proSlug)->first();

        if (! $pro_slug_db) {
            return back()->with([
                'iconMessage' => 'warning',
                'message' => 'Opps!!! Sản phẩm bạn vừa chọn không có trong hệ thống'
            ]);
        }

        // Name and price come from the database, never from the request. The cart
        // now holds only what the customer picked: product, size, colour, quantity.
        $pro_name = $pro_slug_db->pro_name;
        $pro_price = app(CartPricingService::class)->unitPrice($pro_slug_db, $color_id, $size_id);
        $check_pro_quantity = Quantity::where('pro_id', $pro_slug_db->pro_id)->get();
        $check_current_pro_quantity = Quantity::where('pro_id', $pro_slug_db->pro_id)->where('color_id', $color_id)->where('size_id', $size_id)->first();
        if (!$check_pro_quantity || !$check_current_pro_quantity)
            return back()->with([
                'iconMessage' => 'warning',
                'message' => 'Opps!!! Sản phẩm bạn vừa chọn chưa được nhập trong kho'
            ]);
        if ($check_current_pro_quantity->quantity == 0)
            return back()->with([
                'iconMessage' => 'warning',
                'message' => 'Opps!!! Sản phẩm bạn vừa chọn đã hết hàng'
            ]);
        // Counted against what this variant already holds in the cart, not against
        // the amount being added: two helpings of five each passed a stock of five
        // one at a time.
        $alreadyInCart = 0;
        foreach ($request->session()->get('cart', []) as $item) {
            if (($item['proSlug'] ?? null) == $proSlug
                && ($item['color_id'] ?? null) == $color_id
                && ($item['size_id'] ?? null) == $size_id) {
                $alreadyInCart += (int) ($item['quantity'] ?? 0);
            }
        }

        if ($quantity + $alreadyInCart > $check_current_pro_quantity->quantity) {
            return back()->with([
                'iconMessage' => 'warning',
                'message' => $alreadyInCart > 0
                    ? 'Opps!!! Giỏ hàng của bạn đã có ' . $alreadyInCart . ' sản phẩm này, trong kho chỉ còn ' . $check_current_pro_quantity->quantity
                    : 'Opps!!! Số lượng bạn chọn vượt quá số lượng trong kho'
            ]);
        }

        if (!$request->session()->has('cart')) {
            $request->session()->put('cart', [['proSlug' => $proSlug, 'pro_name' => $pro_name, 'quantity' => $quantity, 'color_id' => $color_id, 'size_id' => $size_id, 'pro_price' => $pro_price]]);
        } else {
            $cart = $request->session()->get('cart');
            $found = false;

            foreach ($cart as $key => $item) {
                if ($item['proSlug'] == $proSlug && $item['pro_name'] == $pro_name && $item['size_id'] == $size_id && $item['color_id'] == $color_id && $item['pro_price'] == $pro_price) {
                    $cart[$key]['quantity'] += $quantity;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $cart[] = ['proSlug' => $proSlug, 'pro_name' => $pro_name, 'quantity' => $quantity, 'color_id' => $color_id, 'size_id' => $size_id, 'pro_price' => $pro_price];
            }
            $request->session()->put('cart', $cart);
        }
        return redirect('/gio-hang');
    }

    public function delPro(Request $request, string $proSlug = '')
    {
        $cart = $request->session()->get('cart');
        $coupon_data = $request->session()->get('coupon_data');
        $giatri_donhang = $request['giatri_donhang'];
        // Matched on the variant, not the product: the same shoe in two colours is
        // two cart lines, and slug alone removes whichever comes first.
        $colorId = $request->input('color_id');
        $sizeId = $request->input('size_id');

        $index = collect($cart)->search(fn ($item) => $item['proSlug'] === $proSlug
            && (string) ($item['color_id'] ?? '') === (string) $colorId
            && (string) ($item['size_id'] ?? '') === (string) $sizeId);

        if ($index !== false) {
            array_splice($cart, $index, 1);
            $request->session()->put('cart', $cart);
        }
        if ($coupon_data !== null && ($coupon_data->coupon_value >= $giatri_donhang)) {
            session()->forget('coupon_data');
            Session::flash('iconMessage', 'warning');
            return redirect()->back()->with('message', 'Cần đặt hàng có giá trị cao hơn để sử dụng mã giảm giá này.');
        }
        Session::flash('iconMessage', 'success');
        return redirect()->route('product.cart')->with('message', 'Xóa sản phẩm thành công!');
    }

    public function delCart(Request $request)
    {
        $request->session()->forget('cart');
        $request->session()->forget('coupon_data');
        Session::flash('iconMessage', 'success');
        return redirect()->route('empty.cart')->with('message', 'Xóa giỏ hàng thành công!');
    }

    public function checkCoupon(CouponCheckRequest $request)
    {
        $today = Carbon::now('Asia/Ho_Chi_Minh')->format('Y/m/d');
        $coupon = trim($request['coupon']);
        $giatri_donhang = $request['giatri_donhang'];

        $coupon_data = Coupon::where('coupon_code', $coupon)->first();

        if (!$coupon_data) {
            Session::flash('iconMessage', 'error');
            return redirect()->back()->with('message', 'Opps!!! Mã giảm giá này không khả dụng.');
        }
        if ($coupon_data->coupon_quantity == 0) {
            Session::flash('iconMessage', 'warning');
            return redirect()->back()->with('message', 'Rất tiếc! Mã giảm giá đã hết lượt sử dụng.');
        }

        if (
            !((
                date('Y-m-d', strtotime($coupon_data->coupon_end))
                >=
                date('Y-m-d', strtotime($today))
            ))
        ) {
            Session::flash('iconMessage', 'warning');
            return redirect()->back()->with('message', 'Opps!!! Mã giảm giá đã quá hạn sử dụng.');
        }

        if ($coupon_data->coupon_value >= $giatri_donhang) {
            session()->forget('coupon_data');
            Session::flash('iconMessage', 'warning');
            return redirect()->back()->with('message', 'Cần đặt hàng có giá trị cao hơn để sử dụng mã giảm giá này.');
        }

        session()->put('coupon_data', $coupon_data);

        Session::flash('iconMessage', 'success');
        if ($coupon_data->coupon_condition == 1)
            return redirect()
                ->back()
                ->with(
                    'message',
                    'Tuyệt quá! Bạn đã sử dụng thành công mã giảm giá ' . number_format($coupon_data->coupon_value, 0, ',', '.') . 'VNĐ'
                );
        else
            return redirect()
                ->back()
                ->with(
                    'message',
                    'Tuyệt quá! Bạn đã sử dụng thành công mã giảm giá ' . number_format($coupon_data->coupon_value, 0, ',', '.') . '%'
                );
    }

    public function removeCoupon(Request $request)
    {
        session()->forget('coupon_data');

        Session::flash('iconMessage', 'success');
        return redirect()->back()->with('message', 'Mã giảm giá đã được loại bỏ thành công.');
    }

    public function emptycart(Request $request)
    {
        return view('frontend.pages.product.empty_cart');
    }

    public function checkout(Request $request)
    {
        if (!Auth::check()) {
            Session::flash('iconMessage', 'warning');
            return redirect()->route('user.login')->with('message', 'Vui lòng đăng nhập trước khi thanh toán.');
        }
        $user_id = Auth::user()->user_id;
        $InfoDeli = Info::where('user_id', $user_id)->get();
        $get = $this->checkProduct($request);
        $cart = $request->session()->get('cart');
        $coupon_data = $request->session()->get('coupon_data');
        if (!is_array($cart) || empty($cart)) {
            $request->session()->forget('coupon_data');
            return redirect('/gio-hang-trong');
        }
        if (isset($get['isRemove']) && $get['isRemove']) {
            Session::flash('iconMessage', 'error');
            return redirect()->back()->with('message', 'Opps!!! Sản phẩm trong giỏ hàng của bạn vừa bị ẩn, hãy mua sản phẩm khác !');
        }
        if (isset($get['isntEnough']) && $get['isntEnough']) {
            Session::flash('iconMessage', 'error');
            return redirect()->back()->with('message', 'Opps!!! Sản phẩm trong giỏ hàng của bạn không đủ số lượng trong kho, hãy giảm số lượng mua !');
        }
        // if (!is_array($cart) || count($cart) <= 0) {
        //     Session::flash('iconMessage', 'warning');
        //     return redirect()->route('product.page')->with('message', 'Hãy mua hàng trước nhé.');
        // }
        return view('frontend.pages.product.product_checkout', compact('cart', 'coupon_data', 'InfoDeli'));

    }

    public function checkoutPOST(CheckoutRequest $request, PlaceOrderAction $placeOrder)
    {
        if (! Auth::check()) {
            return redirect()->route('user.login');
        }

        $user = Auth::user();

        // checkProduct() may drop items from the cart, so it has to run before we read it.
        $check = $this->checkProduct($request);
        $cart = $request->session()->get('cart');
        $coupon = $request->session()->get('coupon_data');
        $coupon = $coupon instanceof Coupon ? $coupon : null;

        if (! is_array($cart) || empty($cart)) {
            $request->session()->forget('coupon_data');

            return redirect('/gio-hang-trong');
        }

        if (isset($check['isRemove']) && $check['isRemove']) {
            Session::flash('iconMessage', 'error');

            return redirect()->back()->with('message', 'Opps!!! Sản phẩm trong giỏ hàng của bạn vừa bị ẩn, hãy mua sản phẩm khác !');
        }

        if (isset($check['isntEnough']) && $check['isntEnough']) {
            Session::flash('iconMessage', 'error');

            return redirect()->back()->with('message', 'Opps!!! Sản phẩm trong giỏ hàng của bạn không đủ số lượng trong kho, hãy giảm số lượng mua !');
        }

        $address = Info::where('user_id', $user->user_id)->where('info_default', 1)->first();

        if (! $address) {
            Session::flash('iconMessage', 'warning');

            return redirect()->back()->with('message', 'Hãy chọn địa chỉ nhận hàng !');
        }

        // Every amount is recomputed inside PlaceOrderAction from the database.
        // The thanhtien / deliFee / couVal values posted by the browser are ignored.
        try {
            $order = $placeOrder->execute($user, $cart, $coupon, $address, [
                'payment' => $request->input('payment'),
                'note_customer' => $request->input('note_customer'),
            ]);
        } catch (InsufficientStockException $e) {
            Session::flash('iconMessage', 'error');

            return redirect()->back()->with('message', $e->userMessage());
        }

        session()->forget('cart');
        session()->forget('coupon_data');

        if ($order->order_payment === self::PAYMENT_ON_DELIVERY) {
            $this->mailer->sendConfirmation($order);

            return redirect()->route('success.checkout');
        }

        return $this->startGatewayPayment($order);
    }

    /**
     * Sends the customer to the gateway that will take their money.
     *
     * If building that URL fails the order is cancelled straight away, so the
     * stock it reserved goes back instead of sitting behind an order nobody can
     * ever pay for.
     */
    private function startGatewayPayment(Order $order): RedirectResponse
    {
        $gateway = $this->gateways->byName((string) $order->order_payment);

        try {
            if (! $gateway) {
                throw new InvalidPaymentCallbackException('Phương thức thanh toán không hợp lệ.');
            }

            $checkoutUrl = $gateway->checkoutUrl($order);
        } catch (Throwable $e) {
            report($e);
            $this->cancelOrder->execute($order);

            Session::flash('iconMessage', 'error');

            return redirect()->route('product.cart')
                ->with('message', 'Không thể kết nối cổng thanh toán, đơn hàng đã được hủy.');
        }

        $order->order_payment_url = $checkoutUrl;
        $order->save();

        return redirect()->away($checkoutUrl);
    }

    public function successCheckout()
    {
        return view('frontend.pages.product.success_checkout');
    }

    public function failedCheckout()
    {
        return view('frontend.pages.product.failed_checkout');
    }

    /**
     * Drops anything from the cart the shop can no longer sell.
     *
     * Returns the same shape the callers have always expected: the surviving
     * cart, or a flag array saying why something went missing.
     *
     * @return array<int, array<string, mixed>>|array<string, bool>
     */
    private function checkProduct(Request $request)
    {
        $cart = $request->session()->get('cart');

        if (! is_array($cart) || $cart === []) {
            return ['isEmpty' => true];
        }

        $result = $this->cart->screen($cart);
        $request->session()->put('cart', $result['cart']);

        if ($result['removed']) {
            return ['isRemove' => true];
        }

        if ($result['insufficient']) {
            return ['isntEnough' => true];
        }

        return $result['cart'];
    }

    public function orderBill(string $order_code = '')
    {
        if (Auth::check()) {
            $order = Order::where('order_code', $order_code)->first();
            if ($order) {
                $coupon_data = Coupon::where('coupon_id', $order->coupon_id)->first();
                $orderDetail = OrderDetail::where('order_id', $order->order_id)->get();
                return view('frontend.pages.product.order_bill', compact('order', 'coupon_data', 'orderDetail'));
            } else {
                Session::flash('iconMessage', 'warning');
                return redirect()->back()->with('message', 'Không tồn tại đơn hàng này !');
            }
        } else {
            Session::flash('iconMessage', 'warning');
            return redirect()->route('user.login')->with('message', 'Vui lòng đăng nhập trước khi xem đơn hàng.');
        }
    }

    public function printBill($order_code)
    {
        if (Auth::check()) {
            $check_current_order = Order::where('order_code', $order_code)->first();
            if (Auth::guard('web')->user()->user_id != $check_current_order->user_id) {
                Session::flash('iconMessage', 'warning');
                return redirect()->back()->with('message', 'Bạn chỉ được quyền xuất hóa đơn của bạn !');
            }
            $pdf = App::make('dompdf.wrapper');
            $pdf->loadHTML($this->print_order_convert($order_code));
            return $pdf->stream();
        } else {
            Session::flash('iconMessage', 'warning');
            return redirect()->route('user.login')->with('message', 'Vui lòng đăng nhập trước xuất hóa đơn.');
        }
    }

    public function print_order_convert($order_code)
    {
        $order = Order::where('order_code', $order_code)->first();
        $od = OrderDetail::where('order_id', $order->order_id)->get();
        return view('frontend.pages.product.pdf.print_bill', compact('od', 'order'));
    }

    /**
     * A customer cancelling their own order.
     *
     * Two things were missing. Anyone signed in could cancel any order by
     * guessing its code, because the order was never checked against the
     * caller. And the goods stayed reserved: the order was marked cancelled
     * without ever putting the stock back.
     */
    public function cancelOrder(Request $request, $order_code)
    {
        if (! Auth::check()) {
            return redirect()->route('user.login');
        }

        $order = Order::where('order_code', $order_code)
            ->where('user_id', Auth::user()->user_id)
            ->first();

        if (! $order) {
            Session::flash('iconMessage', 'warning');

            return redirect()->back()->with('message', 'Không tìm thấy đơn hàng này trong tài khoản của bạn !');
        }

        // A person cancelling an order they paid for is a refund, not a fraud
        // attempt, so this call is allowed to touch paid orders.
        if (! $this->cancelOrder->execute($order, allowPaid: true)) {
            Session::flash('iconMessage', 'warning');

            return redirect()->back()->with('message', 'Đơn hàng này đã được hủy trước đó.');
        }

        $order->note_customer = 'Lí do hủy đơn: ' . $request->input('inputCancelOrder');
        $order->save();

        Session::flash('iconMessage', 'success');

        return redirect()->back()->with([
            'message' => ' Gửi yêu cầu hủy đơn thành công !',
            'text' => 'Số tiền sẽ được hoàn trả trong vòng 24h',
        ]);
    }

    public function getToken()
    {
        $dataToken = [
            'tokenAPI' => '54ac66e2-52c7-11ee-96dc-de6f804954c9',
            'shopID' => 4541647,
        ];
        return response()->json($dataToken);
    }
}