<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ExportCoupon;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\CouponRequest;
use App\Http\Requests\Backend\CouponUpRequest;
use App\Mail\CouponMail;
use App\Models\CouponModel;
use App\Models\UserModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;

class CouponAdminController extends Controller
{
    /**
     * The groups the "send coupon" form offers, by the number of orders a
     * customer has placed: everyone, first-time buyers, regulars, loyal ones.
     */
    private const AUDIENCES = [
        1 => null,
        2 => [1, 1],
        3 => [2, 5],
        4 => [6, null],
    ];

    private const DATE_FORMAT = 'Y/m/d';

    private const COUPON_NOT_FOUND = 'Không tồn tại mã khuyến mãi';

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
        $perpage = 10;
        [$orderBy, $orderType] = $this->listingSort($request, CouponModel::class, 'coupon_id');
        $keyword = $request->input('keyword');
        $searchableFields = ['coupon_name', 'coupon_code'];

        $coupon = $this->performSearch(CouponModel::orderBy($orderBy, $orderType), $keyword, $searchableFields)
            ->paginate($perpage)
            ->withQueryString();
        $today = Carbon::now()->format(self::DATE_FORMAT);

        $max_coupon_used = CouponModel::max('coupon_used');
        $max_use = null;

        while (! $max_use && $max_coupon_used > 0) {
            $max_use = CouponModel::where('coupon_end', '>=', $today)
                ->where('coupon_used', $max_coupon_used)
                ->orderBy('coupon_id', 'desc')
                ->first();

            $max_coupon_used--;
        }

        if ($max_use) {
            $max_used = $max_use->coupon_used;
        } else {
            $max_used = 0;
        }

