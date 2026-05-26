<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use App\Models\CateNewsModel as CateNews;
use App\Models\NewsModel as News;
use App\Http\Requests\Backend\CateNewsRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Backend\CateNewsUpdateRequest;



class BlogCateController extends Controller
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
        $orderBy = $request->input('sort-by', 'cate_news_id'); 
        $orderType = $request->input('sort-type', 'asc');

        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['cate_news.cate_news_name'];

        $cateNews = $this->performSearch(CateNews::orderBy($orderBy, $orderType), $keyword, $searchableFields)->paginate(5)->withQueryString();
        return view('backend.pages.blog.blog_cate.blog_cate_list', compact('cateNews','orderBy', 'orderType'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.pages.blog.blog_cate.blog_cate_create');
    }

    public function status(Request $request, $id)
    {
        $arr = $request->post();
        $status = ($request->has('cate-new-status'))? $arr['cate-new-status']:"";
        $data = CateNews::find($id);

        if (!$data) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu'], 404);
        }

        $data->cate_news_hidden = $status;
        $data->save();

        return response()->json(['message' => 'Cập nhật thành công']);  
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(CateNewsRequest $request)
    {
        $arr = $request->post();
        $name = ($request->has('name'))? $arr['name']:"";
        $slug = ($request->has('slug'))? $arr['slug']:"";
        $hidden = ($request->has('hidden'))? (int)$arr['hidden']:"0";
        $sort = ($request->has('sort'))? $arr['sort']: "";
        $cates = new CateNews;
        $cates->cate_news_name = $name;
        $cates->cate_news_slug = $slug;
        $cates->cate_news_hidden = $hidden;
        $cates->cate_news_sort = $sort;
        
        if($request->hasFile('img_blog'))
        {
            $file = $request->file('img_blog');
            $extension = $file->getClientOriginalExtension();
            $file_name = time().'.'.$extension;
            $file->move(public_path('/backend/uploads/blog/'), $file_name);
            $cates->cate_news_img = '/backend/uploads/blog/'.$file_name;
           
        } 
        $cates->save();
        Session::flash('iconMessage', 'success');
        return redirect(route('blog-category.index'))->with('message', 'Thêm mới thành công!');
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
    public function edit(Request $request, string $cate_news_id)
    {
        $cateb = CateNews::find($cate_news_id);
        if ($cateb==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/blog-category')->with('message', 'Không tồn tại thông tin danh mục bài viết');
        }
        return view("backend.pages.blog.blog_cate.blog_cate_edit", compact('cateb'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CateNewsUpdateRequest $request, string $id)
    {
        $arr = $request->post();
        $cateb = CateNews::find($id);
        $name = ($request->has('name'))? $arr['name']:"";
        $slug = ($request->has('slug'))? $arr['slug']:"";
        $hidden = ($request->has('status'))? (int)$arr['status']:"0";
        $sort = ($request->has('sort'))? $arr['sort']: "";

        if ($cateb ==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect(route('blog-category.index'))->with('message', 'Không tồn tại danh mục');;
        }
        $cateb->cate_news_name = $name;
        $cateb->cate_news_slug = $slug;
        $cateb->cate_news_hidden = $hidden;
        $cateb->cate_news_sort = $sort;
        if($request->hasFile('img_blog'))
        {
            $file = $request->file('img_blog');
            $extension = $file->getClientOriginalExtension();
            $file_name = time().'.'.$extension;
            $file->move(public_path('/backend/uploads/blog/'), $file_name);
            $cateb->cate_news_img = '/backend/uploads/blog/'.$file_name;
        } 
        $cateb->save();
        Session::flash('iconMessage', 'success');
        return redirect(route('blog-category.index'))->with('message', 'Chỉnh sửa thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(Request $request, string $id){
        $catenews = CateNews::find($id);
        if($catenews==null){
            $request->session();
            Session::flash('iconMessage', 'info');
            redirect()->back()->with('message', 'Không tồn tại danh mục!');
        }

        if (count($catenews->getNewsInCate) != 0){
            Session::flash('iconMessage', 'info');
            return redirect()->back()->with('message', 'Không thể xóa danh mục đang có bài viết!');
        }
        $catenews->delete();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Xóa thành công!');
    }
    
    public function trashed(Request $request)
    {
        $orderBy = $request->input('sort-by', 'deleted_at'); 
        $orderType = $request->input('sort-type', 'asc');

        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['cate_news.cate_news_name'];

        $cateBTrash = $this->performSearch(CateNews::onlyTrashed($orderBy, $orderType), $keyword, $searchableFields)->paginate(5)->withQueryString();
        return view("backend.pages.blog.blog_cate.blog_cate_trash", compact('cateBTrash', 'orderBy', 'orderType'));
    }

    public function restore($id){
        $cateBl = CateNews::withTrashed()->where('cate_news_id', $id)->first();
        if ($cateBl) {
            $cateBl->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Hoàn tác thành công!');
        } else {
            return abort(404);
        }
    }

    public function restoreAll() {
        CateNews::onlyTrashed()->restore();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Hoàn tác thành công!');
    }
    
    public function forceDelete($id){ // xóa vĩnh viễn
        $cateBl = CateNews::withTrashed()->find($id); // Lấy bản ghi đã xóa mềm
        if ($cateBl) {
            $cateBl->forceDelete();
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa thành công!');
        } else {
            return abort(404); 
        }
    }

    public function deleteAll()
    {
        CateNews::onlyTrashed()->forceDelete();
        Session::flash('iconMessage', 'success');
        return redirect()->back()->with('message', 'Xóa tất cả thành công!');
    }
}
