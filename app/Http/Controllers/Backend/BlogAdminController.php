<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use App\Models\CateNewsModel as CateNews;
use App\Models\NewsModel as News;
use App\Models\TagsModel as Tags;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Backend\NewsRequest;
use App\Http\Requests\Backend\NewsUpdateRequest;
use App\Models\UserModel;

class BlogAdminController extends Controller
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
        $orderBy = $request->input('sort-by', 'news_id'); 
        $orderType = $request->input('sort-type', 'asc');

        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['news.news_title'];

        $news = $this->performSearch(News::orderBy($orderBy, $orderType), $keyword, $searchableFields)->paginate(10)->withQueryString();
        
        return view('backend.pages.blog.blogs.blog_list',compact('news', 'orderBy', 'orderType'));
    }

    /**
     * Show the form for creating a new resource.
     */

    public function cateNews($id)
    {
        $cateNews = CateNews::find($id);
        if ($cateNews) {
            return $cateNews->cate_new_name;
        }
        return false;
    }

    public function create()
    {
        $cateN = CateNews::all();
        $tags = Tags::all();
        $today = today()->toDateString();
        return view('backend.pages.blog.blogs.blog_create',compact('cateN','tags', 'today'));
    }

    public function status(Request $request, $id)
    {
        $arr = $request->post();
        $status = ($request->has('n-status'))? $arr['n-status']:"";
        $data = News::find($id);

        if (!$data) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu'], 404);
        }

        $data->news_hidden = $status;
        $data->save();

        return response()->json(['message' => 'Cập nhật thành công']);
        
    }

    public function hot(Request $request, $id)
    {
        $arr = $request->post();
        $hot = ($request->has('n-hot'))? $arr['n-hot']:"";
        $data = News::find($id);

        if (!$data) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu'], 404);
        }

        $data->news_hot = $hot;
        $data->save();

        return response()->json(['message' => 'Cập nhật thành công']);
        
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(NewsRequest $request)
    {
        $arr = $request->post(); 
        $date = ($request->has('post_date'))? $arr['post_date']:"";
        $slug = ($request->has('slug'))? $arr['slug']:"";
        if (!empty($slug) && substr($slug, -5) !== ".html") {
            $slug .= ".html";
        }
        $news = News::create([
            'news_title'=> $request->title,
            'news_slug'=>  $slug,
            'news_hidden'=>  $request->hidden,
            'news_hot'=>  $request->hot,
            'news_summarize'=>  $request->summarize,
            'news_content'=>  $request->content,
            'post_date'=>  date('Y/m/d', strtotime($date)),
            'cate_news_id'=>  $request->cate,
            'news_SEO_title'=>  $request->seo_title,
            'news_meta_keywords'=>  $request->seo_keywords,
            'news_meta_description'=>  $request->seo_description,
            'news_created_by'=>  $request->created_by,
            'user_id' => Auth::user()->user_id,
        ]);
        $news->getTags()->attach($request->tags);

        if($request->hasFile('img_blog'))
        {
            $file = $request->file('img_blog');
            $extension = $file->getClientOriginalExtension();
            $file_name = time().'.'.$extension;
            $file->move(public_path('backend/uploads/blog/'), $file_name);
            $news->news_img = 'backend/uploads/blog/'.$file_name;
        } 
        $news->save();
        Session::flash('iconMessage','success');
        return redirect(route('blog.index'))->with('message', 'Thêm mới thành công!');
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
    public function edit(Request $request, string $news_id)
    {
        $cateN = CateNews::all();
        $tags = Tags::all();
        $news = News::find($news_id);
        if ($news==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/blog')->with('message', 'Không tồn tại bài viết');
        }
        return view("backend.pages.blog.blogs.blog_edit", compact('news','cateN','tags'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(NewsUpdateRequest $request, string $id)
    {
        $arr = $request->post();
        $news = News::find($id);
        $title = ($request->has('title'))? $arr['title']:"";
        $summarize = ($request->has('summarize'))? $arr['summarize']:"";
        $contents = ($request->has('content'))? $arr['content']:"";
        $slug = ($request->has('slug'))? $arr['slug']:"";
        if (!empty($slug) && substr($slug, -5) !== ".html") {
            $slug .= ".html";
        }
        $hidden = ($request->has('hidden'))? (int)$arr['hidden']:"0";
        $hot = ($request->has('hot'))? (int)$arr['hot']:"0";
        $date = ($request->has('post_date'))? $arr['post_date']:"";
        $cate_id = ($request->has('cate'))? $arr['cate'] : "";
        $seo_title = ($request->has('seo_title'))? $arr['seo_title'] : "";
        $seo_keywords = ($request->has('seo_keywords'))? $arr['seo_keywords'] : "";
        $seo_description = ($request->has('seo_description'))? $arr['seo_description'] : "";
        // $created_by = ($request->has('created_by'))? $arr['created_by'] : "";
        $post_date = date('Y/m/d', strtotime($date));
        if ($news ==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect(route('blog.index'))->with('message', 'Không tồn tại bài viết');;
        }
        $news->news_title = $title;
        $news->news_summarize = $summarize;
        $news->news_content = $contents;
        $news->news_slug = $slug;
        $news->news_hidden = $hidden;
        $news->news_hot = $hot;
        $news->post_date = $post_date;
        $news->cate_news_id = $cate_id;
        $news->news_SEO_title = $seo_title;
        $news->news_meta_keywords = $seo_keywords;
        $news->news_meta_description = $seo_description;
        // $news->news_created_by = $created_by;

        $news->getTags()->sync($request->tags);

        if($request->hasFile('img_blog'))
        {
            $file = $request->file('img_blog');
            $extension = $file->getClientOriginalExtension();
            $file_name = time().'.'.$extension;
            $file->move(public_path('backend/uploads/blog/'), $file_name);
            $news->news_img = 'backend/uploads/blog/'.$file_name;
            
        }
        $news->save();
        Session::flash('iconMessage', 'success');
        return redirect(route('blog.index'))->with('message', 'Chỉnh sửa thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $news = News::find($id);
        if ($news==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            redirect()->back()->with('message', 'Không tồn tại bài viết này!');
        }
        $news->delete();
        Session::flash('iconMessage', 'success');
        return redirect('/admin/blog')->with('message', 'Xóa thành công');
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
        $searchableFields = ['news.news_title'];

        $blogTrash = $this->performSearch(News::onlyTrashed($orderBy, $orderType), $keyword, $searchableFields)->paginate(10)->withQueryString();
        return view("backend.pages.blog.blogs.blog_trash", compact('blogTrash', 'orderBy', 'orderType'));
    }

    public function restore($id)
    {
        $news = News::withTrashed()->where('news_id', $id)->first();
        if ($news) {
            $news->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Hoàn tác thành công!');
        } else {
            return abort(404);
        }
    }

    public function restoreAll() 
    {
        News::onlyTrashed()->restore();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Hoàn tác thành công!');
    }
    
    public function forceDelete($id)
    {
        $news = News::withTrashed()->find($id); 
        if ($news) {
            $news->forceDelete();
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa thành công!');
        } else {
            return abort(404); 
        }
    }

    public function deleteAll()
    {
        News::onlyTrashed()->forceDelete();
        Session::flash('iconMessage', 'success');
        return redirect()->back()->with('message', 'Xóa tất cả thành công!');
    }

}
