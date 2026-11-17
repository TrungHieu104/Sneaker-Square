<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\SharesStorefrontLayout;
use App\Http\Requests\Frontend\ContactFormRequest;
use App\Mail\ContactMail;
use App\Models\CategoryModel as Category;
use App\Models\CateSlideModel as CateSlide;
use App\Models\ContactFormModel;
use App\Models\FaqGroupModel;
use App\Models\NewsModel as News;
use App\Models\PageModel;
use App\Models\ProductModel as Product;
use App\Services\ShopSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class HomeController extends Controller
{
    use SharesStorefrontLayout;

    public function __construct()
    {
        $this->shareStorefrontLayout();

        // The home page shows only the promotions running today, from every
        // slide group, where other pages fall back to the first group.
        $slide = CateSlide::join('promotion', function ($join) {
            $join->on('cate_slide.cate_slide_id', '=', 'promotion.cate_slide_id')
                ->where('cate_slide.cate_slide_hidden', 1)
                ->where('promotion.promotion_hidden', 1);
        })
            ->select('cate_slide.*', 'promotion.*')
            ->orderBy('promotion_sort', 'asc')
            ->whereDate('promotion_start', '<=', date('Y-m-d'))
            ->whereDate('promotion_end', '>', date('Y-m-d'))
            ->get();
        $catePro = Category::where('cate_hidden', 1)->where('cate_parent_id', 1)->orderBy('cate_sort', 'DESC')->get();
        View::share(compact('slide', 'catePro'));
    }

    public function index(Request $request)
    {
        $hotProduct = Product::withRatingSummary()->withPriceRange()
            ->where('pro_hot', 1)
            ->where('pro_hidden', 1)
            ->where('pro_date', '<=', date('Y-m-d'))
            ->orderBy('pro_date', 'desc')
            ->limit(8)
            ->get();

        $saleProduct = Product::withRatingSummary()->withPriceRange()
            ->onSale()
            ->where('pro_hidden', 1)
            ->whereDate('pro_date', '<=', date('Y-m-d'))
            ->orderBy('updated_at', 'desc')
            ->limit(3)
            ->get();

        $mostViewProduct = Product::withRatingSummary()->withPriceRange()
            ->where('pro_hidden', 1)
            ->whereDate('pro_date', '<=', date('Y-m-d'))
            ->orderBy('pro_views', 'desc')
            ->limit(9)
            ->get();

        $hotNews = News::where('news_hidden', 1)
            ->where('news_hot', 1)
            ->whereDate('post_date', '<=', date('Y-m-d'))
            ->orderBy('views', 'asc')
            ->limit(5)
            ->get();

        return view('frontend.pages.home', compact('hotProduct', 'saleProduct', 'mostViewProduct', 'hotNews'));
    }

    public function about()
    {
        $about = PageModel::about()->visible()->limit(1)->get();

        return view('frontend.pages.about', compact('about'));
    }

    public function contact()
    {
        $contact = app(ShopSettings::class)->storefrontContact();

        return view('frontend.pages.contact', compact('contact'));
    }

    /**
     * The return policy, written from the settings the shop actually runs on
     * rather than from a page somebody has to remember to edit.
     */
    public function returnPolicy()
    {
        $settings = app(ShopSettings::class);

        return view('frontend.pages.return_policy', [
            'soNgayTra' => $settings->returnDays(),
            'soNgayTuHoanTat' => $settings->autoCompleteDays(),
        ]);
    }

    /**
     * Every shown question, grouped as the admin ordered them, with the way to
     * reach a person for anything the page does not answer.
     */
    public function faq()
    {
        $shop = app(ShopSettings::class)->shopInfoForDisplay();

        return view('frontend.pages.faq', [
            'groups' => FaqGroupModel::published(),
            'hotline' => ['tel' => $shop['phone'], 'display' => $shop['phone_display']],
        ]);
    }

    public function policyDetail(string $pageSlug = '')
    {
        $pageId = PageModel::policies()->where('page_slug', $pageSlug)->value('page_id');
        if ($pageId == null) {
            Session::flash('iconMessage', 'info');

            return redirect()->back()->with('message', 'Chính sách không tồn tại');
        }

        $policy = PageModel::visible()->find($pageId);
        abort_if($policy === null, 404);

        return view('frontend.pages.policy', compact('policy'));
    }

    public function store(ContactFormRequest $request)
    {
        $contact = new ContactFormModel;
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
