<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FaqModel;
use App\Http\Requests\Backend\FaqRequest;
use App\Http\Requests\Backend\FaqUpRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
class FaqAdminController extends Controller
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
        $orderBy = $request->input('sort-by', 'faq_id'); 
        $orderType = $request->input('sort-type', 'asc');


        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['faq_name'];
        
        $faq = $this->performSearch(FaqModel::orderBy($orderBy, $orderType)->where('faq_about',0), $keyword, $searchableFields)
        ->paginate($perpage)
        ->withQueryString();
    
        return view('backend.pages.faq.faq_list', compact('faq', 'keyword','orderBy', 'orderType'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.pages.faq.faq_create');
    }

    public function status(Request $request, $id)
    {
        $arr = $request->post();
        $status = ($request->has('faq-status'))? $arr['faq-status']:"";
        $data = FaqModel::find($id);

        if (!$data) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu'], 404);
        }

        $data->faq_hidden = $status;
        $data->save();

        return response()->json(['message' => 'Cập nhật thành công']);
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FaqRequest $request)
    {
        $arr = $request->post();
        $name = ($request->has('name'))? $arr['name']:"";
        $author = ($request->has('author'))? $arr['author']: "";
        $slug = ($request->has('slug'))? $arr['slug']:"";
        if (!empty($slug) && substr($slug, -5) !== ".html") {
            $slug .= ".html";
        }
        $key = ($request->has('key'))? $arr['key']:"";
        $faq_des = ($request->has('faq-des'))? $arr['faq-des']:"";
        $content = ($request->has('content'))? $arr['content']:"";
        $des = ($request->has('des'))? $arr['des']:"";
        $status = ($request->has('status'))? (int)$arr['status']:"0";
        $faq = new FaqModel;
        $faq->faq_name = $name;
        $faq->faq_slug = $slug;
        $faq->faq_description = $faq_des;
        $faq->faq_content = $content;
        $faq->faq_meta_keywords = $key;
        $faq->faq_meta_description = $des;
        $faq->faq_created_by = $author;
        $faq->faq_hidden = $status;
        $faq->save();
        Session::flash('iconMessage', 'success');
        return redirect('admin/faq')->with('message', 'Thêm thành công');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request,string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */

    public function edit(Request $request,string $encryptedFaqId)
    {
        try{
            $faq_id = Crypt::decrypt($encryptedFaqId);
            $faq = FaqModel::find($faq_id);
            if ($faq==null) {
                $request->session();
                Session::flash('iconMessage', 'info');
                return redirect('admin/faq')->with('message', 'Không tồn tại chính sách');
            }
            return view("backend.pages.faq.faq_edit", compact('faq'));
        } catch (DecryptException $e) {
            // Xử lý khi giải mã thất bại
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/faq')->with('message', 'Không tồn tại nội dung!');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FaqUpRequest $request, string $faq_id)
    {
        $arr = $request->post();
        $name = ($request->has('name'))? $arr['name']:"";
        $editor = ($request->has('editor'))? $arr['editor']: "";
        $slug = ($request->has('slug'))? $arr['slug']:"";
        if (!empty($slug) && substr($slug, -5) !== ".html") {
            $slug .= ".html";
        }
        $key = ($request->has('key'))? $arr['key']:"";
        $faq_des = ($request->has('faq-des'))? $arr['faq-des']:"";
        $content = ($request->has('content'))? $arr['content']:"";
        $des = ($request->has('des'))? $arr['des']:"";
        $status = ($request->has('status'))? (int)$arr['status']:"0";
        $faq = FaqModel::find($faq_id);
        if ($faq ==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('/admin/faq')->with('message', 'Không tồn tại chính sách');;
        }
        $faq->faq_name = $name;
        $faq->faq_slug = $slug;
        $faq->faq_description = $faq_des;
        $faq->faq_content = $content;
        $faq->faq_meta_keywords = $key;
        $faq->faq_meta_description = $des;
        $faq->faq_updated_by = $editor;
        $faq->faq_hidden = $status;
        $faq->save();
        Session::flash('iconMessage', 'success');
        return redirect('/admin/faq')->with('message', 'Chỉnh sửa thành công');
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(string $encryptedFaqId, Request $request)
    // {
    //     try {
    //         $faq_id = Crypt::decrypt($encryptedFaqId);
    //         $faq = FaqModel::find($faq_id);
    //         if ($faq==null) {
    //             $request->session();
    //             Session::flash('iconMessage', 'info');
    //             redirect()->back()->with('message', 'Không tồn tại thông tin liên hệ');
    //         }
    //         $faq->delete();
    //         Session::flash('iconMessage', 'success');
    //         return redirect('/admin/faq')->with('message', 'Xóa thành công');
    //     } catch (DecryptException $e) {
    //         $request->session();
    //         Session::flash('iconMessage', 'info');
    //         return redirect('admin/faq')->with('message', 'Không tồn tại nội dung!');
    //     }
    // }
    
    public function softDelete(Request $request,string $encryptedFaqId){
        try {
            $id = Crypt::decrypt($encryptedFaqId);
            $faq = FaqModel::find($id);
            if ($faq==null) {
                $request->session();
                Session::flash('iconMessage', 'info');
                redirect()->back()->with('message', 'Không tồn tại mã khuyến mãi');
            }
            $faq->delete();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Xóa thành công');
        } catch (DecryptException $e) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/faq')->with('message', 'Không tồn tại nội dung!');
        }
    }

    public function trashed(Request $request){
        $perpages = 10;
        $orderBy = $request->input('sort-by', 'faq_id'); 
        $orderType = $request->input('sort-type', 'asc');


        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['faq_name'];
        
        $faqTrash = $this->performSearch(FaqModel::onlyTrashed()->orderBy($orderBy, $orderType), $keyword, $searchableFields)
        ->paginate($perpages)
        ->withQueryString();
    
        return view('backend.pages.faq.faq_trash', compact('faqTrash', 'keyword','orderBy', 'orderType'));
    }

    public function restore(Request $request,string $encryptedFaqId){
        try {
            $faq_id = Crypt::decrypt($encryptedFaqId);
            $faqRe = FaqModel::withTrashed()->where('faq_id', $faq_id)->first();
            if ($faqRe) {
                $faqRe->restore();
                Session::flash('iconMessage', 'success');
                return back()->with('message', 'Khôi phục thành công');
            } else {
                return abort(404);
            }
        } catch (DecryptException $e) {
            // Xử lý khi giải mã thất bại
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/faq')->with('message', 'Không tồn tại nội dung!');
        }
    }

    public function restoreAll() {
        $trashed = FaqModel::onlyTrashed();
        if ($trashed->count() > 0) {
            $trashed->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Hoàn tác thành công');
        } else {
            Session::flash('iconMessage', 'info');
            return back()->with('message', 'Không có dữ liệu trong thùng rác!');
        }
    }
    
    public function forceDelete(Request $request,string $encryptedFaqId){
        try {
            $id = Crypt::decrypt($encryptedFaqId);
            $faqDe = FaqModel::withTrashed()->find($id);
            if ($faqDe) {
                $faqDe->forceDelete();
                Session::flash('iconMessage', 'success');
                return redirect()->back()->with('message', 'Xóa thành công');
            } else {
                return abort(404); 
            }
        } catch (DecryptException $e) {
            // Xử lý khi giải mã thất bại
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/faq')->with('message', 'Không tồn tại nội dung!');
        }
    }
    public function deleteAll() 
    {
        $trashAll = FaqModel::onlyTrashed()->get();
        if ($trashAll->count() > 0) {
            FaqModel::onlyTrashed()->forceDelete();
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa dữ liệu thành công!');
        } else {
            Session::flash('iconMessage', 'info');
            return back()->with('message', 'Không có dữ liệu trong thùng rác!');
        }
    }
}
