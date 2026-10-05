<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\SharesStorefrontLayout;
use App\Models\CategoryModel as Category;
use App\Models\LikeModel;
use App\Models\ProductModel as Product;
use App\Models\ProductQuantityModel as Quantity;
use App\Models\SizeModel as Size;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

/**
 * Product listing and the product page.
 */
class ProductController extends Controller
{
    use SharesStorefrontLayout;

    public function __construct(
        Request $request,
    ) {
        $this->shareStorefrontLayout();
        View::share('keyword', $request->input('keyword'));
    }

    public function index(Request $request)
    {
        $url = Route::getFacadeRoot()->current()->uri;
        $getAllCate = Category::withCount('getProductsInCate')
            ->where('cate_hidden', 1)->orderBy('cate_sort', 'asc')->get();
        $getAllProduct = Product::withRatingSummary()->withPriceRange()->where('pro_hidden', 1)->whereDate('pro_date', '<=', date('Y-m-d'));
        $getAccessories = Category::with([
            // The accessories block on product_page.blade.php draws stars for
            // every product in these categories, so load the ratings with them.
            'getProductsInCate' => fn ($products) => $products->withRatingSummary()->withPriceRange(),
        ])->where('cate_parent_id', 6)->where('cate_hidden', 1)->get();
        $getOneCate = Category::where(['cate_slug' => $request->route('cate_slug'), 'cate_hidden' => 1])->first();
        $getHotProduct = Product::withRatingSummary()->withPriceRange()->where('pro_hot', 1)->where('pro_hidden', 1)->whereDate('pro_date', '<=', date('Y-m-d'))->get();
        $getSaleProduct = Product::withRatingSummary()->withPriceRange()->onSale()->where('pro_hidden', 1)->whereDate('pro_date', '<=', date('Y-m-d'))->get();

        if ($getOneCate) {
            $getAllProduct = $getOneCate->getProductsInCate()->withRatingSummary()->withPriceRange()->where('pro_hidden', 1);
        } elseif (url()->current() == route('product.hot')) {
            $getAllProduct = $getAllProduct->where('pro_hot', 1);
        } elseif (url()->current() == route('product.sale')) {
            $getAllProduct = $getAllProduct->onSale();
        }

        $keyword = $request->input('keyword');
        $keyword = trim(strip_tags($keyword));
        if ($keyword) {
            $getAllProduct->where(function ($query) use ($keyword) {
                $query->where('pro_name', 'like', '%'.$keyword.'%');
            });
        }

        $this->applySort($getAllProduct, $request['sort']);

        $reqSort = $request['sort'];
        if ($getAllProduct->count() === 0) {
            Session::flash('iconMessage', 'info');

            return redirect()->back()->with('message', 'Không tìm thấy sản phẩm nào.');
        }
        $getAllProduct = $getAllProduct->paginate(9);
        if ($request->ajax()) {
            return response()->json([
                'view' => View::make('frontend.pages.product.product_filter')
                    ->with(compact('getAllCate', 'getAllProduct', 'url', 'reqSort', 'getOneCate', 'getAccessories', 'keyword'))
                    ->render(),
            ]);
        } else {
            return view('frontend.pages.product.product_page', compact('getAllCate', 'getAllProduct', 'getHotProduct', 'getSaleProduct', 'url', 'reqSort', 'getOneCate', 'getAccessories', 'keyword'));
        }
    }

    /**
     * The orderings the listing's sort menu offers, by their slug in the URL.
     * An unknown slug leaves the listing in its natural order.
     */
    private function applySort($products, ?string $sort): void
    {
        match ($sort) {
            'gia-giam' => $products->orderBySellingPrice('desc'),
            'gia-tang' => $products->orderBySellingPrice('asc'),
            'moi-nhat' => $products->orderBy('created_at', 'DESC'),
            'cu-nhat' => $products->orderBy('created_at', 'ASC'),
            'a-z' => $products->orderBy('pro_name', 'ASC'),
            'z-a' => $products->orderBy('pro_name', 'DESC'),
            default => null,
        };
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
                ->where('order.order_status', OrderStatus::Completed)
                ->exists();
        }

        $likeStatus = LikeModel::where('pro_id', $proId)->where('user_id', Auth::id())->first();

        // Both keyed "colour-size": one for the script that swaps the displayed
        // price, one for the script that greys out the sizes a colour has run out
        // of.
        $variantPrices = [];
        $variantStock = [];
        foreach ($detailProduct->getQuantities as $variant) {
            $key = $variant->color_id.'-'.$variant->size_id;

            $variantPrices[$key] = [
                'price' => $variant->sellingPrice($detailProduct),
                'list' => $variant->listPrice($detailProduct),
            ];
            $variantStock[$key] = (int) $variant->quantity;
        }

        // Answered before the customer picks anything. Without it the only way
        // to learn the shelf is empty is to fill in the form and be turned away.
        $stockTotal = (int) $detailProduct->getQuantities->sum('quantity');

        return view('frontend.pages.product.product_detail', compact('detailProduct', 'getColor', 'getSize', 'relatedProduct', 'hotProduct', 'likeStatus', 'hasPurchased', 'variantPrices', 'variantStock', 'stockTotal'));
    }
}
