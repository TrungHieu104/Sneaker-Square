<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Carbon\Traits\Timestamp;
use Illuminate\Http\Request;
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

    public function __construct(Request $request)
    {
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
        $getAllCate = Category::where('cate_hidden', 1)->orderBy('cate_sort', 'asc')->get();
        $getAllProduct = Product::where('pro_hidden', 1)->whereDate('pro_date', '<=', date("Y-m-d"));
        $getAccessories = Category::where('cate_parent_id', 6)->where('cate_hidden', 1)->get();
        $getOneCate = Category::where(['cate_slug' => $request->route('cate_slug'), 'cate_hidden' => 1])->first();
        $getHotProduct = Product::where('pro_hot', 1)->where('pro_hidden', 1)->whereDate('pro_date', '<=', date("Y-m-d"))->get();
        $getSaleProduct = Product::where('pro_price_sale', '!=', 0)->where('pro_hidden', 1)->whereDate('pro_date', '<=', date("Y-m-d"))->get();

        if ($getOneCate) {
            $getAllProduct = $getOneCate->getProductsInCate()->where('pro_hidden', 1);
        } elseif (url()->current() == route('product.hot')) {
            $getAllProduct = $getAllProduct->where('pro_hot', 1);
        } elseif (url()->current() == route('product.sale')) {
            $getAllProduct = $getAllProduct->where('pro_price_sale', '!=', 0);
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
                $getAllProduct->where('pro_hidden', 1)->orderByDesc('pro_price_sale')->orderByDesc('pro_price');
            } else if ($request['sort'] == "gia-tang") {
                $getAllProduct->where('pro_hidden', 1)->orderBy('pro_price_sale', 'asc')->orderBy('pro_price', 'asc');
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

        $detailProduct = Product::where('pro_id', $proId)->first();
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

        $relatedProduct = Product::where('cate_id', $detailProduct->cate_id)
            ->where('pro_id', '!=', $detailProduct->pro_id)
            ->where('pro_hidden', 1)
            ->orderBy('pro_views', 'desc')
            ->limit(5)
            ->get();

        $hotProduct = Product::where('pro_hot', 1)
            ->where('pro_hidden', 1)
            ->orderBy('pro_date', 'desc')
            ->limit(5)
            ->get();

        $likeStatus = LikeModel::where('pro_id', $proId)->where('user_id', Auth::id())->first();

        return view('frontend.pages.product.product_detail', compact('detailProduct', 'getColor', 'getSize', 'relatedProduct', 'hotProduct', 'likeStatus'));
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

        if (isset($get['isntEnough']) && $get['isntEnough']) {
            Session::flash('iconMessage', 'error');
            return redirect()->back()->with('message', 'Opps!!! Sản phẩm trong giỏ hàng của bạn không đủ số lượng trong kho, hãy giảm số lượng mua !');
        }

        return view('frontend.pages.product.product_cart', compact('cart', 'coupon_data'));
    }


    public function addPro(Request $request, string $proSlug = '')
    {
        $pro_name = $request['pro_name'];
        $quantity = $request['quantity'];
        $color_id = $request['options-color'];
        $size_id = $request['options-size'];
        $pro_price = $request['pro_price'];

        $pro_slug_db = Product::where('pro_slug', $proSlug)->first();
        $check_pro_quantity = Quantity::where('pro_id', $pro_slug_db->pro_id)->get();
        $check_current_pro_quantity = Quantity::where('pro_id', $pro_slug_db->pro_id)->where('color_id', $color_id)->where('size_id', $size_id)->first();
        if (!$pro_slug_db)
            return back()->with([
                'iconMessage' => 'warning',
                'message' => 'Opps!!! Sản phẩm bạn vừa chọn không có trong hệ thống'
            ]);
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
        if ($quantity > $check_current_pro_quantity->quantity) {
            return back()->with([
                'iconMessage' => 'warning',
                'message' => 'Opps!!! Số lượng bạn chọn vượt quá số lượng trong kho'
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
        $index = array_search($proSlug, array_column($cart, 'proSlug'));
        if ($index != '') {
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

    public function checkoutPOST(Request $request)
    {
        if (Auth::check()) {
            $isSuccess = false;
            $user = Auth::user();
            $deliInfo = Info::where('user_id', $user->user_id)->get();
            // $cart = $this->checkProduct($request) ?? $request->session()->get('cart');
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
            $coupon_data = $request->session()->get('coupon_data') ?? null;
            $date_format = Carbon::now('Asia/Ho_Chi_Minh')->format('Y/m/d');

            //create order
            $order_code = $request->order_code;
            $order = new Order();
            $order->order_code = $order_code;
            $defaultDeliInfo = null;
            foreach ($deliInfo as $dI) {
                if ($dI->info_default == 1) {
                    $defaultDeliInfo = $dI;
                    break;
                }
            }
            if ($defaultDeliInfo) {
                $order->order_name = $defaultDeliInfo->info_name;
                $order->order_phone = $defaultDeliInfo->info_phone;
                $order->order_email = $defaultDeliInfo->info_email;
                $order->order_address = $defaultDeliInfo->info_address;
                $order->order_local = $defaultDeliInfo->info_ward . ', ' . $defaultDeliInfo->info_district . ', ' . $defaultDeliInfo->info_province;
                $isSuccess = true;
            } else {
                Session::flash('iconMessage', 'warning');
                return redirect()->back()->with('message', 'Hãy chọn địa chỉ nhận hàng !');
            }
            $order->order_delivery_fee = $request['deliFee'];
            $order->order_coupon_value = $request['couVal'];
            $order->order_total = $request['thanhtien'];
            $order->order_payment = $request['payment'];
            $order->order_payment_status = 0;
            $order->order_date = $date_format;
            $order->note_customer = $request['note_customer'];
            $order->coupon_id = $coupon_data->coupon_id ?? null;
            $order->user_id = $user->user_id;
            $order->save();

            if (is_array($cart)) {
                foreach ($cart as $item) {
                    $pro_id = Product::where('pro_slug', $item['proSlug'])->value('pro_id');
                    $pro_quantity = Quantity::where('pro_id', $pro_id)
                        ->where('size_id', $item['size_id'])
                        ->where('color_id', $item['color_id'])
                        ->first();
                    $pro_quantity->quantity -= $item['quantity'];
                    $pro_quantity->save();
                    $size = Size::where('size_id', $item['size_id'])->value('size');
                    $color = Color::where('color_id', $item['color_id'])->value('color_vn');
                    $OD = new OrderDetail();
                    $OD->order_id = $order->order_id;
                    $OD->pro_name = $item['pro_name'];
                    $OD->size = $size ?? null;
                    $OD->color = $color ?? null;
                    $OD->price = $item['pro_price'];
                    $OD->quantity = $item['quantity'];
                    $OD->pro_id = $pro_id;
                    $OD->save();
                }
            } else
                $isSuccess = false;

            if (!$isSuccess) {
                Session::flash('iconMessage', 'error');
                return redirect()->back()->with('message', 'Đặt hàng thất bại!');
            }

            if ($coupon_data) {
                $coupon_DB = Coupon::find($coupon_data->coupon_id);
                if ($coupon_DB->coupon_quantity != 0) {
                    $coupon_DB->coupon_quantity = $coupon_DB->coupon_quantity - 1;
                    $coupon_DB->coupon_used = $coupon_DB->coupon_used + 1;
                    $coupon_DB->save();
                }
            }
            session()->forget('cart');
            session()->forget('coupon_data');
            if ($request->input('payment') !== 'cod') {
                $payment = new PaymentCheckoutController();
                return $payment->paymentCheckout($request);

            }
            return $this->processCheckout($order->order_code);
        }
    }

    public function processCheckout($order_code = null)
    {
        if (
            (isset($_GET['resultCode']) && $_GET['resultCode'] == 1006) ||
            (isset($_GET['vnp_ResponseCode']) && $_GET['vnp_ResponseCode'] == 24)
        ) {
            $order = Order::where('order_code', $_GET['orderId'] ?? $_GET['vnp_TxnRef'])->first();
            if ($order && $order->coupon_id !== null) {
                $coupon_DB = Coupon::where('coupon_id', $order->coupon_id)->first();
                $coupon_DB->coupon_quantity += 1;
                $coupon_DB->coupon_used -= 1;
                $coupon_DB->save();
            }
            $order->forceDelete();
            $cart = session()->get('cart') ?? null;
            if (is_array($cart)) {
                foreach ($cart as $item) {
                    $pro_id = Product::where('pro_slug', $item['proSlug'])->value('pro_id');
                    $pro_quantity = Quantity::where('pro_id', $pro_id)
                        ->where('size_id', $item['size_id'])
                        ->where('color_id', $item['color_id'])
                        ->first();
                    $pro_quantity->quantity += $item['quantity'];
                    $pro_quantity->save();
                }
            }
            session()->forget('coupon_data');
            return redirect()->route('failed.checkout');
        }
        if (isset($_GET['orderId']) || isset($_GET['vnp_TxnRef'])) {
            $order = Order::where('order_code', $_GET['orderId'] ?? $_GET['vnp_TxnRef'])->first();
            $order->order_payment_status = 1;
            $order->order_payment_time = Carbon::now('Asia/Ho_Chi_Minh');
            $order->save();
        }
        $this->orderMail($order_code);
        session()->forget('coupon_data');
        session()->forget('cart');
        return redirect()->route('success.checkout');

    }

    public function successCheckout()
    {
        return view('frontend.pages.product.success_checkout');
    }

    public function failedCheckout()
    {
        return view('frontend.pages.product.failed_checkout');
    }

    private function checkProduct(Request $request)
    {
        $isRemove = false;
        $isntEnough = false;
        $isEmpty = false;
        $cart = $request->session()->get('cart');
        if ($cart) {
            foreach ($cart as $key => $cart_item) {
                $pro_slug_db = Product::where('pro_slug', $cart_item['proSlug'])->first();
                if ($pro_slug_db) {
                    $check_quantity_pro = Quantity::where('pro_id', $pro_slug_db->pro_id)
                        ->where('color_id', $cart_item['color_id'])
                        ->where('size_id', $cart_item['size_id'])
                        ->first();
                    if ($cart_item['quantity'] > $check_quantity_pro->quantity) {
                        array_splice($cart, $key, 1);
                        $request->session()->put('cart', $cart);
                        $isntEnough = true;
                    }
                    if ($pro_slug_db->pro_hidden == 0) {
                        array_splice($cart, $key, 1);
                        $request->session()->put('cart', $cart);
                        $isRemove = true;
                    }
                    if (!$pro_slug_db) {
                        array_splice($cart, $key, 1);
                        $request->session()->put('cart', $cart);
                        $isRemove = true;
                    }
                } else {
                    array_splice($cart, $key, 1);
                    $request->session()->put('cart', $cart);
                    $isRemove = true;
                }

                if ($isRemove) {
                    $data['isRemove'] = $isRemove;
                    return $data;
                }
                if ($isntEnough) {
                    $data['isntEnough'] = $isntEnough;
                    return $data;
                }
            }
            return $cart;
        } else
            $isEmpty = true;
        if ($isEmpty) {
            $data['isEmpty'] = $isEmpty;
            return $data;
        }
    }

    public function orderMail($order_code = null)
    {
        if (Auth::check()) {
            $email = Auth::user()->email;
            $order = Order::where('order_code', $order_code ?? $_GET['orderId'] ?? $_GET['vnp_TxnRef'])->first();
            Mail::to($email)
                ->send(new ConfirmOrder($order));
        }
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

    public function cancelOrder(Request $request, $order_code)
    {
        $order = Order::where('order_code', $order_code)->first();
        $order->order_status = 2;
        $order->note_customer = 'Lí do hủy đơn: ' . $request->inputCancelOrder;
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