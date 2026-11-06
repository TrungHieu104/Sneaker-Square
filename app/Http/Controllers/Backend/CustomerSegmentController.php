<?php

namespace App\Http\Controllers\Backend;

use App\Enums\CustomerSegment;
use App\Http\Controllers\Controller;
use App\Services\CustomerSegments\SegmentReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;

class CustomerSegmentController extends Controller
{
    /**
     * The admin navbar prints the search box's last keyword on every page.
     */
    public function __construct(Request $request)
    {
        ViewFacade::share('keyword', $request->input('keyword'));
    }

    public function index(SegmentReport $report): View
    {
        return view('backend.pages.statistical.segments', [
            'groups' => $report->overview(),
            'campaigns' => $report->campaigns(),
            'levels' => $report->levels(),
        ]);
    }

    public function show(CustomerSegment $segment, SegmentReport $report): View
    {
        return view('backend.pages.statistical.segment_members', [
            'segment' => $segment,
            'members' => $report->members($segment),
        ]);
    }
}
