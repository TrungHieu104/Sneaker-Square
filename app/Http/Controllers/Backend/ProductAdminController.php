<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Requests\Backend\ProductRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Carbon;
use App\Models\CategoryModel as Category;
use App\Models\ProductModel as Product;
use App\Models\CommentModel as Comment;
use App\Models\ProductQuantityModel as Quantity;
use App\Models\OrderDetailModel as OrderDetail;

class ProductAdminController extends Controller
{
    public function __construct(Request $request)
    {
        $keyword = $request->input('keyword');
        View::share(compact('keyword'));
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $orderBy = $request->input('orderBy', 'pro_id');
        $orderType = $request->input('orderType', 'asc');

        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['pro_code', 'pro_name'];
        $allProducts = $this->performSearch(Product::orderBy($orderBy, $orderType), $keyword, $searchableFields)
        ->paginate(10)
        ->withQueryString();

        $allCates = Category::all();
        return view('backend.pages.product.products.product_list', compact('allProducts', 'allCates', 'orderBy', 'orderType'));
    }

    /**
     * Display statistical
     */
    public function productsStatistical(Request $request) 
    {
        $thismonth = Carbon::now('Asia/Ho_Chi_minh')->startOfMonth()->toDateString();
        $now = Carbon::now('Asia/Ho_Chi_minh')->toDateString();

        $orderBy = $request->input('orderBy', 'order_details_id');
        $orderType = $request->input('orderType', 'asc');

        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchStock = ['products.pro_name', 'color.color_vn'];
        $searchableFields = ['products.pro_name', 'order_details.color'];

        $statistical = OrderDetail::select(
                'products.pro_name',
                'color',
                'price',
                DB::raw('SUM(quantity) AS total_quantity'),
                'order_details.pro_id',
                'products.capital_price',
            )
            ->join('order', 'order_details.order_id', '=', 'order.order_id')
            ->join('products', 'order_details.pro_id', '=', 'products.pro_id')
            ->where('order.order_status', 10)
            ->whereBetween('order.order_date',[$thismonth,$now])
            ->groupBy(
                'products.pro_name',
                'order_details.color', 
                'order_details.price', 
                'order_details.pro_id',
                'products.capital_price',
            )
            ->orderBy('total_quantity', 'desc')
            ->limit(10)
            ->get();
        $stock = $this->performSearch(
            Quantity::select(
                DB::raw('SUM(products_quantity.quantity) AS total_quantity'),
                'products_quantity.pro_id',
                'products_quantity.color_id',
                'color.color_vn',
            )
            ->join('products', 'products_quantity.pro_id', '=', 'products.pro_id')
            ->join('color', 'products_quantity.color_id', '=', 'color.color_id', 'left')
            ->where('products_quantity.quantity', '<=', 10)
            ->groupBy(
                'products_quantity.pro_id',
                'products_quantity.color_id',
                'color.color_vn',
            )
            ->orderBy('products_quantity.quantity', 'asc'), $keyword, $searchStock)
            ->get();
        return view('backend.pages.statistical.products', compact('statistical', 'orderBy', 'orderType', 'stock'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $allCates = Category::all();
        $today = today()->toDateString();
        return view('backend.pages.product.products.product_create', compact('allCates', 'today'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        $input = $request->post();
        $pro_name = ($request->has('pro_name'))? ucwords($input['pro_name']):"";
        $pro_slug = ($request->has('pro_slug'))? $input['pro_slug']:"";
        $pro_code = ($request->has('pro_code'))? $input['pro_code']:"";
        $pro_price = ($request->has('pro_price'))? (int)$input['pro_price']:"";
        $pro_price_sale = ($request->has('pro_price_sale'))? (int)$input['pro_price_sale']:0;
        $capital_price = ($request->has('capital_price'))? (int)$input['capital_price']:"";
        $pro_description = ($request->has('pro_description'))? $input['pro_description']:"";
        $pro_SEO_title = ($request->has('pro_SEO_title'))? $input['pro_SEO_title']:"";
        $pro_meta_keywords = ($request->has('pro_meta_keywords'))? $input['pro_meta_keywords']:"";
        $pro_meta_description = ($request->has('pro_meta_description'))? $input['pro_meta_description']:"";
        $pro_date = ($request->has('pro_date'))? $input['pro_date']:"";
        $cate_id = ($request->has('cate_id'))? $input['cate_id']:"";
        $pro_hot = ($request->has('pro_hot'))? (int)$input['pro_hot']:0;
        $pro_hidden = ($request->has('pro_hidden'))? (int)$input['pro_hidden']:0;

        $product = new Product;
        $product->pro_name = $pro_name;
        $product->pro_slug = $pro_slug;
        $product->pro_code = $pro_code;
        $product->pro_price = $pro_price;
        $product->pro_price_sale = $pro_price_sale;
        $product->capital_price = $capital_price;
        $product->pro_description = $pro_description;
        $product->pro_SEO_title = $pro_SEO_title;
        $product->pro_meta_keywords = $pro_meta_keywords;
        $product->pro_meta_description = $pro_meta_description;
        $product->pro_date = date('Y-m-d', strtotime($pro_date));
        $product->cate_id = $cate_id;
        $product->pro_hot = $pro_hot;
        $product->pro_hidden = $pro_hidden;
        if($request->has('pro_img'))
        {
            $file = $request->file('pro_img');
            $file_name = time().'-'.$file->getClientOriginalName();
            $file->move(public_path('backend/uploads/product/'), $file_name);
            $product->pro_img = 'backend/uploads/product/'.$file_name;
        }
        $product->save();
        Session::flash('iconMessage', 'success');
        return redirect(route('product.index'))->with('message', 'Thêm sản phẩm thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function update(Request $request, string $proId)
    {
        $input = $request->post();
        $product = Product::find($proId);
        $pro_name = ($request->has('pro_name'))? ucwords($input['pro_name']):"";
        $pro_slug = ($request->has('pro_slug'))? $input['pro_slug']:"";
        $pro_code = ($request->has('pro_code'))? $input['pro_code']:"";
        $pro_price = ($request->has('pro_price'))? (int)$input['pro_price']:"";
        $pro_price_sale = ($request->has('pro_price_sale'))? (int)$input['pro_price_sale']:0;
        $capital_price = ($request->has('capital_price'))? (int)$input['capital_price']:"";
        $pro_description = ($request->has('pro_description'))? $input['pro_description']:"";
        $pro_SEO_title = ($request->has('pro_SEO_title'))? $input['pro_SEO_title']:"";
        $pro_meta_keywords = ($request->has('pro_meta_keywords'))? $input['pro_meta_keywords']:"";
        $pro_meta_description = ($request->has('pro_meta_description'))? $input['pro_meta_description']:"";
        $pro_date = ($request->has('pro_date'))? $input['pro_date']:"";
        $cate_id = ($request->has('cate_id'))? $input['cate_id']:"";

        $rules = (new ProductRequest)->rules();
        $messages = (new ProductRequest)->messages();
        if ($product->pro_name != $pro_name) {
            $rule = [
                'pro_name' => 'required|max: 255|unique:products',
            ];
            $validation = Validator::make($input, $rule, $messages);
            $errors = $validation->errors();
            if ($validation->fails()) {
                $request->session();
                Session::flash('iconMessage', 'error');
                return back()->with('message', $errors->first());
            }
        } elseif ($product->pro_code != $pro_code) {
            $rule = [
                'pro_code' => 'required|max: 50|unique:products',
            ];
            $validation = Validator::make($input, $rule, $messages);
            $errors = $validation->errors();
            if ($validation->fails()) {
                $request->session();
                Session::flash('iconMessage', 'error');
                return back()->with('message', $errors->first());
            }
        }

        $product->pro_name = $pro_name;
        $product->pro_slug = $pro_slug;
        $product->pro_code = $pro_code;
        $product->pro_price = $pro_price;
        $product->pro_price_sale = $pro_price_sale;
        $product->capital_price = $capital_price;
        $product->pro_description = $pro_description;
        $product->pro_SEO_title = $pro_SEO_title;
        $product->pro_meta_keywords = $pro_meta_keywords;
        $product->pro_meta_description = $pro_meta_description;
        $product->pro_date = date('Y-m-d', strtotime($pro_date));
        $product->cate_id = $cate_id;
        if($request->has('pro_img'))
        {
            $rule = [
                'pro_img' => 'image',
            ];
            $validation = Validator::make($request->all(), $rule, $messages);
            $errors = $validation->errors();
            if ($validation->fails()) {
                $request->session();
                Session::flash('iconMessage', 'error');
                return back()->with('message', $errors->first());
            } else {
                $file = $request->file('pro_img');
                $file_name = time().'-'.$file->getClientOriginalName();
                $file->move(public_path('backend/uploads/product/'), $file_name);
                $product->pro_img = 'backend/uploads/product/'.$file_name;
            }
            
        }
        $rule = [
            'capital_price' => 'required|numeric|min: 1|max: 9999999999|integer',
            'pro_price' => 'required|numeric|min: 1|max: 9999999999|integer|gt:capital_price',
            'pro_price_sale' => 'nullable|numeric|min: 0|max: 9999999999|integer|lt:pro_price',
            'pro_SEO_title' => 'nullable|max: 255',
            'pro_meta_keywords' => 'nullable|max: 2000',
            'pro_meta_description' => 'nullable|max: 2000',
            'pro_date' => 'required',
            'cate_id' => 'required',
        ];
        $validation = Validator::make($input, $rule, $messages);
        $errors = $validation->errors();
        if ($validation->fails()) {
            $request->session();
            Session::flash('iconMessage', 'error');
            return back()->with('message', $errors->first());
        }
        $product->save();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Cập nhật sản phẩm thành công!');
    }

    public function updateStatus(Request $request, string $proId) 
    {
        $input = $request->post();
        $pro_hidden = ($request->has('pro_hidden'))? $input['pro_hidden']:"";
        $product = Product::find($proId);

        if (!$product) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu'], abort(404));
        }

        $product->pro_hidden = $pro_hidden;
        $product->save();
        $comment = Comment::where('pro_id', $product->pro_id)
                            -> update(['comment_hidden' => $pro_hidden]);

        return response()->json(['message' => 'Cập nhật thành công']); 
    }

    public function updateHot(Request $request, string $proId)
    {
        $input = $request->post();
        $pro_hot = ($request->has('pro_hot'))? $input['pro_hot']:"";
        $product = Product::find($proId);

        if (!$product) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu'], abort(404));
        }

        $product->pro_hot = $pro_hot;
        $product->save();

        return response()->json(['message' => 'Cập nhật thành công']); 
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $proId)
    {
        $product = Product::find($proId);
        $inStock = Quantity::select('pro_id', DB::raw('SUM(quantity) AS total_quantity'))
                            -> where('pro_id', $proId)
                            -> groupBy('pro_id')
                            -> first();
        $soldProduct = OrderDetail::where('pro_id', $proId)->first();
        if ($product == null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect()->back()->with('message', 'Không tồn tại sản phẩm.');
        } elseif($inStock != null && $inStock->total_quantity != 0) {
            $request->session();
            Session::flash('iconMessage', 'error');
            return redirect()->back()->with('message', 'Không thể xóa sản phẩm còn hàng trong kho.');
        } elseif ($soldProduct != null) {
            $request->session();
            Session::flash('iconMessage', 'error');
            return redirect()->back()->with('message', 'Không thể xóa sản phẩm đã bán.');
        } else {
            $product->delete();
            $comment = Comment::where('pro_id', $product->pro_id)->delete();
            Session::flash('iconMessage', 'success');
            return redirect(route('product.index'))->with('message', 'Xóa sản phẩm thành công!'); 
        }
    }

    /**
     * Show trashed view.
     */
    public function trashed(Request $request) 
    {
        $orderBy = $request->input('orderBy', 'pro_id');
        $orderType = $request->input('orderType', 'asc');

        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['products.pro_code', 'products.pro_name'];

        $productTrash = $this->performSearch(Product::onlyTrashed($orderBy, $orderType), $keyword, $searchableFields)
                                    ->paginate(20)
                                    ->withQueryString();
        return view('backend.pages.product.products.product_trash', compact('productTrash', 'orderBy', 'orderType'));
    }

    /**
     * Restore one product.
     */
    public function restore(string $proId) 
    {
        $product = Product::withTrashed()->where('pro_id', $proId)->first();
        if ($product) {
            $product->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Khôi phục sản phẩm thành công!');
        } else {
            return abort(404);
        }
    }

    /**
     * Restore all products.
     */
    public function restoreAll() 
    {
        Product::onlyTrashed()->restore();
        Session::flash('iconMessage', 'success');
        return redirect(route('product.index'))->with('message', 'Khôi phục tất cả sản phẩm thành công!');
    }

    /**
     * Permanently delete one product.
     */
    public function delete(string $proId) 
    {
        $product = Product::withTrashed()->find($proId);
        if ($product) {
            $product->forceDelete(); 
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa sản phẩm thành công!');
        } else {
            return abort(404); 
        }
    }

    /**
     * Permanently delete all products.
     */
    public function deleteAll() 
    {
        Product::onlyTrashed()->forceDelete();
        Session::flash('iconMessage', 'success');
        return redirect(route('product.index'))->with('message', 'Xóa tất cả sản phẩm thành công!');
    }
}