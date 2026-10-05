<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\SharesStorefrontLayout;
use App\Models\WishListModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishListController extends Controller
{
    use SharesStorefrontLayout;

    public function __construct()
    {
        $this->shareStorefrontLayout();
    }

    public function index()
    {
        // Every card prints a price range; without this each one queries for it.
        $wishList = WishListModel::with([
            'products' => fn ($products) => $products->withPriceRange(),
        ])
            ->where('user_id', Auth::id())
            ->join('products', 'like.pro_id', '=', 'products.pro_id')
            ->where('pro_hidden', 1)
            ->orderBy('like.created_at', 'DESC')->get();

        return view('frontend.pages.product.product_wishlist', compact('wishList'));
    }

    public function store(Request $request)
    {
        if (Auth::check()) {
            $pro_id = $request->input('product-id');
            if (WishListModel::where('user_id', Auth::id())->where('pro_id', $pro_id)->exists()) {
                WishListModel::where('user_id', Auth::id())->where('pro_id', $pro_id)->delete();

                return response()->json([
                    'status' => 'Đã xóa khỏi mục yêu thích',
                    'icon' => 'success',
                ]);
            } else {
                $wishList = new WishListModel;
                $wishList->pro_id = $pro_id;
                $wishList->user_id = Auth::id();
                $wishList->save();

                return response()->json([
                    'status' => 'Thêm mục yêu thích thành công',
                    'icon' => 'success',
                ]);
            }

        } else {
            return response()->json([
                'status' => 'Vui lòng đăng nhập',
                'icon' => 'error',
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
            'count' => $countWish,
        ]);
    }

    public function destroy(Request $request)
    {
        if (Auth::check()) {
            $pro_id = $request->input('pro_id');
            if (WishListModel::where('pro_id', $pro_id)->where('user_id', Auth::id())->exists()) {
                $wishList = WishListModel::where('pro_id', $pro_id)->where('user_id', Auth::id())->first();
                $wishList->delete();

                return response()->json([
                    'status' => 'Xóa thành công',
                    'icon' => 'success',
                ]);
            }
        }
    }
}
