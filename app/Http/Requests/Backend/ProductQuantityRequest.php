<?php

namespace App\Http\Requests\Backend;

use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class ProductQuantityRequest extends FormRequest
{
    private const NO_PRODUCT = 'Sản phẩm không tồn tại!';

    private const NO_SIZE = 'Size không tồn tại!';

    private const NO_COLOR = 'Màu không tồn tại!';

    private const QUANTITY_REQUIRED = 'Vui lòng nhập số lượng!';

    private const QUANTITY_INTEGER = 'Số lượng phải là số nguyên!';

    private const QUANTITY_POSITIVE = 'Vui lòng nhập số lượng lớn hơn 0!';

    private const QUANTITY_MAX = 'Mỗi lần nhập tối đa 999.999!';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Which quantity field is required depends on the kind of product: one with
     * colours and sizes carries a quantity per variant, a plain one a single
     * number.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'pro_id' => ['required', 'integer', Rule::exists('products', 'pro_id')->whereNull('deleted_at')],
            'quantity_date' => ['required', 'date'],
            'size_id' => ['nullable', 'array'],
            'size_id.*' => ['integer', 'exists:size,size_id'],
            'color_id' => ['nullable', 'array'],
            'color_id.*' => ['integer', 'exists:color,color_id'],
        ];
        $quantity = ['integer', 'gt:0', 'max:'.ProductQuantityModel::MAX_STOCK_MOVE];

        $price = ['nullable', 'numeric', 'integer', 'min:1', 'max:9999999999'];
        $salePrice = ['nullable', 'numeric', 'integer', 'min:0', 'max:9999999999'];

        if ($this->hasVariants()) {
            return $rules + [
                'quantityColorAndSize' => ['required', 'array'],
                'quantityColorAndSize.*' => $quantity,
                'priceColor.*' => $price,
                'priceSaleColor.*' => $salePrice,
                'capitalPriceColor.*' => $price,
            ];
        }

        return $rules + [
            'quantityOthers' => ['required', ...$quantity],
            'priceOthers' => $price,
            'priceSaleOthers' => $salePrice,
            'capitalPriceOthers' => $price,
        ];
    }

    /**
     * Selling above cost, sale below selling.
     *
     * Not expressible as `gt:`/`lt:` like ProductRequest, because every colour is
     * its own trio of boxes and a blank box is compared using the product's price.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $proId = $this->input('pro_id');
                $product = is_scalar($proId) ? ProductModel::find($proId) : null;

                if (! $product) {
                    return;
                }

                if (! $this->hasVariants()) {
                    $this->checkPriceTrio(
                        $validator,
                        $product,
                        'priceOthers',
                        'priceSaleOthers',
                        'capitalPriceOthers',
                        ProductQuantityModel::where('pro_id', $product->pro_id)
                            ->whereNull('size_id')
                            ->whereNull('color_id')
                            ->get(),
                    );

                    return;
                }

                foreach ((array) $this->input('color_id', []) as $colorId) {
                    if (! is_scalar($colorId)) {
                        continue;
                    }

                    $this->checkPriceTrio(
                        $validator,
                        $product,
                        'priceColor.'.$colorId,
                        'priceSaleColor.'.$colorId,
                        'capitalPriceColor.'.$colorId,
                        $this->existingVariants($product, $colorId),
                    );
                }
            },
        ];
    }

    /**
     * Checks the three prices the variant will hold once saved, not the three that
     * were typed: a blank box keeps whatever the variant already has.
     *
     * Every row the delivery will touch is checked, not one of them. Asking a
     * single row let a cost price through that put a different size of the same
     * colour under water — which row answered depended on the order the database
     * happened to return.
     *
     * @param  Collection<int, ProductQuantityModel>  $variants
     */
    private function checkPriceTrio(
        Validator $validator,
        ProductModel $product,
        string $priceKey,
        string $saleKey,
        string $capitalKey,
        Collection $variants,
    ): void {
        $rows = $variants->isEmpty() ? collect([new ProductQuantityModel]) : $variants;

        $belowCost = false;
        $saleTooHigh = false;

        foreach ($rows as $variant) {
            $merged = clone $variant;

            foreach ([$priceKey => 'pro_price', $saleKey => 'pro_price_sale', $capitalKey => 'capital_price'] as $key => $column) {
                if ($this->filled($key)) {
                    $merged->{$column} = (int) $this->input($key);
                }
            }

            $listPrice = $merged->listPrice($product);
            $salePrice = $merged->salePrice($product);
            $capitalPrice = $merged->capitalPrice($product);

            $belowCost = $belowCost || $listPrice <= $capitalPrice;
            $saleTooHigh = $saleTooHigh || ($salePrice != 0 && $salePrice >= $listPrice);
        }

        if ($belowCost) {
            $validator->errors()->add($priceKey, 'Giá bán phải lớn hơn giá vốn!');
        }

        if ($saleTooHigh) {
            $validator->errors()->add($saleKey, 'Giá giảm phải thấp hơn giá bán!');
        }
    }

    /**
     * Every row of that colour, because prices are entered per colour and the
     * delivery merges into all of them at once.
     *
     * @return Collection<int, ProductQuantityModel>
     */
    private function existingVariants(ProductModel $product, $colorId): Collection
    {
        return ProductQuantityModel::where('pro_id', $product->pro_id)
            ->where('color_id', $colorId)
            ->get();
    }

    /**
     * `pro_type` is 0 for a product sold in colours and sizes, 1 for one that
     * is not.
     */
    public function hasVariants(): bool
    {
        return (int) $this->input('pro_type', 0) === 0;
    }

    public function messages()
    {
        return [
            'pro_id.required' => 'Vui lòng chọn sản phẩm!',
            'pro_id.integer' => self::NO_PRODUCT,
            'pro_id.exists' => self::NO_PRODUCT,

            'quantity_date.required' => 'Vui lòng nhập ngày nhập hàng!',
            'quantity_date.date' => 'Ngày nhập hàng không hợp lệ!',

            'size_id.array' => self::NO_SIZE,
            'size_id.*.integer' => self::NO_SIZE,
            'size_id.*.exists' => self::NO_SIZE,
            'color_id.array' => self::NO_COLOR,
            'color_id.*.integer' => self::NO_COLOR,
            'color_id.*.exists' => self::NO_COLOR,

            'quantityColorAndSize.required' => self::QUANTITY_REQUIRED,
            'quantityColorAndSize.array' => self::QUANTITY_REQUIRED,
            'quantityColorAndSize.*.integer' => self::QUANTITY_INTEGER,
            'quantityColorAndSize.*.gt' => self::QUANTITY_POSITIVE,
            'quantityColorAndSize.*.max' => self::QUANTITY_MAX,

            'quantityOthers.required' => self::QUANTITY_REQUIRED,
            'quantityOthers.integer' => self::QUANTITY_INTEGER,
            'quantityOthers.gt' => self::QUANTITY_POSITIVE,
            'quantityOthers.max' => self::QUANTITY_MAX,

            'priceColor.*.integer' => 'Giá bán phải là số nguyên!',
            'priceColor.*.min' => 'Giá bán phải lớn hơn 0!',
            'priceSaleColor.*.integer' => 'Giá giảm phải là số nguyên!',
            'priceSaleColor.*.min' => 'Giá giảm không được là số âm!',
            'capitalPriceColor.*.integer' => 'Giá vốn phải là số nguyên!',
            'capitalPriceColor.*.min' => 'Giá vốn phải lớn hơn 0!',

            'priceOthers.integer' => 'Giá bán phải là số nguyên!',
            'priceOthers.min' => 'Giá bán phải lớn hơn 0!',
            'priceSaleOthers.integer' => 'Giá giảm phải là số nguyên!',
            'priceSaleOthers.min' => 'Giá giảm không được là số âm!',
            'capitalPriceOthers.integer' => 'Giá vốn phải là số nguyên!',
            'capitalPriceOthers.min' => 'Giá vốn phải lớn hơn 0!',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        Session::flash('iconMessage', 'error');
        Session::flash('message', 'Nhập hàng thất bại!');

        parent::failedValidation($validator);
    }
}
