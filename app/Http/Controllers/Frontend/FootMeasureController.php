<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\SharesStorefrontLayout;
use App\Models\ProductModel;
use App\Services\FootSize;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The photo never reaches the server: it is measured in the browser, and only
 * the size the customer settles on is posted here.
 */
class FootMeasureController extends Controller
{
    use SharesStorefrontLayout;

    private const SESSION_KEY = 'shoe_size';

    public function index(Request $request, FootSize $sizes)
    {
        $this->shareStorefrontLayout();

        $product = $request->filled('sp')
            ? ProductModel::where('pro_slug', $request->query('sp'))->where('pro_hidden', 1)->first()
            : null;

        return view('frontend.pages.foot.measure', [
            'product' => $product,
            'shoeSize' => $this->shoeSize(),
            'sizeChart' => $sizes->chart(),
        ]);
    }

    public function show(): JsonResponse
    {
        return response()->json(['size' => $this->shoeSize()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'size' => ['required', 'integer', 'between:'.FootSize::MIN_SIZE.','.FootSize::MAX_SIZE],
        ], [
            'size.required' => 'Vui lòng chọn size giày của bạn.',
            'size.integer' => 'Size giày không hợp lệ.',
            'size.between' => 'Size giày phải từ '.FootSize::MIN_SIZE.' đến '.FootSize::MAX_SIZE.'.',
        ]);

        $size = (int) $data['size'];

        if ($user = Auth::user()) {
            $user->shoe_size = $size;
            $user->save();
        }

        // Kept for a guest too, and for the moment a guest logs in before the
        // account has a size of its own.
        $request->session()->put(self::SESSION_KEY, $size);

        return response()->json(['size' => $size]);
    }

    private function shoeSize(): ?int
    {
        $size = Auth::user()?->shoe_size ?? session(self::SESSION_KEY);

        return $size === null ? null : (int) $size;
    }
}
