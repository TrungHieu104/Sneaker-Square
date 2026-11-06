<?php

namespace App\Http\Controllers\Backend;

use App\Actions\SendCampaignAction;
use App\Enums\CustomerSegment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\CampaignRequest;
use App\Services\CustomerSegments\CampaignDraft;
use App\Services\CustomerSegments\SegmentReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View as ViewFacade;

class CampaignController extends Controller
{
    /**
     * The admin navbar prints the search box's last keyword on every page.
     */
    public function __construct(Request $request)
    {
        ViewFacade::share('keyword', $request->input('keyword'));
    }

    public function create(CustomerSegment $segment, CampaignDraft $draft, SendCampaignAction $action, SegmentReport $report): View
    {
        return view('backend.pages.statistical.campaign_create', [
            'segment' => $segment,
            'draft' => $draft->for($segment),
            'recipients' => $report->figuresOf($action->reachable($segment)->pluck('user_id')),
            'members' => $report->memberCount($segment),
            'quietDays' => SendCampaignAction::QUIET_DAYS,
        ]);
    }

    public function store(CustomerSegment $segment, CampaignRequest $request, SendCampaignAction $action): RedirectResponse
    {
        $sent = $action->execute($segment, [
            'name' => (string) $request->validated('name'),
            'percent' => $request->integer('percent'),
            'min_order' => $request->integer('min_order'),
            'max_discount' => $request->filled('max_discount') ? $request->integer('max_discount') : null,
            'days' => $request->integer('days'),
            'subject' => (string) $request->validated('subject'),
            'message' => (string) $request->validated('message'),
        ], $request->recipientIds(), (int) $request->user()?->user_id);

        Session::flash('iconMessage', $sent > 0 ? 'success' : 'error');

        return redirect()->route('admin.segments')->with('message', $sent > 0
            ? "Đã gửi ưu đãi cho {$sent} khách."
            : 'Không có khách nào để gửi: khách đã nhận ưu đãi hoặc đã huỷ nhận email.');
    }
}
