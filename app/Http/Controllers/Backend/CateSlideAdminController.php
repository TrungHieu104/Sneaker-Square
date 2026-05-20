<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\CateSlideModel;
use App\Http\Requests\Backend\CateSlideRequest;
use App\Http\Requests\Backend\CateSlideUpdate;
use App\Models\PromotionModel;
use Illuminate\Support\Facades\View;

class CateSlideAdminController extends Controller
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
        $perpage = 8;
        $orderBy = $request->input('sort-by', 'cate_slide_id'); 
        $orderType = $request->input('sort-type', 'asc');


        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['cate_slide_name'];
        
        $cateSlide = $this->performSearch(CateSlideModel::orderBy($orderBy, $orderType), $keyword, $searchableFields)
        ->paginate($perpage)
        ->withQueryString();

        return view('backend.pages.slide.slide_cate.slide_cate_list', compact('cateSlide','orderBy', 'orderType'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.pages.slide.slide_cate.slide_cate_create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CateSlideRequest $request)
    {
        $arr = $request->post();
        $name = ($request->has('name'))? $arr['name']:"";
        $slug = ($request->has('slug'))? $arr['slug']:"";
        $status = ($request->has('status'))? (int)$arr['status']:"0";
        $cates = new CateSlideModel;
        $cates->cate_slide_name = $name;
        $cates->cate_slide_slug = $slug;
        $cates->cate_slide_hidden = $status;
        $cates->save();
        Session::flash('iconMessage', 'success');
        return redirect(route('cate-slide.index'))->with('message', 'Thêm mới thành công!');
    }

    public function status(Request $request, $id)
    {
        $arr = $request->post();
        $status = ($request->has('status'))? $arr['status']:"";
        $data = CateSlideModel::find($id);

        if (!$data) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu'], 404);
        }

        $data->cate_slide_hidden = $status;
        $data->save();

        return response()->json(['message' => 'Cập nhật thành công']);
        
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
    public function edit(Request $request, string $cate_slide_id)
    {
        $cates = CateSlideModel::find($cate_slide_id);
        if ($cates==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/cate-slide')->with('message', 'Không tồn tại thông tin liên hệ');
        }
        return view("backend.pages.slide.slide_cate.slide_cate_edit", compact('cates'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CateSlideUpdate $request, string $id)
    {
        $arr = $request->post();
        $name = ($request->has('name'))? $arr['name']:"";
        $slug = ($request->has('slug'))? $arr['slug']:"";
        $status = ($request->has('status'))? (int)$arr['status']:"0";
        $cates = CateSlideModel::find($id);
        if ($cates ==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect(route('coupon.index'))->with('message', 'Không tồn tại danh muc');;
        }
        $cates->cate_slide_name = $name;
        $cates->cate_slide_slug = $slug;
        $cates->cate_slide_hidden = $status;
        $cates->save();
        Session::flash('iconMessage', 'success');
        return redirect(route('cate-slide.index'))->with('message', 'Chỉnh sửa thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(Request $request,string $id)
    // {
    //     $promotion = PromotionModel::where('cate_slide_id', $id)->count();
    //     if($promotion>0){
    //         Session::flash('iconMessage', 'info');
    //         return redirect('admin/cate-slide')->with('message', 'Không thể xóa danh mục!');
    //     }
    //     $cateS = CateSlideModel::find($id);
    //     if ($cateS==null) {
    //         $request->session();
    //         Session::flash('iconMessage', 'info');
    //         redirect()->back()->with('message', 'Không tồn tại danh mục!');
    //     }
    //     $cateS->delete();
    //     Session::flash('iconMessage', 'success');
    //     return redirect('/admin/cate-slide')->with('message', 'Xóa thành công');
    // }


    public function softDelete(Request $request,string $id){
        $promotion = PromotionModel::where('cate_slide_id', $id)->count();
        if($promotion>0){
            Session::flash('iconMessage', 'info');
            return redirect('admin/cate-slide')->with('message', 'Không thể xóa danh mục!');
        }
        $cateS = CateSlideModel::find($id);
        if ($cateS==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            redirect()->back()->with('message', 'Không tồn tại danh mục!');
        }
        $cateS->delete();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Xóa thành công!');
    }
    
    public function trashed(Request $request){
        $perpages = 10;
        $orderBy = $request->input('sort-by', 'cate_slide_id'); 
        $orderType = $request->input('sort-type', 'asc');


        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['cate_slide_name'];
        
        $cateSlides = $this->performSearch(CateSlideModel::onlyTrashed()->orderBy($orderBy, $orderType), $keyword, $searchableFields)
        ->paginate($perpages)
        ->withQueryString();

        return view('backend.pages.slide.slide_cate.slide_cate_trash', compact('cateSlides','orderBy', 'orderType'));
    }

    public function restore($id){
        $cateSlide = CateSlideModel::withTrashed()->where('cate_slide_id', $id)->first();
        if ($cateSlide) {
            $cateSlide->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Hoàn tác thành công!');
        } else {
            return abort(404);
        }
    }

    public function restoreAll() {
        $trash=CateSlideModel::onlyTrashed();
        if ($trash->count() > 0) {
            $trash->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Hoàn tác thành công!');
        } else {
            Session::flash('iconMessage', 'info');
            return back()->with('message', 'Không có dữ liệu trong thùng rác!');
        }
    }
    
    public function forceDelete($id){
        $cateSlide = CateSlideModel::withTrashed()->find($id); 
        if ($cateSlide) {
            $cateSlide->forceDelete();
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa thành công!');
        } else {
            return abort(404); 
        }
    }
    public function deleteAll() 
    {
        $trashAll = CateSlideModel::onlyTrashed()->get();
        if ($trashAll->count() > 0) {
            CateSlideModel::onlyTrashed()->forceDelete();
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa danh mục thành công!');
        } else {
            Session::flash('iconMessage', 'info');
            return back()->with('message', 'Không có dữ liệu trong thùng rác!');
        }
    }
}
