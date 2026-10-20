<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\UserEventModel;
use App\Services\UserEventLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Takes what happens on a page without a request of its own, sent by
 * navigator.sendBeacon from frontend/js/track.js.
 *
 * Only the types listed here are accepted: everything else is recorded on the
 * server where it happens, and a browser must not be able to fake an add to
 * cart or a purchase.
 */
class EventBeaconController extends Controller
{
    private const TYPES = [UserEventModel::SELECT_VARIANT, UserEventModel::STOCK_OUT_HIT];

    public function store(Request $request, UserEventLog $events): Response
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(self::TYPES)],
            'pro_id' => ['required', 'integer', 'exists:products,pro_id'],
            'size_id' => ['nullable', 'integer', 'exists:size,size_id'],
            'color_id' => ['nullable', 'integer', 'exists:color,color_id'],
        ]);

        $events->record($data['type'], [
            'pro_id' => (int) $data['pro_id'],
            'size_id' => $data['size_id'] ?? null,
            'color_id' => $data['color_id'] ?? null,
        ]);

        return response()->noContent();
    }
}
