<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Backend\ProductCateRequest;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use App\Models\CategoryModel as Category;
use App\Models\ProductModel as Product;

class ProductCateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __construct(Request $request)
    {
        $keyword = $request->input('keyword');
        View::share(compact('keyword'));
    }

    public function index(Request $request)
    {
        $orderBy = $request->input('orderBy', 'updated_at');
        $orderType = $request->input('orderType', 'asc');

        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['category.cate_name'];
        $allCates = $this->performSearch(Category::orderBy($orderBy, $orderType), $keyword, $searchableFields)
                                -> paginate(10)
                                -> withQueryString();
        $cate = $this->cate_tree($allCates);
        return view('backend.pages.product.product-cate.product_cate_list', compact('allCates', 'cate', 'orderBy', 'orderType'));
    }

    /**
     * Level of category.
     */
    public function cate_tree($data, $parent_id = 0, $level = 0){
        $result = [];
        foreach($data as $item){
            if($item['cate_parent_id'] == $parent_id){
                $item['level'] = $level;
                $result[] = $item;
                $child = $this->cate_tree($data, $item['cate_id'], $level+1);
                $result = array_merge($result, $child);
            }
        }
        return $result;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $allCates = Category::all();
        $cates = $this->cate_tree($allCates);
        return view('backend.pages.product.product-cate.product_cate_create', compact('allCates', 'cates'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductCateRequest $request)
    {
        $input = $request->post();
        $cate_name = ($request->has('cate_name'))? ucwords($input['cate_name']):"";
        $cate_slug = ($request->has('cate_slug'))? $input['cate_slug']:"";
        $cate_sort = ($request->has('cate_sort'))? $input['cate_sort']:"";
        $cate_meta_keywords = ($request->has('cate_meta_keywords'))? $input['cate_meta_keywords']:"";
        $cate_parent_id = ($request->has('cate_parent_id'))? $input['cate_parent_id']:"";
        $cate_hidden = ($request->has('cate_hidden'))? (int)$input['cate_hidden']:0;
        $cate = new Category;
        $cate->cate_name = $cate_name;
        $cate->cate_slug = $cate_slug;
        $cate->cate_sort = $cate_sort;
        $cate->cate_meta_keywords = $cate_meta_keywords;
        $cate->cate_parent_id = $cate_parent_id;
        $cate->cate_hidden = $cate_hidden;
        if($request->has('cate_img'))
        {
            $file = $request->file('cate_img');
            $file_name = time().'-'.$file->getClientOriginalName();
            $file->move(public_path('backend/uploads/product/category/'), $file_name);
            $cate->cate_img = 'backend/uploads/product/category/'.$file_name;
        }
        $cate->save();
        Session::flash('iconMessage', 'success');
        return redirect(route('product-category.index'))->with('message', 'Thêm danh mục thành công!');
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
    public function update(Request $request, string $cateId)
    {
        $input = $request->post();
        $cate = Category::find($cateId);
        $cate_name = ($request->has('cate_name'))? ucwords($input['cate_name']):"";
        $cate_slug = ($request->has('cate_slug'))? $input['cate_slug']:"";
        $cate_sort = ($request->has('cate_sort'))? $input['cate_sort']:"";
        $cate_meta_keywords = ($request->has('cate_meta_keywords'))? $input['cate_meta_keywords']:"";
        $cate_parent_id = ($request->has('cate_parent_id'))? $input['cate_parent_id']:"";
        $rules = (new ProductCateRequest)->rules();
        $messages = (new ProductCateRequest)->messages();
        if (($cate->cate_name != $cate_name) && ($cate->cate_sort != $cate_sort)){
            $validation = Validator::make($input, $rules, $messages);
            if ($validation->fails()) {
                $request->session();
                Session::flash('iconMessage', 'error');
                return back()->with('message', 'Cập nhật danh mục thất bại!');
            }
        } elseif ($cate->cate_name != $cate_name) {
            $rule = [
                'cate_name' => 'required|unique:category',
            ];
            $validation = Validator::make($input, $rule, $messages);
            $errors = $validation->errors();
            if ($validation->fails()) {
                $request->session();
                Session::flash('iconMessage', 'error');
                return back()->with('message', $errors->first());
            }
        } elseif ($cate->cate_sort != $cate_sort) {
            $rule = [
                'cate_sort' => 'required|min:0|max:100000000|numeric|integer|unique:category',
            ];
            $validation = Validator::make($input, $rule, $messages);
            $errors = $validation->errors();
            if ($validation->fails()) {
                $request->session();
                Session::flash('iconMessage', 'error');
                return back()->with('message', $errors->first());
            }
        }
        
        $cate->cate_name = $cate_name;
        $cate->cate_slug = $cate_slug;
        $cate->cate_sort = $cate_sort;
        $cate->cate_meta_keywords = $cate_meta_keywords;
        $cate->cate_parent_id = $cate_parent_id;
        if($request->has('cate_img'))
        {
            $rule = [
                'cate_img' => 'image',
            ];
            $validation = Validator::make($request->all(), $rule, $messages);
            $errors = $validation->errors();
            if ($validation->fails()) {
                $request->session();
                Session::flash('iconMessage', 'error');
                return back()->with('message', $errors->first());
            } else {
                $file = $request->file('cate_img');
                $file_name = time().'-'.$file->getClientOriginalName();
                $file->move(public_path('backend/uploads/product/category/'), $file_name);
                $cate->cate_img = 'backend/uploads/product/category/'.$file_name;
            }
        }
        $cate->save();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Cập nhật danh mục thành công!');
    }

    /**
     * Update status of the specified from storage.
     */
    public function updateStatus(Request $request, string $cateId) {
        $input = $request->post();
        $cate_hidden = ($request->has('cate_hidden'))? $input['cate_hidden']:"";
        $cate = Category::find($cateId);

        if (!$cate) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu'], abort(404));
        }

        $cate->cate_hidden = $cate_hidden;
        $cate->save();
        $childCate = Category::where('cate_parent_id', $cateId)
                                -> update(['cate_hidden' => $cate_hidden]);
                                
        return response()->json(['message' => 'Cập nhật thành công']); 
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $cateId)
    {
        $cate = Category::find($cateId);
        if ($cate == null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            redirect()->back()->with('message', 'Không tồn tại danh mục.');
        }

        if (count($cate->getChildCate) != 0) {
            Session::flash('iconMessage', 'error');
            return redirect()->back()->with('message', 'Không thể xóa danh mục gốc!');
        } else if (count($cate->getProductsInCate) != 0) {
            Session::flash('iconMessage', 'error');
            return redirect()->back()->with('message', 'Không thể xóa danh mục đang có sản phẩm!');
        } else {
            $cate->delete();
            $childCate = Category::where('cate_parent_id', $cateId)
                                -> update(['cate_hidden' => 0]);
            Session::flash('iconMessage', 'success');
            return redirect(route('product-category.index'))->with('message', 'Xóa danh mục thành công!');
        }  
    }

    /**
     * Show trashed view.
     */
    public function trashed(Request $request) 
    {
        $orderBy = $request->input('orderBy', 'cate_id');
        $orderType = $request->input('orderType', 'asc');

        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['cate_name'];

        $cateTrash = $this->performSearch(Category::onlyTrashed($orderBy, $orderType), $keyword, $searchableFields)->paginate(20)->withQueryString();
        return view('backend.pages.product.product-cate.product_cate_trash', compact('cateTrash', 'orderBy', 'orderType'));
    }

    /**
     * Restore one category.
     */
    public function restore(string $cateId) 
    {
        $cate = Category::withTrashed()->where('cate_id', $cateId)->first();
        if ($cate) {
            $cate->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Khôi phục danh mục thành công!');
        } else {
            return abort(404);
        }
    }

    /**
     * Restore all categories.
     */
    public function restoreAll() 
    {
        Category::onlyTrashed()->restore();
        Session::flash('iconMessage', 'success');
        return redirect(route('product-category.index'))->with('message', 'Khôi phục tất cả danh mục thành công!');
    }

    /**
     * Permanently delete one category.
     */
    public function delete(string $cateId) 
    {
        $cate = Category::withTrashed()->find($cateId);
        if ($cate) {
            $cate->forceDelete();
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa danh mục thành công!');
        } else {
            return abort(404); 
        }
    }

    /**
     * Permanently delete all categories.
     */
    public function deleteAll() 
    {
        Category::onlyTrashed()->forceDelete();
        Session::flash('iconMessage', 'success');
        return redirect(route('product-category.index'))->with('message', 'Xóa tất cả danh mục thành công!');
    }
}
