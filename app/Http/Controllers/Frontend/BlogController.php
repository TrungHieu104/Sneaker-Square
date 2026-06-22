<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CateNewsModel as CateNews;
use App\Models\NewsModel as News;
use App\Models\TagsModel as Tags;
use App\Models\NewsByTagsModel as NewsByTags;
use App\Models\ContactModel as Contact;
use App\Models\PromotionModel as Promotion;
use App\Models\FaqModel as Faq;
use App\Models\MenuModel as Menu;
use App\Models\UserModel;
use Illuminate\Support\Facades\Session;

class BlogController extends Controller
{   
    public function __construct()
    {
        $slide = Promotion::where('cate_slide_id',1)->where('promotion_hidden',1)->get();
        $contact = Contact::where('contact_hidden',1)->limit(1)->get();
        $faq = Faq::where('faq_hidden',1)->where('faq_about',0)->orderBy('faq_id','desc')->get();
        $cateNews = CateNews::where('cate_news_hidden',1)
        ->orderBy('cate_news_sort', 'asc')
        ->get();
        $data = Menu::where('menu_hidden',1)->orderBy('menu_position','asc')->get();
        $menu = $this->data_tree($data);
        view()->share(compact('slide','contact','faq','cateNews','menu'));
    }

    function data_tree($data, $parent_id = 0, $level=0)
    {
        $result = [];
        foreach($data as $item){
            if($item['menu_parent_id'] == $parent_id){
                $item['level'] = $level;
                $result[] = $item;
                $child = $this->data_tree($data, $item['menu_id'], $level+1);
                $result = array_merge($result, $child);
            }
        }
        return $result;
    }

    public function index(string $cateNewsSlug = '')
    {
        $perpage = 8;
        $getOneCateNews = CateNews::where('cate_news_hidden',1)
        ->where('cate_news_slug', $cateNewsSlug)
        ->first();

        $cateNewsId = CateNews::where('cate_news_slug', $cateNewsSlug)->value('cate_news_id');

        if(($cateNewsSlug != '') && ($cateNewsId == null)) {
            Session::flash('iconMessage', 'info');
            return redirect(route('blog.page'))
            ->with('message', 'Không tồn tại mục bài viết này!');
        }
        
        if ($getOneCateNews) {
            $getOneCateNews->setRelation('getNewsInCate', $getOneCateNews->getNewsInCate()->where('news_hidden', 1)->whereDate('post_date', '<=', date('Y-m-d'))->orderBy('post_date','desc')->paginate(8));
        }
        $listNews = News::where('news_hidden', 1)->whereDate('post_date', '<=', date('Y-m-d'))->orderBy('news_id', 'DESC')->paginate($perpage)->withQueryString();
        
        $hotNews = News::where('news_hidden', 1)
        ->where('cate_news_id', '!=', $cateNewsId)
        ->whereDate('post_date', '<=', date('Y-m-d'))
        ->orderBy('views','DESC')
        ->limit(5)
        ->get();

        $tags = Tags::where('tag_hidden',1)
        ->orderBy('created_at','asc')
        ->limit(20)
        ->get();

        return view('frontend.pages.blog.blog_page', compact('listNews', 'hotNews', 'getOneCateNews', 'tags'));
    }

    public function detail(Request $request,string $newsSlug = '')
    {
        $newsId = News::where('news_slug', $newsSlug)
        ->whereDate('post_date', '<=', date('Y-m-d'))
        ->value('news_id');

        $newsIdmt = News::where('news_slug', $newsSlug)->get();
        if(($newsSlug != '') && ($newsId == null)) {
            Session::flash('iconMessage', 'info');
            return redirect(route('blog.page'))
            ->with('message', 'Bài viết này không tồn tại trong blog của Sneaker Square!');
        }           
        $detailNews = News::where('news_id', $newsId)
        ->where('news_hidden', 1)
        ->first();
        if ($detailNews == null){
            Session::flash('iconMessage', 'info');
            return redirect(route('blog.page'))
            ->with('message', 'Không tìm thấy bài viết trong blog của Sneaker Square!');
        }
        $detailNews->views = $detailNews->views+1;
        $detailNews->save();
        
        $relatedNews = News::where('cate_news_id', $detailNews->cate_news_id)
        ->where('news_id', '!=', $detailNews->news_id)
        ->where('news_hidden', 1)
        ->whereDate('post_date', '<=', date('Y-m-d'))
        ->orderBy('post_date','desc')
        ->orderBy('views', 'desc')
        ->limit(5)
        ->get();

        $getTags = NewsByTags::join('tags','news_by_tags.tag_id','=','tags.tag_id')
        ->select('news_id','news_by_tags.tag_id','tags.tag_content','tags.tag_slug','tags.tag_hidden')
        ->where('news_id',$newsId)
        ->where('tag_hidden', 1)
        ->groupBy('news_id', 'news_by_tags.tag_id','tags.tag_content','tags.tag_slug','tags.tag_hidden')
        ->get();
        
        foreach ($newsIdmt as $key => $value) {
            $meta_desc = $value->news_meta_description; 
            $meta_keywords = $value->news_meta_keywords;      
            $meta_title = $value->news_SEO_title;
            $img = $value->news_img;
            $url_canonical = $request->url();    
        }
        return view('frontend.pages.blog.blog_detail', compact('detailNews', 'relatedNews', 'getTags',
        'meta_desc', 'meta_keywords', 'meta_title', 'url_canonical','img'));
    }

    public function tags(string $tagsSlug = '')
    {
        $tagsId = Tags::where('tag_slug', $tagsSlug)->value('tag_id');
        $perpage = 8;
        if(($tagsSlug != '') && ($tagsId == null)) {
            Session::flash('iconMessage', 'info');
            return redirect(route('blog.page'))->with('message', 'Không tồn tại tag này!');
        } 
                
        $getOneTag = Tags::where('tag_hidden',1)
        ->where('tag_id', $tagsId)
        ->first();

        if ($getOneTag) {
            $getOneTag->setRelation('getNews', $getOneTag->getNews()->where('news_hidden', 1)->whereDate('post_date', '<=', date('Y-m-d'))->orderBy('post_date','desc')->paginate(8));
        }
        $listNews = News::where('news_hidden', 1)
        ->whereDate('post_date', '<=', date('Y-m-d'))
        ->orderBy('news_id','desc')
        ->paginate($perpage)->withQueryString();

        $hotNews = News::where('news_hidden', 1)
        ->whereDate('post_date', '<=', date('Y-m-d'))
        ->orderBy('views','desc')
        ->limit(5)
        ->get();

        $tags = Tags::where('tag_hidden',1)
        ->orderBy('created_at','asc')
        ->limit(20)
        ->get();

        return view('frontend.pages.blog.blog_tag', compact('listNews', 'hotNews', 'tags', 'getOneTag'));
    }

    public function search (Request $request)
    {
        $perpage = 8;
        $keywords = trim(strip_tags($request->input('keywords', '')));
        $listNews=[];
        if ($keywords!=""){
            $listNews = News::where('news_hidden',1)
            ->whereDate('post_date', '<=', date('Y-m-d'))
            ->where(function($query) use ($keywords){
                $query->where("news_title", "like", "%$keywords%")
                ->orWhere("news_content", "like", "%$keywords%")
                ->orWhere("news_summarize", "like", "%$keywords%");
            })->paginate($perpage)->withQueryString();
        } else {
            Session::flash('iconMessage', 'info');
            return redirect()->back()->with('message', 'Vui lòng nhập từ khóa');
        }
        return view('frontend.pages.blog.blog_search', compact('listNews','keywords'));
    }
}