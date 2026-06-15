<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\Authuser\ResetpassRequets;
use App\Http\Requests\Frontend\Authuser\ChangeEmailRequest;
use App\Http\Requests\Frontend\Authuser\UserInfoRequest;
use App\Models\DeliveryModel;
use App\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\CateNewsModel as CateNews;
use App\Models\NewsModel as News;
use App\Models\TagsModel as Tags;
use App\Models\NewsByTagsModel as NewsByTags;
use App\Models\ContactModel as Contact;
use App\Models\DeliveryInfoModel as DeliInfo;
use App\Models\PromotionModel as Promotion;
use App\Models\OrderModel as Order;
use App\Models\OrderDetailModel as OrderDetail;
use App\Models\FaqModel as Faq;
use App\Models\MenuModel as Menu;
use App\Mail\sendMailPass;



class UserController extends Controller
{
    public function __construct()
    {
        $data = Menu::where('menu_hidden', 1)->orderBy('menu_position', 'asc')->get();
        $menu = $this->data_tree($data);
        $slide = Promotion::where('cate_slide_id', 1)->where('promotion_hidden', 1)->get();
        $contact = Contact::where('contact_hidden', 1)->limit(1)->get();
        $faq = Faq::where('faq_hidden',1)->where('faq_about',0)->orderBy('faq_id','desc')->get();
        $cateNews = CateNews::where('cate_news_hidden', 1)
            ->orderBy('cate_news_sort', 'asc')
            ->get();
        view()->share(compact('slide', 'contact', 'faq', 'cateNews', 'menu'));
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
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = UserModel::where('user_id', Auth::id())->first();
        $address = DeliInfo::where('user_id', Auth::id())->where('info_default', '1')->first();
        return view('frontend.pages.account.user_info.pages.user_info', compact('user', 'address'));
    }

    public function updatepass()
    {
        return view('frontend.pages.account.user_info.pages.update_pass');
    }

    public function changepass($remember_token)
    {
        $user = UserModel::getTokenSingle($remember_token);
        if(!empty($user)){
            $data['user'] = $user;
            return view('frontend.pages.account.user_info.pages.change_pass',$data);
        } else {
            abort(404);
        }
    }

    public function delivery()
    {
        $delivery = DeliInfo::where('user_id', Auth::id())->orderBy('info_default', 'desc')->get();
        return view('frontend.pages.account.user_info.pages.delivery', compact('delivery'));
    }

    public function successOrder(Request $request)
    {
        $order_code = $request['order_code'];
        $order_db = Order::where('order_code', $order_code)->first();
        $order_db->order_status = 10;
        $order_db->save();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Cám ơn bạn !');
    }

    public function returnOrder(Request $request)
    {
        $order_code = $request['order_code'];
        $order_db = Order::where('order_code', $order_code)->first();
        $order_db->order_status = 3;
        $order_db->note_customer = 'Lí do trả hàng: ' . $request->inputReturnOrder;
        $order_db->save();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Gửi yêu cầu trả hàng thành công !');
    }

    public function userOrder()
    {
        $current_user = Auth::user()->user_id;

        $all_order = Order::where('user_id', $current_user)
            ->orderBy('created_at', 'desc')
            ->get();
        $all_order_detail = OrderDetail::all();

        $wait_payment = Order::where('user_id', $current_user)
            ->whereIn('order_payment', ['payUrl', 'redirect'])
            ->where('order_payment_status', 0)
            ->orderBy('created_at', 'desc')
            ->get();
        $wait_confirm = Order::where('user_id', $current_user)
            ->where('order_status', 0)
            ->where(function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('order_payment', 'payUrl')
                        ->where('order_payment_status', '!=', 0);
                })->orWhere(function ($subQuery) {
                    $subQuery->where('order_payment', 'redirect')
                        ->where('order_payment_status', '!=', 0);
                })->orWhere(function ($subQuery) {
                    $subQuery->where('order_payment', '!=', 'payUrl')
                        ->where('order_payment', '!=', 'redirect');
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();
        $delivery_order = Order::where('user_id', $current_user)
            ->where('order_status', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        $success_order = Order::where('user_id', $current_user)
            ->where('order_status', 10)
            ->orderBy('created_at', 'desc')
            ->get();

        $cancel_order = Order::where('user_id', $current_user)
            ->where('order_status', 2)
            ->orderBy('created_at', 'desc')
            ->get();

        $return_order = Order::where('user_id', $current_user)
            ->where('order_status', 3)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('frontend.pages.account.user_info.pages.order', compact('all_order', 'all_order_detail', 'wait_payment', 'wait_confirm', 'delivery_order', 'success_order', 'cancel_order', 'return_order'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserInfoRequest $request, string $id)
    {
        $user = UserModel::find(Auth::id());
        $user->name = $request['name'];
        $user->password = $request['password'];
        $user->user_phone = $request['user_phone'];
        if ($request->hasFile('img__new')) {
            $file = $request->file('img__new');
            $extension = $file->getClientOriginalExtension();
            $file_name = time() . '.' . $extension;
            $file->move('uploads/images/user/', $file_name);
            $user->user_img = '/uploads/images/user/' . $file_name;
        }
        $user->save();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Cập nhật thành công !');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function sendMailPass()
    {
        $user = UserModel::find(Auth::id());
        $user->remember_token = Str::random(30);
        $user->save();
        Mail::to($user->email)->send(new sendMailPass($user));
        Session::flash('iconMessage','success');
        return redirect()->back()->with('message','Đã gửi mail');
    }

    public function updatePassPost(ResetpassRequets $request)
    {
        $user = UserModel::find(Auth::id());
        $user->password = $request['password'];
        $user->remember_token = Str::random(30);
        $user->save();


        Session::flash('iconMessage', 'success');
        return redirect(route('user.update_pass'))->with('message', 'Thay đổi mật khẩu thành công !');

    }

    public function verifiedEamil(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Mail đã được gửi!');
    }

    public function removeToken(Request $request)
    {
        UserModel::whereNotNull('remember_token')
        ->where('user_id', Auth::id())
        ->where('remember_token', '<', Carbon::now()->subMinute(1))
        ->update(['remember_token' => null]);
        return response()->json(['message' => 'Email verification removed successfully']);
    }

    public function changeEmail(ChangeEmailRequest $request, string $id){
        $user = UserModel::find(Auth::id());
        $user->email_verified_at = null ;
        $user->email = $request['email'];
        $user->save();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Thay đổi email thành công !');
    }
}