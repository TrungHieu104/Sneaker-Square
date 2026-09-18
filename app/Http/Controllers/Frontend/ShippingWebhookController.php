<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Shipping\ShipmentTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Where GHN reports what happened to a parcel.
 *
 * GHN signs nothing, so the secret is the URL itself: the path carries a token
 * only GHN and this application know. That is weaker than an HMAC, and it is
 * what the carrier offers.
 *
 * The answer is 200 in almost every case on purpose. GHN retries a non-200 ten
 * times, five seconds apart, so returning an error for a callback that will
 * never succeed — an unknown order, a malformed body — buys ten more copies of
 * the same failure and nothing else.
 */
class ShippingWebhookController extends Controller
{
    public function __construct(private ShipmentTracker $tracker) {}

    public function ghn(Request $request, string $token): JsonResponse
    {
        $expected = (string) config('services.ghn.webhook_token');

        if ($expected === '' || ! hash_equals($expected, $token)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $payload = $request->json()->all();

        if (! is_array($payload) || $payload === []) {
            $payload = $request->all();
        }

        try {
            $result = $this->tracker->record($payload);
        } catch (Throwable $e) {
            // A crash is the one case worth a retry: the event is real and the
            // failure is ours.
            Log::error('GHN webhook failed', [
                'message' => $e->getMessage(),
                'order_code' => $payload['OrderCode'] ?? null,
            ]);

            return response()->json(['message' => 'Lỗi xử lý'], 500);
        }

        if (! $result['order']) {
            Log::info('GHN webhook for an unknown order', [
                'OrderCode' => $payload['OrderCode'] ?? null,
                'ClientOrderCode' => $payload['ClientOrderCode'] ?? null,
                'Status' => $payload['Status'] ?? null,
            ]);

            return response()->json(['message' => 'Không khớp đơn hàng nào'], 200);
        }

        return response()->json([
            'message' => 'ok',
            'order_code' => $result['order']->order_code,
            'recorded' => $result['recorded'],
        ]);
    }
}
