<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\OrderModel;
use App\Models\OrderReturnModel;
use App\Models\ShipmentEventModel;
use App\Services\Shipping\GhnStatus;
use App\Services\Shipping\ShipmentTracker;
use App\Services\Shipping\ShippingCarrier;
use App\Services\Shipping\ShippingUnavailable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Plays GHN's part on a local machine.
 *
 * The sandbox only lets a shop move a parcel to three states, so the rest of
 * the journey — picked up, out for delivery, delivered, sent back — cannot be
 * produced there. This page builds the callback GHN would send and hands it to
 * the same tracker the webhook uses; only the HTTP hop and the URL secret are
 * skipped.
 */
class GhnSimulatorController extends Controller
{
    /**
     * Statuses a shop token is allowed to set on GHN, and how. `api` is the
     * switch-status path; null means the status happens by itself and there is
     * nothing to call. `reachable` is narrower than having the permission:
     * GHN also refuses a status its own state machine cannot get to from where
     * the parcel stands, which on the sandbox is everything past pickup.
     *
     * @var array<string, array{note: string, api: ?string, reachable: bool}>
     */
    public const ON_SANDBOX = [
        'ready_to_pick' => ['note' => 'Tự có khi tạo vận đơn', 'api' => null, 'reachable' => true],
        'cancel' => ['note' => 'API huỷ đơn (switch-status/cancel)', 'api' => 'cancel', 'reachable' => true],
        'storing' => [
            'note' => 'API giao lại đơn hàng (switch-status/storing). GHN chỉ nhận khi vận đơn đã qua bước lấy hàng, trên sandbox không tới được bước đó',
            'api' => 'storing',
            'reachable' => false,
        ],
        'return' => [
            'note' => 'API trả lại hàng (switch-status/return). GHN chỉ nhận khi vận đơn đã qua bước lấy hàng, trên sandbox không tới được bước đó',
            'api' => 'return',
            'reachable' => false,
        ],
    ];

    /**
     * The order a parcel moves through in real life, so the page reads top
     * to bottom the way a shipment does.
     *
     * @var array<string, array<int, string>>
     */
    public const GROUPS = [
        'Lấy hàng' => ['ready_to_pick', 'picking', 'money_collect_picking', 'picked'],
        'Trung chuyển' => ['storing', 'transporting', 'sorting'],
        'Giao hàng' => ['delivering', 'money_collect_delivering', 'delivered'],
        'Giao không thành công' => ['delivery_fail', 'waiting_to_return'],
        'Hoàn hàng' => ['return', 'return_transporting', 'return_sorting', 'returning', 'return_fail', 'returned'],
        'Khác' => ['cancel', 'exception', 'damage', 'lost'],
    ];

    public function __construct(Request $request)
    {
        ViewFacade::share('keyword', $request->input('keyword'));
    }

    public static function enabled(): bool
    {
        return (bool) config('services.ghn.simulator') && ! app()->isProduction();
    }

    public function index(Request $request): View
    {
        abort_unless(self::enabled(), 404);

        $parcels = $this->parcels();
        $code = (string) $request->query('ma', $parcels->keys()->first() ?? '');
        $parcel = $parcels->get($code);

        return view('backend.pages.order.ghn_simulator', [
            'parcels' => $parcels,
            'code' => $parcel ? $code : null,
            'parcel' => $parcel,
            'events' => $parcel
                ? ShipmentEventModel::where('shipping_code', $code)->orderByDesc('happened_at')->get()
                : collect(),
        ]);
    }

    public function send(Request $request, ShipmentTracker $tracker): RedirectResponse
    {
        abort_unless(self::enabled(), 404);

        $parcels = $this->parcels();

        $data = $request->validate([
            'ma' => ['required', 'string', Rule::in($parcels->keys()->all())],
            'status' => ['required', 'string', Rule::in(array_keys(GhnStatus::all()))],
        ], [
            'ma.in' => 'Không có vận đơn này trong hệ thống.',
            'status.in' => 'Trạng thái GHN không hợp lệ.',
        ]);

        $parcel = $parcels->get($data['ma']);
        $time = $this->timeFor($data['ma']);

        $result = $tracker->record([
            'OrderCode' => $data['ma'],
            'ClientOrderCode' => $parcel['reference'],
            'Status' => $data['status'],
            'Time' => $time->toIso8601String(),
            'Type' => 'switch_status',
            'Warehouse' => 'Giả lập (local)',
            'Description' => 'Giả lập: '.GhnStatus::label($data['status']),
        ]);

        Session::flash('iconMessage', $result['recorded'] ? 'success' : 'info');

        return redirect()->route('ghn_simulator.index', ['ma' => $data['ma']])->with('message', $result['recorded']
            ? 'Đã gửi "'.GhnStatus::label($data['status']).'" lúc '.$time->format('H:i d/m/Y').'.'
            : 'Sự kiện này đã có, không ghi thêm.');
    }

