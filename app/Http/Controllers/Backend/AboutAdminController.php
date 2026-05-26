<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Models\FaqModel;
use Illuminate\Support\Facades\Session;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use App\Http\Requests\Backend\FaqUpRequest;
class AboutAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __construct(Request $request)
    {
        $keyword = $request->input('keyword');
        View::share(compact('keyword'));
    }

    public function index()
    {
        $about = FaqModel::orderBy('faq_id','desc')->where('faq_about',1)->paginate(5);
        return view('backend.pages.about.about_list', compact('about'));
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
    public function store(Request $request)
    {
        //
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
    public function edit(Request $request,string $encryptedAboutId)
    {
        try{
            $faq_id = Crypt::decrypt($encryptedAboutId);
            $about = FaqModel::find($faq_id);
            if ($about==null) {
                $request->session();
                Session::flash('iconMessage', 'info');
                return redirect('admin/about')->with('message', 'Không tồn tại nội dung');
            }
            return view("backend.pages.about.about_edit", compact('about'));
        } catch (DecryptException $e) {
            // Xử lý khi giải mã thất bại
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/about')->with('message', 'Không tồn tại nội dung!');
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
        $content = ($request->has('content'))? $arr['content']:"";
        $des = ($request->has('des'))? $arr['des']:"";
        $status = ($request->has('status'))? (int)$arr['status']:"1";
        $faq = FaqModel::find($faq_id);
        if ($faq ==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('/admin/about')->with('message', 'Không tồn tại chính sách');;
        }
        $faq->faq_name = $name;
        $faq->faq_slug = $slug;
        $faq->faq_content = $content;
        $faq->faq_meta_keywords = $key;
        $faq->faq_meta_description = $des;
        $faq->faq_updated_by = $editor;
        $faq->faq_hidden = $status;
        $faq->save();
        Session::flash('iconMessage', 'success');
        return redirect('/admin/about')->with('message', 'Chỉnh sửa thành công');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
