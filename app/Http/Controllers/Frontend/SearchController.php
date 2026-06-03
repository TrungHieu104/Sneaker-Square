<?php

namespace App\Http\Controllers\Frontend;

use App\Models\NewsModel;
use App\Models\ProductModel;
use App\Models\PromotionModel as Promotion;
use App\Models\ContactModel as Contact;
use App\Models\FaqModel as Faq;
use App\Models\MenuModel as Menu;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use ProtoneMedia\LaravelCrossEloquentSearch\Search;

class SearchController extends Controller
{
    public function __construct(Request $request)
    {
        $keyword = $request->input('keyword');
        $slide = Promotion::where('cate_slide_id', 1)->where('promotion_hidden', 1)->get();
        $contact = Contact::where('contact_hidden', 1)->limit(1)->get();
        $faq = Faq::where('faq_hidden',1)->where('faq_about',0)->orderBy('faq_id','desc')->get();
        $data = Menu::where('menu_hidden', 1)->orderBy('menu_position', 'asc')->get();
        $menu = $this->data_tree($data);
        View::share(compact('slide', 'contact', 'faq', 'menu', 'keyword'));
    }

    function data_tree($data, $parent_id = 0, $level = 0)
    {
        $result = [];
        foreach ($data as $item) {
            if ($item['menu_parent_id'] == $parent_id) {
                $item['level'] = $level;
                $result[] = $item;
                $child = $this->data_tree($data, $item['menu_id'], $level + 1);
                $result = array_merge($result, $child);
            }
        }
        return $result;
    }

    public function search(Request $request)
    {
        $keyword = trim(strip_tags($request->input('keyword')));

        if(!$keyword){
            Session::flash('iconMessage', 'info');
            return redirect()->back()->with('message', 'Vui lòng nhập từ khóa');
        }

        $results = Search::add(NewsModel::where('news_hidden', 1)->whereDate('post_date', '<=', date('Y-m-d')), 'news_title')
        ->add(ProductModel::where('pro_hidden', 1)->whereDate('pro_date', '<=', date("Y-m-d")), 'pro_name')
        ->dontParseTerm()
        ->paginate(8)
        ->search($keyword);
        $countRecord = $results->count();
        return view('frontend.pages.search', compact('results', 'keyword', 'countRecord'));
    }
}
