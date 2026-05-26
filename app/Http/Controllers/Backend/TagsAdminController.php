<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Backend\TagsRequest;
use App\Models\TagsModel as Tags;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Backend\TagsUpdateRequest;

use App\Models\NewsModel as News;
// use Illuminate\Support\Facades\View;
class TagsAdminController extends Controller
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
        $orderBy = $request->input('sort-by', 'tag_id'); 
        $orderType = $request->input('sort-type', 'asc');

        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['tags.tag_content'];

        $tags = $this->performSearch(Tags::orderBy($orderBy, $orderType), $keyword, $searchableFields)->paginate(10)->withQueryString();
        return view('backend.pages.blog.tags.tag_list', compact('tags', 'orderBy', 'orderType'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.pages.blog.tags.tag_create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TagsRequest $request)
    {
        $arr = $request->post();
        $contentTg = ($request->has('contentTg'))? $arr['contentTg']:"";
        $slug = ($request->has('slug'))? $arr['slug']:"";
        $hidden = ($request->has('hidden'))? (int)$arr['hidden']:"0";
        $tags = new Tags();
        $tags->tag_content = $contentTg;
        $tags->tag_slug = $slug;
        $tags->tag_hidden = $hidden;
        $tags->save();
        Session::flash('iconMessage','success');
        return redirect(route('tags.index'))->with('message', 'Thêm mới thành công!');
    }

    public function status(Request $request, $id)
    {
        $arr = $request->post();
        $status = ($request->has('tag-status'))? $arr['tag-status']:"";
        $data = Tags::find($id);

        if (!$data) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu'], 404);
        }

        $data->tag_hidden = $status;
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
    public function edit(Request $request,string $tag_id)
    {
        $tags = Tags::find($tag_id);
        if($tags==null){
            $request->session();
            Session::flash('iconMessage','info');
            return redirect('admin/tags')->with('message','Không tồn tại thẻ tag này');
        }
        return view("backend.pages.blog.tags.tag_edit",compact('tags'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TagsUpdateRequest $request, string $id)
    {
        $arr = $request->post();
        $tags = Tags::find($id);
        $contentTg = ($request->has('contentTg'))? $arr['contentTg']:"";
        $slug = ($request->has('slug'))? $arr['slug']:"";
        $hidden = ($request->has('hidden'))? (int)$arr['hidden']:"0";

        if ($tags ==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect(route('tags.index'))->with('message', 'Không tồn tại thẻ tag này!');;
        }
        $tags->tag_content = $contentTg;
        $tags->tag_slug = $slug;
        $tags->tag_hidden = $hidden;
        $tags->save();
        Session::flash('iconMessage','success');
        return redirect(route('tags.index'))->with('message', 'Chỉnh sửa thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    
    public function destroy(Request $request, string $id)
    {
        $tags = Tags::find($id);
        if ($tags==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            redirect()->back()->with('message', 'Không tồn tại thẻ tag này!');
        }
        $tags->delete();
        Session::flash('iconMessage', 'success');
        return redirect('/admin/tags')->with('message', 'Xóa thành công');
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
        $searchableFields = ['tags.tag_content'];

        $tagTrash = $this->performSearch(Tags::onlyTrashed($orderBy, $orderType), $keyword, $searchableFields)->paginate(10)->withQueryString();
        return view("backend.pages.blog.tags.tag_trash", compact('tagTrash', 'orderBy', 'orderType'));
    }

    public function restore($id)
    {
        $tags = Tags::withTrashed()->where('tag_id', $id)->first();
        if ($tags) {
            $tags->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Hoàn tác thành công!');
        } else {
            return abort(404);
        }
    }

    public function restoreAll() 
    {
        Tags::onlyTrashed()->restore();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Hoàn tác thành công!');
    }
    
    public function forceDelete($id)
    {
        $tags = Tags::withTrashed()->find($id); 
        if ($tags) {
            $tags->forceDelete();
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa thành công!');
        } else {
            return abort(404); 
        }
    }

    public function deleteAll()
    {
        Tags::onlyTrashed()->forceDelete();
        Session::flash('iconMessage', 'success');
        return redirect()->back()->with('message', 'Xóa tất cả thành công!');
    }
}