        return view('backend.pages.coupon.coupon_list', compact('coupon', 'max_used', 'keyword', 'orderBy', 'orderType', 'today'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // The datepicker on this form reads and writes dd-mm-yyyy.
        $today = today()->format('d-m-Y');

        return view('backend.pages.coupon.coupon_create', compact('today'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CouponRequest $request)
    {
        $arr = $request->post();
        $name = ($request->has('name')) ? $arr['name'] : '';
        $code = ($request->has('code')) ? $arr['code'] : '';
        $quantity = ($request->has('quantity')) ? $arr['quantity'] : '';
        $start_coupon = ($request->has('start_coupon')) ? $arr['start_coupon'] : '';
        $end_coupon = ($request->has('end_coupon')) ? $arr['end_coupon'] : '';
        $condition = ($request->has('condition')) ? (int) $arr['condition'] : '0';
        $money = ($request->has('money')) ? $arr['money'] : '';
        $cou = new CouponModel;

        $cou->coupon_name = $name;
        $cou->coupon_code = $code;
        $cou->coupon_quantity = $quantity;
        $time_start = date(self::DATE_FORMAT, strtotime($start_coupon));
        $time_end = date(self::DATE_FORMAT, strtotime($end_coupon));
        $cou->coupon_start = $time_start;
        $cou->coupon_end = $time_end;
        $cou->coupon_condition = $condition;
        $cou->coupon_value = $money;
        $cou->save();
        Session::flash('iconMessage', 'success');

        return redirect(route('coupon.index'))->with('message', 'Thêm thành công');
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
    public function edit(Request $request, $coupon_id)
    {
        $coupon = CouponModel::find($coupon_id);
        if ($coupon == null) {
            $request->session();
            Session::flash('iconMessage', 'info');

            return redirect(route('coupon.index'))->with('message', self::COUPON_NOT_FOUND);
        }

        return view('backend.pages.coupon.coupon_edit', compact('coupon'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CouponUpRequest $request, $coupon_id)
    {

        $arr = $request->post();
        $name = ($request->has('name')) ? $arr['name'] : '';
        $code = ($request->has('code')) ? $arr['code'] : '';
        $quantity = ($request->has('quantity')) ? $arr['quantity'] : '';
        $start_coupon = ($request->has('start_coupon')) ? $arr['start_coupon'] : '';
        $end_coupon = ($request->has('end_coupon')) ? $arr['end_coupon'] : '';
        $condition = ($request->has('condition')) ? (int) $arr['condition'] : '0';
        $money = ($request->has('money')) ? $arr['money'] : '';
        $cou = CouponModel::find($coupon_id);
        if ($cou == null) {
            $request->session();
            Session::flash('iconMessage', 'info');

            return redirect(route('coupon.index'))->with('message', self::COUPON_NOT_FOUND);
        }
        $cou->coupon_name = $name;
        $cou->coupon_code = $code;
        $cou->coupon_quantity = $quantity;
        $time_start = date(self::DATE_FORMAT, strtotime($start_coupon));
        $time_end = date(self::DATE_FORMAT, strtotime($end_coupon));
        $cou->coupon_start = $time_start;
        $cou->coupon_end = $time_end;
        $cou->coupon_condition = $condition;
        $cou->coupon_value = $money;
        $cou->save();
        Session::flash('iconMessage', 'success');

        return redirect(route('coupon.index'))->with('message', 'Chỉnh sửa thành công');
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(Request $request,string $coupon_id)
    // {
    //     $cou = CouponModel::find($coupon_id);
    //     if ($cou==null) {
    //         $request->session();
    //         Session::flash('iconMessage', 'info');
    //         redirect()->back()->with('message', self::COUPON_NOT_FOUND);
    //     }
    //     $isUsedInOrders = $cou->Coupon()->exists();

    public function softDelete(Request $request, string $coupon_id)
    {
        $cou = CouponModel::find($coupon_id);
        if ($cou == null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            redirect()->back()->with('message', self::COUPON_NOT_FOUND);
        }
        $isUsedInOrders = $cou->Coupon()->exists();

        if ($isUsedInOrders) {
            Session::flash('iconMessage', 'info');

            return back()->with('message', 'Mã khuyến mãi đã được sử dụng trong các đơn hàng!');
        }
        $cou->delete();
        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Xóa thành công');
    }

    public function trashed(Request $request)
    {
        $perpages = 10;
        [$orderBy, $orderType] = $this->listingSort($request, CouponModel::class, 'coupon_id');
        $keyword = $request->input('keyword');
        $searchableFields = ['coupon_name', 'coupon_code'];

        $couponTrash = $this->performSearch(CouponModel::onlyTrashed()->orderBy($orderBy, $orderType), $keyword, $searchableFields)
            ->paginate($perpages)
            ->withQueryString();
        $today = Carbon::now()->format(self::DATE_FORMAT);

        return view('backend.pages.coupon.coupon_trash', compact('couponTrash', 'keyword', 'orderBy', 'orderType', 'today'));
    }

    public function restore($id)
    {
        $couponRe = CouponModel::withTrashed()->where('coupon_id', $id)->first();
        if ($couponRe) {
            $couponRe->restore();
            Session::flash('iconMessage', 'success');

            return back()->with('message', 'Khôi phục thành công');
        } else {
            return abort(404);
        }
    }

    public function restoreAll()
    {
        $trashedCoupons = CouponModel::onlyTrashed();
        if ($trashedCoupons->count() > 0) {
            $trashedCoupons->restore();
            Session::flash('iconMessage', 'success');

            return back()->with('message', 'Hoàn tác thành công!');
        } else {
            Session::flash('iconMessage', 'info');

            return back()->with('message', 'Không có dữ liệu trong thùng rác!');
        }
    }

    public function forceDelete($id)
    {
        $couponDe = CouponModel::withTrashed()->find($id);
        $isUsedInOrders = $couponDe->Coupon()->exists();

        if ($isUsedInOrders) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', 'Không thể xóa, mã khuyến mãi đã được sử dụng trong các đơn hàng.');
        }
        if ($couponDe) {
            $couponDe->forceDelete();
            Session::flash('iconMessage', 'success');

            return redirect()->back()->with('message', 'Xóa thành công');
        } else {
            return abort(404);
        }
    }

    public function exportcou_scv()
    {
        return Excel::download(new ExportCoupon, 'Mã giảm giá.xlsx');
    }

    public function deleteAll()
    {
        $trashAll = CouponModel::onlyTrashed()->get();
        if ($trashAll->count() > 0) {
            CouponModel::onlyTrashed()->forceDelete();
            Session::flash('iconMessage', 'success');

            return redirect()->back()->with('message', 'Xóa mã giảm giá thành công!');
        } else {
            Session::flash('iconMessage', 'info');

            return back()->with('message', 'Không có dữ liệu trong thùng rác!');
        }
    }

    public function sendCoupon(Request $request)
    {
        $coupon = CouponModel::find($request->input('couid'));
        $audience = (int) $request->input('coupon');

        if ($coupon && array_key_exists($audience, self::AUDIENCES)) {
            foreach ($this->activeCustomers(self::AUDIENCES[$audience])->get() as $customer) {
                Mail::to($customer->email)->send(new CouponMail($coupon));
            }
        }

        Session::flash('iconMessage', 'success');

        return redirect()->back()->with('message', 'Gửi mã giảm thành công!');
    }

    /**
     * @param  array{0: int, 1: ?int}|null  $orders  how many orders a customer must have placed, or null for everyone
     * @return Builder<UserModel>
     */
    private function activeCustomers(?array $orders)
    {
        $customers = UserModel::where('user_role', 0)->where('user_status', 1);

        if ($orders !== null) {
            [$fewest, $most] = $orders;
            $customers->has('Order', '>=', $fewest);

            if ($most !== null) {
                $customers->has('Order', '<=', $most);
            }
        }

        return $customers;
    }
}
