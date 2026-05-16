<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Requests\Backend\ImageRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use App\Models\ProductModel as Product;
use App\Models\ImageModel as Image;

class ImageController extends Controller
{
    public function __construct(Request $request)
    {
        $keyword = $request->input('keyword');
        View::share(compact('keyword'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(string $proId)
    {
        
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
    public function store(ImageRequest $request)
    {   
        $input = $request->post();
        if($request->has('img_name'))
        {
            foreach($request->file('img_name') as $img) {
                $file = $img;
                $file_name = time().'-'.$file->getClientOriginalName();
                $file->move(public_path('backend/uploads/product/gallery/'), $file_name);
                $image = new Image;
                $image->img_name = 'backend/uploads/product/gallery/'.$file_name;
                $image->pro_id = $input['pro_id'];
                $image->img_hidden = $input['img_hidden'] ? $input['img_hidden']:0;
                $image->save();
            }
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Thêm hình ảnh thành công!');
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

        $allImages = Image::where('pro_id', $proId)
                                -> orderBy('updated_at', 'desc')
                                -> paginate(10);
        return view('backend.pages.product.image.product_img_list', compact('allImages', 'proId'));
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
    public function update(Request $request, string $imgId) {
        $input = $request->post();
        $img_hidden = ($request->has('img_hidden'))? $input['img_hidden']:"";
        $image = Image::find($imgId);

        if (!$image) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu'], abort(404));
        }

        $image->img_hidden = $img_hidden;
        $image->save();

        return response()->json(['message' => 'Cập nhật thành công']); 
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $imgId)
    {
        $image = Image::find($imgId);
        if ($image == null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            redirect()->back()->with('message', 'Không tồn tại hình ảnh.');
        }
        $image->delete();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Xóa hình ảnh thành công!');
    }

    /**
     * Show trashed view.
     */
    public function trashed(string $proSlug = '') 
    {
        $proId = Product::where('pro_slug', $proSlug)->value('pro_id');

        if($proId == null) {
            Session::flash('iconMessage', 'info');
            return redirect()->route('product.index')->with('message', 'Sản phẩm không tồn tại');
        }

        $imgTrash = Image::onlyTrashed()
                            -> where('pro_id', $proId)
                            -> paginate(20);

        return view('backend.pages.product.image.product_img_trash', compact('imgTrash', 'proSlug'));
    }

    /**
     * Restore one category.
     */
    public function restore(string $imgId) 
    {
        $image = Image::withTrashed()->where('img_id', $imgId)->first();
        if ($image) {
            $image->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Khôi phục hình ảnh thành công!');
        } else {
            return abort(404);
        }
    }

    /**
     * Restore all categories.
     */
    public function restoreAll(string $proSlug = '') 
    {
        $proId = Product::where('pro_slug', $proSlug)->value('pro_id');

        if($proId == null) {
            Session::flash('iconMessage', 'info');
            return redirect()->route('product.index')->with('message', 'Sản phẩm không tồn tại');
        }

        Image::onlyTrashed()
                -> where('pro_id', $proId)
                -> restore();
        Session::flash('iconMessage', 'success');
        return redirect(route('image.show', $proSlug))->with('message', 'Khôi phục tất cả hình ảnh thành công!');
    }

    /**
     * Permanently delete one category.
     */
    public function delete(string $imgId) 
    {
        $image = Image::withTrashed()->find($imgId);
        if ($image) {
            $image->forceDelete();
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa hình ảnh thành công!');
        } else {
            return abort(404); 
        }
    }

    /**
     * Permanently delete all categories.
     */
    public function deleteAll(string $proSlug = '') 
    {
        $proId = Product::where('pro_slug', $proSlug)->value('pro_id');

        if($proId == null) {
            Session::flash('iconMessage', 'info');
            return redirect()->route('product.index')->with('message', 'Sản phẩm không tồn tại');
        }

        Image::onlyTrashed()
                -> where('pro_id', $proId)
                -> forceDelete();
        Session::flash('iconMessage', 'success');
        return redirect(route('image.show', $proSlug))->with('message', 'Xóa tất cả hình ảnh thành công!');
    }
}
