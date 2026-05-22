<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PromotionModel;
use App\Http\Requests\Backend\PromotionRequest;
use App\Models\CateSlideModel;
use App\Http\Requests\Backend\PromotionUpRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Carbon;
class PromotionAdminController extends Controller
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
        $perpage = 10;
        $today = Carbon::today()->toDateString();
        $orderBy = $request->input('sort-by', 'promotion_id'); 
        $orderType = $request->input('sort-type', 'asc');


        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['promotion_name'];
        
        $promotion = $this->performSearch(PromotionModel::orderBy($orderBy, $orderType), $keyword, $searchableFields)
        ->paginate($perpage)
        ->withQueryString();

        return view('backend.pages.slide.promotion.promotion_list', compact('promotion','orderBy', 'orderType','today'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function cateSlide($id){
        $cate = CateSlideModel::find($id);
        if($cate){
            return $cate->cate_slide_name;
        }
        return false;
    }

    public function create()
    {
        return view('backend.pages.slide.promotion.promotion_create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PromotionRequest $request)
    {
        $arr = $request->post();
        $name = ($request->has('name'))? $arr['name']:"";
        $type = ($request->has('type'))? $arr['type']:"";
        $url = ($request->has('url'))? $arr['url']: "";
        $position = ($request->has('position'))? $arr['position']: "";
        $start_pro = ($request->has('start_pro'))? $arr['start_pro']:"";
        $end_pro = ($request->has('end_pro'))? $arr['end_pro']:"";
        $contents = ($request->has('contents'))? $arr['contents']:"";
        $note = ($request->has('note'))? $arr['note']:"";
        $status = ($request->has('status'))? (int)$arr['status']:"0";
        $idCate = ($request->has('cate'))? $arr['cate'] : " ";
        $pro = new PromotionModel;
        $pro->promotion_name = $name;
        $pro->type = $type;
        $pro->promotion_link = $url;
        $time_start = date('Y/m/d', strtotime($start_pro));
        $time_end = date('Y/m/d', strtotime($end_pro));
        $pro->promotion_start = $time_start;
        $pro->promotion_end = $time_end;
        $pro->promotion_content = $contents;
        $pro->promotion_note = $note;
        $pro->promotion_sort = $position;
        $pro->promotion_hidden = $status;
        $pro->cate_slide_id = $idCate;
        if($request->has('img'))
        {
            $file = $request->file('img');
            $extension = $file->getClientOriginalExtension();
            $file_name = time().'.'.$extension;
            $file->move(public_path('backend/uploads/slide/'), $file_name);
            $pro->promotion_img = 'backend/uploads/slide/'.$file_name;
            // dd($file);
        } 
        $pro->save();
        Session::flash('iconMessage', 'success');
        return redirect('/admin/promotion')->with('message', 'Thêm thành công');
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
    public function edit(Request $request,string $promotion_id)
    {
        $pro = PromotionModel::find($promotion_id);
        if ($pro==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/promotion')->with('message', 'Không tồn tại nội dung');
        }
        return view("backend.pages.slide.promotion.promotion_edit", compact('pro'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PromotionUpRequest $request, string $promotion_id)
    {
        $arr = $request->post();
        $name = ($request->has('name'))? $arr['name']:"";
        $type = ($request->has('type'))? $arr['type']:"";
        $url = ($request->has('url'))? $arr['url']: "";
        $position = ($request->has('position'))? $arr['position']: "";
        $start_pro = ($request->has('start_pro'))? $arr['start_pro']:"";
        $end_pro = ($request->has('end_pro'))? $arr['end_pro']:"";
        $contents = ($request->has('contents'))? $arr['contents']:"";
        $note = ($request->has('note'))? $arr['note']:"";
        $status = ($request->has('status'))? (int)$arr['status']:"0";
        $idCate = ($request->has('cate'))? $arr['cate'] : " ";
        $pro = PromotionModel::find($promotion_id);
        if ($pro ==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('/admin/promotion')->with('message', 'Không tồn tại nội dung');;
        }

        $pro->promotion_name = $name;
        $pro->type = $type;
        $pro->promotion_link = $url;
        $time_start = date('Y/m/d', strtotime($start_pro));
        $time_end = date('Y/m/d', strtotime($end_pro));
        $pro->promotion_start = $time_start;
        $pro->promotion_end = $time_end;
        $pro->promotion_content = $contents;
        $pro->promotion_note = $note;
        $pro->promotion_sort = $position;
        $pro->promotion_hidden = $status;
        $pro->cate_slide_id = $idCate;
        if($request->has('img'))
        {
            $file = $request->file('img');
            $extension = $file->getClientOriginalExtension();
            $file_name = time().'.'.$extension;
            $file->move(public_path('backend/uploads/slide/'), $file_name);
            $pro->promotion_img = 'backend/uploads/slide/'.$file_name;
        } 
        $pro->save();
        Session::flash('iconMessage', 'success');
        return redirect('/admin/promotion')->with('message', 'Chỉnh sửa thành công');
    }
    public function status(Request $request, $id)
    {
        $arr = $request->post();
        $pstatus = ($request->has('p-status'))? $arr['p-status']:"";
        $data = PromotionModel::find($id);

        if (!$data) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu'], 404);
        }

        $data->promotion_hidden = $pstatus;
        $data->save();

        return response()->json(['message' => 'Cập nhật thành công']);
        
    }
    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(Request $request, string $id)
    // {
    //     $promotion = PromotionModel::find($id); 
    //     if ($promotion==null) {
    //         $request->session();
    //         redirect()->back()->with('message', 'Không tồn tại quảng cáo!');
    //     }
    //     $promotion->delete();
    //     Session::flash('iconMessage', 'success');
    //     return redirect('/admin/promotion')->with('message', 'Xóa thành công!');
    // }

    public function softDelete(Request $request, string $id){
        $promotion = PromotionModel::find($id); 
        if ($promotion==null) {
            $request->session();
            redirect()->back()->with('message', 'Không tồn tại quảng cáo!');
        }
        $promotion->delete();
        Session::flash('iconMessage', 'success');
        return redirect('/admin/promotion')->with('message', 'Xóa thành công!');
    }

    public function trashed(Request $request){
        $perpage = 10;
        $orderBy = $request->input('sort-by', 'promotion_id'); 
        $orderType = $request->input('sort-type', 'asc');


        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['promotion_name'];
        
        $promotionTrash = $this->performSearch(PromotionModel::onlyTrashed()->orderBy($orderBy, $orderType), $keyword, $searchableFields)
        ->paginate($perpage)
        ->withQueryString();

        return view('backend.pages.slide.promotion.promotion_trash', compact('promotionTrash','orderBy', 'orderType'));
    }

    public function restore($id){
        $promotionRe = PromotionModel::withTrashed()->where('promotion_id', $id)->first();
        if ($promotionRe) {
            $promotionRe->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Hoàn tác thành công');
        } else {
            return abort(404);
        }
    }

    public function restoreAll() {
        $trashed=PromotionModel::onlyTrashed();
        if ($trashed->count() > 0) {
            $trashed->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Hoàn tác thành công');
        } else {
            Session::flash('iconMessage', 'info');
            return back()->with('message', 'Không có dữ liệu trong thùng rác!');
        }
    }
    
    public function forceDelete($id){
        $promotionDe = PromotionModel::withTrashed()->find($id); // Lấy bản ghi đã xóa mềm
        if ($promotionDe) {
            $promotionDe->forceDelete(); // Xóa vĩnh viễn
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa thành công');
        } else {
            return abort(404); 
        }
    }
    public function deleteAll() 
    {
        $trashAll = PromotionModel::onlyTrashed()->get();
        if ($trashAll->count() > 0) {
            PromotionModel::onlyTrashed()->forceDelete();
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa hình ảnh thành công!');
        } else {
            Session::flash('iconMessage', 'info');
            return back()->with('message', 'Không có dữ liệu trong thùng rác!');
        }
    }
}
