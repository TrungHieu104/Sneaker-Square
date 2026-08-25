<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Requests\Backend\ProductQuantityRequest;
use App\Http\Requests\Backend\ColorRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use App\Models\ProductModel as Product;
use App\Models\ColorModel as Color;
use App\Models\SizeModel as Size;
use App\Models\ProductQuantityModel as Quantity;

class ProductQuantityController extends Controller
{
    public function __construct(Request $request)
    {
        $keyword = $request->input('keyword');
        View::share(compact('keyword'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $allProducts = Product::orderBy('pro_name','asc')->get();
        $allColor = Color::all();
        $allSize = Size::orderBy('size', 'asc')->get();
        return view('backend.pages.product.stock.product_stock_create', compact('allProducts', 'allColor', 'allSize'));
    }

    /**
     * Store new color
     */
    public function storeNewColor(ColorRequest $request) {
        $input = $request->post();
        $color = ($request->has('color'))? $input['color']:"";
        $color_vn = ($request->has('color_vn'))? mb_convert_case($input['color_vn'], MB_CASE_TITLE, "UTF-8"):"";

        $newColor = new Color;
        $newColor->color = $color;
        $newColor->color_vn = $color_vn;
        $newColor->save();

        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Thêm màu thành công!');
    }

    /**
     * Update color
     */
    public function updateColor(ColorRequest $request, string $colorId)
    {
        $color = Color::find($colorId);

        if (! $color) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', 'Không tìm thấy màu này!');
        }

        // ColorRequest has already checked both names and title-cased the
        // Vietnamese one, which is why the branching that used to live here is
        // gone.
        $color->color = (string) $request->input('color');
        $color->color_vn = (string) $request->input('color_vn');
        $color->save();

        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Cập nhật màu sắc thành công!');
    }

    /**
     * Delete color
     */
    public function deleteColor(Request $request, string $colorId)
    {
        $color = Color::find($colorId);
        if ($color == null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            redirect()->back()->with('message', 'Không tồn tại màu sắc.');
        }
        $color->delete();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Xóa màu sắc thành công!');
    }

    /**
     * Store a newly created resource in storage.
     *
     * ProductQuantityRequest holds the rules for both kinds of product now;
     * this method used to build them inline and run its own Validator.
     */
    public function store(ProductQuantityRequest $request)
    {
        $input = $request->all();
        $pro_id = ($request->has('pro_id'))? $input['pro_id']:"";
        $quantity_date = ($request->has('quantity_date'))? $input['quantity_date']:"";
        $pro_type = ($request->has('pro_type'))? (int)$input['pro_type']:0;

        $q = Quantity::where('pro_id', $pro_id)->first();
        if ($pro_type == 0) {
            if (is_null($q)) {
                if (isset($input['color_id']) && is_array($input['color_id']) && count($input['color_id']) > 0) {
                    $checkedColor = $request->input('color_id', []);
                    $quantities = $request->input('quantityColorAndSize', []);

                    foreach ($checkedColor as $value) {
                        if (array_key_exists($value, $quantities)) {
                            if (isset($input['size_id']) && is_array($input['size_id']) && count($input['size_id']) > 0) {
                                foreach ($input['size_id'] as $size) {
                                    $quan = new Quantity;
                                    $quan->pro_id = $pro_id;
                                    $quan->quantity_date = $quantity_date;
                                    $quan->size_id = $size;
                                    $quan->color_id = $value;
                                    $quan->quantity = $quantities[$value];
                                    $quan->save();
                                }
                            } else {
                                $quan = new Quantity;
                                $quan->pro_id = $pro_id;
                                $quan->quantity_date = $quantity_date;
                                $quan->color_id = $value;
                                $quan->quantity = $quantities[$value];
                                $quan->save();
                            }
                        }
                    }
                    Session::flash('iconMessage', 'success');
                    return redirect()->back()->with('message', 'Nhập hàng thành công');
                }
            } else {
                if (isset($input['color_id']) && is_array($input['color_id']) && count($input['color_id']) > 0) {
                    $checkedColor = $request->input('color_id', []);
                    $quantities = $request->input('quantityColorAndSize', []);
                    $checkedSize = $request->input('size_id', []);

                    foreach ($checkedColor as $value) {
                        if (isset($input['size_id']) && is_array($input['size_id']) && count($input['size_id']) > 0) {
                            foreach ($checkedSize as $size) {
                                $quanId = Quantity::where('pro_id', $pro_id)
                                                    -> where('color_id', $value)
                                                    -> where('size_id', $size)
                                                    -> value('quantity_id');
                                if (array_key_exists($value, $quantities)) {
                                    $quanSizeColor = Quantity::find($quanId);
                                    if (!is_null($quanSizeColor)) {
                                        $quanSizeColor = Quantity::where('pro_id', $pro_id)
                                                                -> where('color_id', $value)
                                                                -> where('size_id', $size)
                                                                -> first();
                                        $quanSizeColor->pro_id = $pro_id;
                                        $quanSizeColor->quantity_date = $quantity_date;
                                        $quanSizeColor->size_id = $size;
                                        $quanSizeColor->color_id = $value;
                                        $quanSizeColor->quantity += $quantities[$value];
                                        $quanSizeColor->save();
                                    } else {
                                        $quanSizeColor = new Quantity;
                                        $quanSizeColor->pro_id = $pro_id;
                                        $quanSizeColor->quantity_date = $quantity_date;
                                        $quanSizeColor->size_id = $size;
                                        $quanSizeColor->color_id = $value;
                                        $quanSizeColor->quantity = $quantities[$value];
                                        $quanSizeColor->save();
                                    }
                                }
                            }
                        } else {
                            $quanId = Quantity::where('pro_id', $pro_id)->where('color_id', $value)->value('quantity_id');
                            if (array_key_exists($value, $quantities)) {
                                $quanColor = Quantity::find($quanId);
                                if (!is_null($quanColor)) {
                                    $quanColor = Quantity::where('pro_id', $pro_id)->where('color_id', $value)->first();
                                    $quanColor->pro_id = $pro_id;
                                    $quanColor->quantity_date = $quantity_date;
                                    $quanColor->color_id = $value;
                                    $quanColor->quantity += $quantities[$value];
                                    $quanColor->save();
                                } else {
                                    $quanColor = new Quantity;
                                    $quanColor->pro_id = $pro_id;
                                    $quanColor->quantity_date = $quantity_date;
                                    $quanColor->color_id = $value;
                                    $quanColor->quantity = $quantities[$value];
                                    $quanColor->save();
                                }
                            }
                        }
                    }
                    Session::flash('iconMessage', 'success');
                    return back()->with('message', 'Nhập hàng thành công');
                }
            }
        }

        if ($pro_type == 1) {
            $quantity = ($request->has('quantityOthers'))? $input['quantityOthers']:"";

            if (is_null($q)) {
                $quan = new Quantity;
                $quan->pro_id = $pro_id;
                $quan->quantity_date = $quantity_date;
                $quan->quantity = $quantity;
                $quan->save();
                Session::flash('iconMessage', 'success');
                return back()->with('message', 'Nhập hàng thành công!');
            } else {
                $quan = Quantity::where('pro_id', $pro_id)->first();
                $quan->pro_id = $pro_id;
                $quan->quantity_date = $quantity_date;
                $quan->quantity += $quantity;
                $quan->save();
                Session::flash('iconMessage', 'success');
                return back()->with('message', 'Nhập hàng thành công!');
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $proSlug = '')
    {
        $proId = Product::where('pro_slug', $proSlug)->value('pro_id');

        if($proId == null) {
            Session::flash('iconMessage', 'info');
            return redirect()->route('product.index')->with('message', 'Sản phẩm không tồn tại');
        }

        // Each row prints the product, the size and the colour.
        $allQuantity = Quantity::with(['getProducts', 'getSize', 'getColor'])
                                ->where('pro_id', $proId)
                                -> orderBy('quantity_date', 'desc')
                                -> orderBy('color_id', 'asc')
                                -> orderBy('size_id', 'asc')
                                -> paginate(20);

        if (is_null($allQuantity[0])) {
            Session::flash('iconMessage', 'info');
            return redirect(route('stock.create'))->with('message', 'Chưa có hàng trong kho!');
        } else {
            return view('backend.pages.product.stock.product_stock', compact('allQuantity'));
        }
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $quantityId)
    {
        $quan = Quantity::find($quantityId);
        if ($quan == null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            back()->with('message', 'Không tồn tại sản phẩm này trong kho!');
        }
        $quan->delete();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Xóa sản phẩm trong kho thành công!');
    }
}    