<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Analytics\BehaviourReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;

class BehaviourController extends Controller
{
    public const PERIODS = [7, 30, 90];

    /**
     * The admin navbar prints the search box's last keyword on every page.
     */
    public function __construct(Request $request)
    {
        ViewFacade::share('keyword', $request->input('keyword'));
    }

    public function index(Request $request): View
    {
        $days = in_array($request->integer('days'), self::PERIODS, true) ? $request->integer('days') : self::PERIODS[1];
        $report = BehaviourReport::lastDays($days);
        $funnel = $report->funnel();

        return view('backend.pages.statistical.behaviour', [
            'days' => $days,
            'from' => $report->from(),
            'to' => $report->to(),
            'funnel' => $funnel,
            'biggestDrop' => BehaviourReport::biggestDrop($funnel),
            'missedSearches' => $report->missedSearches(),
            'viewedNotBought' => $report->viewedNotBought(),
            'restock' => $report->restock(),
            'recommendations' => $report->recommendations(),
            'cartReminders' => $report->cartReminders(),
            'periods' => self::PERIODS,
        ]);
    }
}
