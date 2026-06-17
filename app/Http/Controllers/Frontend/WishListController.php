<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Models\WishListModel;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\ContactModel as Contact;
use App\Models\PromotionModel as Promotion;
use App\Models\FaqModel as Faq;
use App\Models\CateNewsModel as CateNews;
use Illuminate\Support\Facades\Session;
use App\Models\MenuModel as Menu;
use Illuminate\Support\Facades\View;

class WishListController extends Controller
{
    public function __construct()
    {
        $slide = Promotion::where('cate_slide_id',1)->where('promotion_hidden',1)->get();
        $contact = Contact::where('contact_hidden',1)->limit(1)->get();
        $faq = Faq::where('faq_hidden',1)->where('faq_about',0)->orderBy('faq_id','desc')->get();
        $data = Menu::where('menu_hidden',1)->orderBy('menu_position','asc')->get();
        $menu = $this->data_tree($data);
        View::share(compact('slide','contact','faq','menu'));
    }

    public function data_tree($data, $parent_id = 0, $level=0){
        $result = [];
        foreach($data as $item){
            if($item['menu_parent_id'] == $parent_id){
                $item['level'] = $level;
                $result[] = $item;
                $child = $this->data_tree($data, $item['menu_id'], $level+1);
                $result = array_merge($result, $child);
            }
        }
        return $result;
    }
    
    public function index()
    {
        $wishList = WishListModel::where('user_id', Auth::id())
        ->join('products', 'like.pro_id', '=', 'products.pro_id')
        ->where('pro_hidden', 1)
        ->orderBy('like.created_at', 'DESC')->get();
        return view('frontend.pages.product.product_wishlist', compact('wishList'));
    }

    public function store(Request $request)
    {
        if(Auth::check()) {
            $pro_id = $request->input('product-id');
            if(WishListModel::where('user_id', Auth::id())->where('pro_id', $pro_id)->exists()) {
                WishListModel::where('user_id', Auth::id())->where('pro_id', $pro_id)->delete();
                return response()->json([
                    'status' => "Đã xóa khỏi mục yêu thích",
                    'icon' => 'success'
                ]);
            } else {
                $wishList = new WishListModel();
                $wishList->pro_id = $pro_id;
                $wishList->user_id = Auth::id();
                $wishList->save();
                return response()->json([
                    'status' => "Thêm mục yêu thích thành công",
                    'icon' => 'success'
                ]);
            }

        } else {
            return response()->json([
                'status' => "Vui lòng đăng nhập",
                'icon' => 'error'
            ]);
        }
    }

    public function count()
    {
        $countWish = WishListModel::where('user_id', Auth::id())
        ->join('products', 'like.pro_id', '=', 'products.pro_id')
        ->where('pro_hidden', 1)
        ->count();
        return response()->json([
            'count' => $countWish
        ]);
    }

    public function destroy(Request $request)
    {
        if(Auth::check()) {
            $pro_id = $request->input('pro_id');
            if(WishListModel::where('pro_id', $pro_id)->where('user_id', Auth::id())->exists()) 
            {
                $wishList = WishListModel::where('pro_id', $pro_id)->where('user_id', Auth::id())->first();
                $wishList->delete();
                return response()->json([
                    'status' => "Xóa thành công",
                    'icon' => 'success'
                ]);
            }
        }
    }
}