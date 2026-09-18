<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Shipping\ShippingCarrier;
use App\Services\Shipping\ShippingUnavailable;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * The address pickers and the shipping price, proxied.
 *
 * The browser used to call GHN itself, which meant the shop's API token was
 * handed to every visitor — enough to create real shipments and read every
 * order on the account — and the fee came back as a hidden form field the
 * server then believed. Both halves now stay here.
 */
class ShippingController extends Controller
{
    public function __construct(
        private ShippingCarrier $carrier,
        private ShippingService $shipping,
    ) {}

    public function provinces(): JsonResponse
    {
        return $this->answer(fn () => ['provinces' => $this->carrier->provinces()]);
    }

    public function districts(Request $request): JsonResponse
    {
        $provinceId = (int) $request->input('province_id');

        if ($provinceId <= 0) {
            return response()->json(['message' => 'Thiếu tỉnh/thành phố.'], 422);
        }

        return $this->answer(fn () => ['districts' => $this->carrier->districts($provinceId)]);
    }

    public function wards(Request $request): JsonResponse
    {
        $districtId = (int) $request->input('district_id');

        if ($districtId <= 0) {
            return response()->json(['message' => 'Thiếu quận/huyện.'], 422);
        }

        return $this->answer(fn () => ['wards' => $this->carrier->wards($districtId)]);
    }

    /**
     * Priced against the cart in the session, never against anything the form
     * says the parcel weighs.
     */
    public function quote(Request $request): JsonResponse
    {
        $districtId = (int) $request->input('district_id');
        $wardCode = trim((string) $request->input('ward_code'));

        if ($districtId <= 0 || $wardCode === '') {
            return response()->json(['message' => 'Vui lòng chọn đầy đủ quận/huyện và phường/xã.'], 422);
        }

        return $this->answer(fn () => $this->shipping
            ->quoteForCart(Session::get('cart', []), $districtId, $wardCode)
            ->toArray());
    }

    /**
     * @param  callable(): array<string, mixed>  $work
     */
    private function answer(callable $work): JsonResponse
    {
        try {
            return response()->json($work());
        } catch (ShippingUnavailable $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }
    }
}
