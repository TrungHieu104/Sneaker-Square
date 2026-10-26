<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\SharesStorefrontLayout;
use App\Models\NewsModel;
use App\Models\ProductModel;
use App\Models\UserEventModel;
use App\Services\UserEventLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use ProtoneMedia\LaravelCrossEloquentSearch\Search;

class SearchController extends Controller
{
    use SharesStorefrontLayout;

    public function __construct(Request $request, private readonly UserEventLog $events)
    {
        $this->shareStorefrontLayout();
        View::share('keyword', $request->input('keyword'));
    }

    public function search(Request $request)
    {
        $keyword = trim(strip_tags($request->input('keyword')));

        if (! $keyword) {
            Session::flash('iconMessage', 'info');

            return redirect()->back()->with('message', 'Vui lòng nhập từ khóa');
        }

        $results = Search::add(NewsModel::where('news_hidden', 1)->whereDate('post_date', '<=', date('Y-m-d')), 'news_title')
            ->add(ProductModel::withRatingSummary()->withPriceRange()->where('pro_hidden', 1)->whereDate('pro_date', '<=', date('Y-m-d')), 'pro_name')
            ->dontParseTerm()
            ->paginate(8)
            ->search($keyword);
        $countRecord = $results->count();

        // Searches that find nothing are the shop learning what it does not
        // sell, so the total is kept rather than just this page of it.
        if ($results->currentPage() === 1) {
            $this->events->record(UserEventModel::SEARCH, ['meta' => ['keyword' => $keyword, 'results' => $results->total()]]);
        }

        return view('frontend.pages.search', compact('results', 'keyword', 'countRecord'));
    }
}
