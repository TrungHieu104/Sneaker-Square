<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\Authuser\DeliveryRequest;
use App\Http\Requests\Frontend\DeliveryInfoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\DeliveryInfoModel as Info;

class DeliveryInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DeliveryRequest $request)
    {
        $found = false;
        $user_id = Auth::user()->user_id;
        $PersonInfo = Info::where('user_id', $user_id)->get();
        foreach ($PersonInfo as $PI) {
            if ($PI->info_default == 1)
                $found = true;
        }
        $Info = new Info();
        $Info->info_name = $request->info_name;
        $Info->info_phone = $request->info_phone;
        $Info->info_email = $request->info_email;
        $Info->info_address = $request->info_address;
        $Info->info_province = $request->info_province;
        $Info->info_district = $request->info_district;
        $Info->info_ward = $request->info_ward;
        $Info->info_delivery_fee = $request->infoFeeForm;
        $Info->user_id = $user_id;
        if (!$found) {
            $Info->info_default = 1;
        }
        $Info->save();
        Session::flash('iconMessage', 'success');
        return redirect()->back()->with('message', 'Thêm mới thành công!');
    }

    public function deliInfoSelected(Request $request)
    {
        $selectInfoDefault = $request->selectInfoDefault;
        $user_id = Auth::user()->user_id;
        $PersonInfo = Info::where('user_id', $user_id)->get();
        foreach ($PersonInfo as $PI) {
            if ($PI->info_default == 1) {
                $PI->info_default = 0;
                $PI->save();
            }
        }
        $dataInfoNew = Info::find($selectInfoDefault);
        $dataInfoNew->info_default = 1;
        $dataInfoNew->save();
        Session::flash('iconMessage', 'success');   
        return redirect()->back()->with('message', 'Đổi địa chỉ nhận hàng thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // // $user_id = Auth::user()->user_id;
        // $InfoDeli = Info::find($id);
        // Session::put('InfoDeli', $InfoDeli);
        // return redirect()->route('product.checkout');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DeliveryRequest $request, string $id)
    {
        $Info = Info::find($id);
        if ($Info == null) {
            Session::flash('iconMessage', 'error');
            return redirect()->back()->with('message', 'Không tồn tại địa chỉ này!');
        }
        $Info->info_name = $request->info_name;
        $Info->info_phone = $request->info_phone;
        $Info->info_email = $request->info_email;
        $Info->info_address = $request->info_address;
        $Info->info_province = $request->info_province;
        $Info->info_district = $request->info_district;
        $Info->info_ward = $request->info_ward;
        $Info->info_delivery_fee = $request->infoFeeFormUp;
        $Info->save();
        Session::flash('iconMessage', 'success');
        return redirect()->back()->with('message', 'Cập nhật địa chỉ nhận hàng thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $info = Info::find($id);

        if ($info == null) {
            Session::flash('iconMessage', 'error');
            return redirect()->back()->with('message', 'Không tồn tại địa chỉ này!');
        }

        $info->delete();

        Session::flash('iconMessage', 'success');
        return redirect()->back()->with('message', 'Xóa địa chỉ nhận hàng thành công!');
    }

    public function deliInfoDefault(Request $request)
    {
        $idinfo = $request['info_id'];
        $info = Info::find($idinfo);

        $info->info_default = $request['info_default'];

        $user_id = Auth::user()->user_id;
        $PersonInfo = Info::where('user_id', $user_id)->get();
        foreach ($PersonInfo as $PI) {
            if ($PI->info_default == 1) {
                $PI->info_default = 0;
                $PI->save();
            }
        }
        $info->save();
        Session::flash('iconMessage', 'success');
        return redirect()->back()->with('message', 'Đổi địa chỉ nhận hàng thành công!');
    }
}