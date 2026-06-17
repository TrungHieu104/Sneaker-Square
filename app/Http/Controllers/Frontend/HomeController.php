<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CategoryModel as Category;
use App\Models\ProductModel as Product;
use App\Models\NewsModel as News;
use App\Models\CateNewsModel as CateNews;
use App\Models\ContactModel as Contact;
use App\Models\ContactFormModel;
use App\Http\Requests\Frontend\ContactFormRequest;
use App\Mail\ContactMail;
use Illuminate\Support\Facades\Mail;
use App\Models\PromotionModel as Promotion;
use App\Models\FaqModel as Faq;
use App\Models\MenuModel as Menu;
use App\Models\CateSlideModel as CateSlide;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class HomeController extends Controller
{
    public function __construct()
    {
        $slide = CateSlide::join('promotion', function ($join) {
            $join->on('cate_slide.cate_slide_id', '=', 'promotion.cate_slide_id')
                ->where('cate_slide.cate_slide_hidden', 1)
                ->where('promotion.promotion_hidden', 1);
                
                //  ->where('promotion.cate_slide_id', 1);
        })
        ->select('cate_slide.*', 'promotion.*')
        ->orderBy('promotion_sort','asc')
        ->whereDate('promotion_start', '<=', date("Y-m-d"))
        ->whereDate('promotion_end', '>', date("Y-m-d"))
        ->get();
        $contact = Contact::where('contact_hidden',1)->limit(1)->get();
        $faq = Faq::where('faq_hidden',1)->where('faq_about',0)->orderBy('faq_id','desc')->get();
        $data = Menu::where('menu_hidden',1)->orderBy('menu_position','asc')->get();
        $catePro = Category::where('cate_hidden', 1)->where('cate_parent_id', 1)->orderBy('cate_sort', 'DESC')->get();
        $menu = $this->data_tree($data);
        View::share(compact('slide','contact','faq','menu', 'catePro'));
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

    public function index(Request $request)
    {
        $hotProduct = Product::where('pro_hot', 1)
                                ->where('pro_hidden', 1)
                                ->where('pro_date','<=', date('Y-m-d'))
                                ->orderBy('pro_date', 'desc')
                                ->limit(8)
                                ->get();

        $saleProduct = Product::where('pro_price_sale', '!=', 0)
                                ->where('pro_hidden', 1)
                                ->whereDate('pro_date', '<=', date('Y-m-d'))
                                ->orderBy('updated_at', 'desc')
                                ->limit(3)
                                ->get();
                                
        $mostViewProduct = Product::where('pro_hidden', 1)
                                ->whereDate('pro_date', '<=', date('Y-m-d'))
                                ->orderBy('pro_views', 'desc')
                                ->limit(9)
                                ->get();
          
        $hotNews = News::where('news_hidden',1)
                        ->where('news_hot',1)
                        ->whereDate('post_date', '<=', date('Y-m-d'))
                        ->orderBy('views', 'asc')
                        ->limit(5)
                        ->get();      
        return view('frontend.pages.home', compact('hotProduct', 'saleProduct', 'mostViewProduct', 'hotNews'));
    }
    
    public function about()
    {
        $about = Faq::where('faq_hidden',1)->where('faq_about',1)->limit(1)->get();
        return view('frontend.pages.about',compact('about'));
    }

    public function contact()
    {
        $contact = Contact::where('contact_hidden',1)->limit(1)->get();
        return view('frontend.pages.contact',compact('contact'));
    }
 
    public function faqDetail(string $faqSlug = '')
    {
        $faqId = Faq::where('faq_slug', $faqSlug)->value('faq_id');
        if($faqId == null) {
            Session::flash('iconMessage', 'info');
            return redirect()->back()->with('message', 'Chính sách không tồn tại');
        }

        $detailFaq = Faq::where('faq_id', $faqId)
                                    ->where('faq_hidden', 1)
                                    ->first();

        return view('frontend.pages.faq',compact('detailFaq'));
    }

    public function store(ContactFormRequest $request)
    {
        $contact = new ContactFormModel();
        $contact->name = $request['name'];
        $contact->email = $request['email'];
        $contact->phone = $request['phone'];
        $contact->title = $request['title'];
        $contact->content = $request['content'];
        $contact->save();
        Mail::to($contact->email)->send(new ContactMail($contact));
        Session::flash('iconMessage', 'success');
        return redirect()->back()->with('message', 'Đã gửi thông tin');
    }
}