    /**
     * Calls GHN for real, for the few statuses a shop token is allowed to set.
     * Nothing is written here: GHN answers by calling the webhook back, which
     * is the point of pressing this rather than the simulate button.
     */
    public function real(Request $request, ShippingCarrier $carrier): RedirectResponse
    {
        abort_unless(self::enabled(), 404);

        $parcels = $this->parcels();
        $callable = array_keys(array_filter(self::ON_SANDBOX, fn (array $row) => $row['api'] !== null));

        $data = $request->validate([
            'ma' => ['required', 'string', Rule::in($parcels->keys()->all())],
            'status' => ['required', 'string', Rule::in($callable)],
        ], [
            'ma.in' => 'Không có vận đơn này trong hệ thống.',
            'status.in' => 'GHN không cho shop tự chuyển sang trạng thái này.',
        ]);

        try {
            $carrier->switchStatus($data['ma'], self::ON_SANDBOX[$data['status']]['api']);
        } catch (ShippingUnavailable $e) {
            Session::flash('iconMessage', 'error');

            return redirect()->route('ghn_simulator.index', ['ma' => $data['ma']])->with('message', $e->getMessage());
        }

        Session::flash('iconMessage', 'success');

        return redirect()->route('ghn_simulator.index', ['ma' => $data['ma']])
            ->with('message', 'GHN đã nhận lệnh chuyển sang '.GhnStatus::label($data['status'])
                .'. Hành trình chỉ đổi khi GHN gọi webhook về.');
    }

    /**
     * Runs the auto-complete job as if some days had already passed, so the
     * waiting period can be tried without backdating GHN's events — the
     * tracker would ignore a backdated "delivered" behind a newer one.
     */
    public function autoComplete(Request $request): RedirectResponse
    {
        abort_unless(self::enabled(), 404);

        $days = (int) $request->validate(['days_later' => ['nullable', 'integer', 'min:0', 'max:60']])['days_later'];

        Carbon::setTestNow(Carbon::now()->addDays($days));

        try {
            Artisan::call('orders:auto-complete');
        } finally {
            Carbon::setTestNow();
        }

        Session::flash('iconMessage', 'info');

        return redirect()->route('ghn_simulator.index', ['ma' => $request->input('ma')])
            ->with('message', 'Giả định đã qua '.$days.' ngày. '.trim(Artisan::output()));
    }

    /**
     * Every parcel GHN could be calling about: the outbound one on each order
     * and the one coming back on each return.
     *
     * @return Collection<string, array{label: string, reference: string, status: ?string, order_id: int}>
     */
    private function parcels()
    {
        $outbound = OrderModel::whereNotNull('order_shipping_code')->orderByDesc('order_id')->get()
            ->mapWithKeys(fn (OrderModel $order) => [$order->order_shipping_code => [
                'label' => 'Giao đi · đơn '.$order->order_code.' · '.$order->order_name,
                'reference' => $order->order_code,
                'status' => $order->order_shipping_status,
                'order_id' => (int) $order->order_id,
            ]]);

        $returns = OrderReturnModel::with('order')->whereNotNull('return_shipping_code')->orderByDesc('return_id')->get()
            ->mapWithKeys(fn (OrderReturnModel $return) => [$return->return_shipping_code => [
                'label' => 'Trả về · đơn '.$return->order->order_code.' · '.$return->order->order_name,
                'reference' => $return->reference(),
                'status' => $return->return_shipping_status,
                'order_id' => (int) $return->order_id,
            ]]);

        return $outbound->union($returns);
    }

    /**
     * A second after the parcel's latest event at the earliest: the tracker
     * ignores anything older than what it already has, and two clicks in the
     * same second would otherwise look like one callback sent twice.
     */
    private function timeFor(string $code): Carbon
    {
        $latest = ShipmentEventModel::where('shipping_code', $code)->max('happened_at');
        $now = Carbon::now();

        return $latest && Carbon::parse($latest)->greaterThanOrEqualTo($now)
            ? Carbon::parse($latest)->addSecond()
            : $now;
    }
}
