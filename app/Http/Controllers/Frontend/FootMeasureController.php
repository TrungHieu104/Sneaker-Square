<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\SharesStorefrontLayout;
use App\Models\ProductModel;
use App\Services\FootSize;
use App\Services\ShoeSizeMemory;
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

    public function __construct(private readonly ShoeSizeMemory $memory) {}

    public function index(Request $request, FootSize $sizes)
    {
        $this->shareStorefrontLayout();

        $product = $request->filled('sp')
            ? ProductModel::where('pro_slug', $request->query('sp'))->where('pro_hidden', 1)->first()
            : null;

        return view('frontend.pages.foot.measure', [
            'product' => $product,
            'shoeSize' => $this->memory->measured(),
            'sizeChart' => $sizes->chart(),
        ]);
    }

    /**
     * The size the product page picks in advance: measured if there is one,
     * otherwise the one this shopper last bought or put in the cart.
     */
    public function show(): JsonResponse
    {
        return response()->json($this->memory->remembered());
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
        $request->session()->put(ShoeSizeMemory::SESSION_KEY, $size);

        return response()->json(['size' => $size]);
    }
}
