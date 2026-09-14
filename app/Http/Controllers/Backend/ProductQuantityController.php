<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Requests\Backend\ProductQuantityRequest;
use App\Http\Requests\Backend\ProductVariantPriceRequest;
use App\Http\Requests\Backend\StockRestockRequest;
use App\Http\Requests\Backend\StockVariantRequest;
use App\Http\Requests\Backend\ColorRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use App\Models\ProductModel as Product;
use App\Models\ColorModel as Color;
use App\Models\SizeModel as Size;
use App\Models\ProductQuantityModel as Quantity;

class ProductQuantityController extends Controller
{
    public function __construct(Request $request)
    {
        $keyword = $request->input('keyword');
        View::share(compact('keyword'));
    }

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
        $allProducts = Product::orderBy('pro_name','asc')->get();
        $allColor = Color::all();
        $allSize = Size::orderBy('size', 'asc')->get();
        $today = today()->toDateString();

        return view('backend.pages.product.stock.product_stock_create', [
            'allProducts' => $allProducts,
            'allColor' => $allColor,
            'allSize' => $allSize,
            'today' => $today,
            'currentPrices' => $this->currentPrices($allProducts),
        ]);
    }

    /**
     * What every product and every colour of it sells for right now, so the form's
     * price boxes can show the figure a blank box stands in for.
     *
     * Prices are entered per colour, so one row of a colour speaks for all its
     * sizes.
     *
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @return array<string, array<string, mixed>>
     */
    private function currentPrices($products): array
    {
        $byId = $products->keyBy('pro_id');
        $prices = ['product' => [], 'variant' => []];

        foreach ($products as $product) {
            $prices['product'][$product->pro_id] = [
                'price' => (int) $product->pro_price,
                'sale' => (int) $product->pro_price_sale,
                'capital' => (int) $product->capital_price,
            ];
        }

        $variants = Quantity::select('pro_id', 'color_id', 'pro_price', 'pro_price_sale', 'capital_price')->get();

        foreach ($variants as $variant) {
            $product = $byId[$variant->pro_id] ?? null;
            $key = $variant->pro_id . '-' . ($variant->color_id ?? 'none');

            if (! $product || isset($prices['variant'][$key])) {
                continue;
            }

            $prices['variant'][$key] = [
                'price' => $variant->listPrice($product),
                'sale' => $variant->salePrice($product),
                'capital' => $variant->capitalPrice($product),
                'own' => $variant->pro_price !== null
                    || $variant->pro_price_sale !== null
                    || $variant->capital_price !== null,
            ];
        }

        return $prices;
    }

    /**
     * Store new color
     */
    public function storeNewColor(ColorRequest $request) {
        $input = $request->post();
        $color = ($request->has('color'))? $input['color']:"";
        $color_vn = ($request->has('color_vn'))? mb_convert_case($input['color_vn'], MB_CASE_TITLE, "UTF-8"):"";

        $newColor = new Color;
        $newColor->color = $color;
        $newColor->color_vn = $color_vn;
        $newColor->save();

        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Thêm màu thành công!');
    }

    /**
     * Update color
     */
    public function updateColor(ColorRequest $request, string $colorId)
    {
        $color = Color::find($colorId);

        if (! $color) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', 'Không tìm thấy màu này!');
        }

        $color->color = (string) $request->input('color');
        $color->color_vn = (string) $request->input('color_vn');
        $color->save();

        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Cập nhật màu sắc thành công!');
    }

    /**
     * Delete color
     */
    public function deleteColor(Request $request, string $colorId)
    {
        $color = Color::find($colorId);
        if ($color == null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            redirect()->back()->with('message', 'Không tồn tại màu sắc.');
        }
        $color->delete();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Xóa màu sắc thành công!');
    }

    /**
     * Receives stock. Prices are entered per colour rather than per size, so every
     * size of a colour is stocked at the same price and cannot drift apart.
     */
    public function store(ProductQuantityRequest $request)
    {
        $proId = $request->input('pro_id');
        $quantityDate = $request->input('quantity_date');

        if (! $request->hasVariants()) {
            // Matched on the null colour and size, not on pro_id alone, or a
            // product that also has coloured variants lands this delivery on
            // whichever coloured row came back first.
            $variant = Quantity::where('pro_id', $proId)
                ->whereNull('size_id')
                ->whereNull('color_id')
                ->first()
                ?? $this->newVariant($proId, null, null);

            $this->applyStock($variant, $quantityDate, (int) $request->input('quantityOthers'), [
                'pro_price' => $request->input('priceOthers'),
                'pro_price_sale' => $request->input('priceSaleOthers'),
                'capital_price' => $request->input('capitalPriceOthers'),
            ]);

            Session::flash('iconMessage', 'success');

            return back()->with('message', 'Nhập hàng thành công!');
        }

        $checkedColors = $request->input('color_id', []);
        $checkedSizes = $request->input('size_id', []);
        $quantities = $request->input('quantityColorAndSize', []);

        foreach ($checkedColors as $colorId) {
            if (! array_key_exists($colorId, $quantities)) {
                continue;
            }

            $prices = [
                'pro_price' => $request->input('priceColor.' . $colorId),
                'pro_price_sale' => $request->input('priceSaleColor.' . $colorId),
                'capital_price' => $request->input('capitalPriceColor.' . $colorId),
            ];

            // With no sizes ticked, the colour alone is the variant.
            foreach ($checkedSizes ?: [null] as $sizeId) {
                $variant = Quantity::where('pro_id', $proId)
                    ->where('size_id', $sizeId)
                    ->where('color_id', $colorId)
                    ->first()
                    ?? $this->newVariant($proId, $sizeId, $colorId);

                $this->applyStock($variant, $quantityDate, (int) $quantities[$colorId], $prices);
            }
        }

        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Nhập hàng thành công');
    }

    private function newVariant(string $proId, $sizeId, $colorId): Quantity
    {
        $variant = new Quantity;
        $variant->pro_id = $proId;
        $variant->size_id = $sizeId;
        $variant->color_id = $colorId;
        $variant->quantity = 0;

        return $variant;
    }

    /**
     * Adds the delivery to a variant and records the prices it came in at.
     *
     * A blank price box leaves the variant's price alone. Clearing a price is done
     * on the stock screen, where blank means "follow the product" instead.
     *
     * @param  array<string, mixed>  $prices
     */
    private function applyStock(Quantity $variant, string $quantityDate, int $quantity, array $prices): void
    {
        $variant->quantity_date = $quantityDate;
        $variant->quantity += $quantity;

        foreach ($prices as $column => $value) {
            if ($value !== null && $value !== '') {
                $variant->{$column} = (int) $value;
            }
        }

        $variant->save();
    }

    /**
     * Changes the price of a variant already in stock.
     *
     * A blank box clears the variant's own price — the opposite of what blank means
     * when receiving stock.
     */
    public function updatePrice(ProductVariantPriceRequest $request, string $quantityId)
    {
        $variant = Quantity::find($quantityId);

        if (! $variant) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', 'Không tìm thấy sản phẩm này trong kho!');
        }

        foreach (['pro_price', 'pro_price_sale', 'capital_price'] as $column) {
            $value = $request->input($column);
            $variant->{$column} = ($value === null || $value === '') ? null : (int) $value;
        }

        $variant->save();

        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Cập nhật giá thành công!');
    }

    /**
     * Adds one variant to a product that already has stock.
     *
     * The intake form stocks the product of every size and colour ticked at once,
     * which is the wrong shape for filling a single gap — one size, one colour.
     */
    public function storeVariant(StockVariantRequest $request, string $proSlug)
    {
        $proId = Product::where('pro_slug', $proSlug)->value('pro_id');

        if ($proId == null) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', 'Sản phẩm không tồn tại!');
        }

        $variant = $this->newVariant($proId, $request->identifier('size_id'), $request->identifier('color_id'));
        $variant->quantity_date = $request->input('quantity_date');
        $variant->quantity = (int) $request->input('quantity');

        foreach (['pro_price', 'pro_price_sale', 'capital_price'] as $column) {
            $variant->{$column} = $request->identifier($column);
        }

        $variant->save();

        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Thêm biến thể thành công!');
    }

    /**
     * Adds a delivery to one variant already on the shelf.
     *
     * Prices are not touched: the row has its own form for those.
     */
    public function restock(StockRestockRequest $request, string $quantityId)
    {
        $variant = Quantity::find($quantityId);

        if (! $variant) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', 'Không tìm thấy sản phẩm này trong kho!');
        }

        // Incremented in the statement rather than read and written back, so two
        // deliveries booked at once cannot lose one another.
        $variant->increment('quantity', (int) $request->input('quantity'), [
            'quantity_date' => $request->input('quantity_date'),
        ]);

        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Nhập thêm hàng thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $proSlug = '')
    {
        $proId = Product::where('pro_slug', $proSlug)->value('pro_id');

        if($proId == null) {
            Session::flash('iconMessage', 'info');
            return redirect()->route('product.index')->with('message', 'Sản phẩm không tồn tại');
        }

        $rows = Quantity::where('pro_id', $proId);

        // The redirect below has to fire on a product with no stock at all, never
        // on a filter that simply matched nothing.
        if (! (clone $rows)->exists()) {
            Session::flash('iconMessage', 'info');

            return redirect(route('stock.create'))->with('message', 'Chưa có hàng trong kho!');
        }

        $filters = [
            'size_id' => $request->input('size_id'),
            'color_id' => $request->input('color_id'),
            'price' => $request->input('price'),
            'stock' => $request->input('stock'),
        ];

        $allQuantity = $this->filterStock(
                                Quantity::with(['getProducts', 'getSize', 'getColor'])->where('pro_id', $proId),
                                $filters,
                            )
                                -> orderBy('quantity_date', 'desc')
                                -> orderBy('color_id', 'asc')
                                -> orderBy('size_id', 'asc')
                                -> paginate(20)
                                -> withQueryString();

        return view('backend.pages.product.stock.product_stock', [
            'allQuantity' => $allQuantity,
            'product' => Product::find($proId),
            'filters' => $filters,
            'today' => today()->toDateString(),
            // The add form offers every size and colour, not only the ones already
            // stocked — a variant that exists is not one you can add.
            'allSize' => Size::orderBy('size', 'asc')->get(),
            'allColor' => Color::orderBy('color_vn', 'asc')->get(),
            // Which columns the table draws is a property of the product, not of
            // whatever the filter left behind, or the table would change shape
            // under the person using it.
            'hasSize' => (clone $rows)->whereNotNull('size_id')->exists(),
            'hasColor' => (clone $rows)->whereNotNull('color_id')->exists(),
            'sizeOptions' => Size::whereIn('size_id', (clone $rows)->distinct()->pluck('size_id'))
                                ->orderBy('size', 'asc')->get(),
            'colorOptions' => Color::whereIn('color_id', (clone $rows)->distinct()->pluck('color_id'))
                                ->orderBy('color_vn', 'asc')->get(),
        ]);
    }

    /**
     * Narrows the stock listing.
     *
     * "Có giá riêng" asks about the variant's own columns, not the price it ends up
     * selling at: a variant priced the same as its product still counts, because
     * clearing it would matter the next time the product's price moves.
     *
     * @param  array<string, mixed>  $filters
     */
    private function filterStock($query, array $filters)
    {
        if ($filters['size_id'] !== null && $filters['size_id'] !== '') {
            $query->where('size_id', $filters['size_id']);
        }

        if ($filters['color_id'] !== null && $filters['color_id'] !== '') {
            $query->where('color_id', $filters['color_id']);
        }

        if ($filters['price'] === 'own') {
            $query->where(function ($sub) {
                $sub->whereNotNull('pro_price')
                    ->orWhereNotNull('pro_price_sale')
                    ->orWhereNotNull('capital_price');
            });
        }

        if ($filters['price'] === 'inherited') {
            $query->whereNull('pro_price')->whereNull('pro_price_sale')->whereNull('capital_price');
        }

        if ($filters['stock'] === 'in') {
            $query->where('quantity', '>', 0);
        }

        if ($filters['stock'] === 'out') {
            $query->where('quantity', 0);
        }

        return $query;
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $quantityId)
    {
        $quan = Quantity::find($quantityId);
        if ($quan == null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            back()->with('message', 'Không tồn tại sản phẩm này trong kho!');
        }
        $quan->delete();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Xóa sản phẩm trong kho thành công!');
    }
}    