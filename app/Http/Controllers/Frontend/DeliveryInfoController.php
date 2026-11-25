<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\Authuser\DeliveryRequest;
use App\Models\DeliveryInfoModel as Info;
use App\Services\Shipping\Shipment;
use App\Services\Shipping\ShippingCarrier;
use App\Services\Shipping\ShippingUnavailable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class DeliveryInfoController extends Controller
{
    private const NOT_FOUND = 'Không tồn tại địa chỉ này!';

    public function __construct(private ShippingCarrier $carrier) {}

    /**
     * What one parcel to this address costs today.
     *
     * Stored so a listing can show a number without calling GHN once per row.
     * It is an indication only: the fee charged is quoted again at checkout,
     * against the cart that is actually being sent.
     */
    private function indicativeFee(int $districtId, string $wardCode): int
    {
        try {
            return $this->carrier->quote(new Shipment(
                toDistrictId: $districtId,
                toWardCode: $wardCode,
                weight: 1200,
                insuranceValue: 0,
            ))->fee;
        } catch (ShippingUnavailable $e) {
            return 0;
        }
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
            if ($PI->info_default == 1) {
                $found = true;
            }
        }
        $Info = new Info;
        $Info->info_name = $request->info_name;
        $Info->info_phone = $request->info_phone;
        $Info->info_email = $request->info_email;
        $Info->info_address = $request->info_address;
        $Info->info_province = $request->info_province;
        $Info->info_district = $request->info_district;
        $Info->info_ward = $request->info_ward;
        $Info->info_district_id = (int) $request->info_district_id;
        $Info->info_ward_code = (string) $request->info_ward_code;
        $Info->info_delivery_fee = $this->indicativeFee((int) $request->info_district_id, (string) $request->info_ward_code);
        $Info->user_id = $user_id;
        if (! $found) {
            $Info->info_default = 1;
        }
        $Info->save();
        Session::flash('iconMessage', 'success');

        return redirect()->back()->with('message', 'Thêm mới thành công!');
    }

    /**
     * The signed-in customer's own address, or null for anyone else's.
     */
    private function ownAddress(mixed $id): ?Info
    {
        if (! is_scalar($id)) {
            return null;
        }

        return Info::where('user_id', Auth::id())->find($id);
    }

    /**
     * Drops the default flag from the customer's other addresses. Done as one
     * query so the chosen address, already loaded, is never touched behind it.
     */
    private function clearOtherDefaults(Info $keep): void
    {
        Info::where('user_id', Auth::id())->whereKeyNot($keep->getKey())->update(['info_default' => 0]);
    }

    private function missingAddress()
    {
        Session::flash('iconMessage', 'error');

        return redirect()->back()->with('message', self::NOT_FOUND);
    }

    public function deliInfoSelected(Request $request)
    {
        $dataInfoNew = $this->ownAddress($request->selectInfoDefault);

        if ($dataInfoNew === null) {
            return $this->missingAddress();
        }

        $this->clearOtherDefaults($dataInfoNew);
        $dataInfoNew->info_default = 1;
        $dataInfoNew->save();
        Session::flash('iconMessage', 'success');

        return redirect()->back()->with('message', 'Đổi địa chỉ nhận hàng thành công!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DeliveryRequest $request, string $id)
    {
        $Info = $this->ownAddress($id);
        if ($Info === null) {
            return $this->missingAddress();
        }
        $Info->info_name = $request->info_name;
        $Info->info_phone = $request->info_phone;
        $Info->info_email = $request->info_email;
        $Info->info_address = $request->info_address;
        $Info->info_province = $request->info_province;
        $Info->info_district = $request->info_district;
        $Info->info_ward = $request->info_ward;
        $Info->info_district_id = (int) $request->info_district_id;
        $Info->info_ward_code = (string) $request->info_ward_code;
        $Info->info_delivery_fee = $this->indicativeFee((int) $request->info_district_id, (string) $request->info_ward_code);
        $Info->save();
        Session::flash('iconMessage', 'success');

        return redirect()->back()->with('message', 'Cập nhật địa chỉ nhận hàng thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $info = $this->ownAddress($id);

        if ($info === null) {
            return $this->missingAddress();
        }

        $info->delete();

        Session::flash('iconMessage', 'success');

        return redirect()->back()->with('message', 'Xóa địa chỉ nhận hàng thành công!');
    }

    public function deliInfoDefault(Request $request)
    {
        $info = $this->ownAddress($request['info_id']);

        if ($info === null) {
            return $this->missingAddress();
        }

        $this->clearOtherDefaults($info);
        $info->info_default = $request['info_default'];
        $info->save();
        Session::flash('iconMessage', 'success');

        return redirect()->back()->with('message', 'Đổi địa chỉ nhận hàng thành công!');
    }
}
