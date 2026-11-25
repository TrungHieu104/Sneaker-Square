<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\SharesStorefrontLayout;
use App\Services\FootSize;

class SizeChartController extends Controller
{
    use SharesStorefrontLayout;

    public function index(FootSize $sizes)
    {
        $this->shareStorefrontLayout();

        return view('frontend.pages.foot.size_chart', [
            'ranges' => $sizes->ranges(),
        ]);
    }
}
