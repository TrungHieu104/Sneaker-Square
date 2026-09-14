<?php

namespace App\Http\Requests\Backend;

use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ProductQuantityRequest extends FormRequest
{
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'pro_id' => ['required'],
            'quantity_date' => ['required'],
            'size_id' => ['nullable'],
            'color_id' => ['nullable'],
        ];

        $price = ['nullable', 'numeric', 'integer', 'min:1', 'max:9999999999'];
        $salePrice = ['nullable', 'numeric', 'integer', 'min:0', 'max:9999999999'];

        if ($this->hasVariants()) {
            return $rules + [
                'quantityColorAndSize' => ['required'],
                'quantityColorAndSize.*' => ['gt:0'],
                'priceColor.*' => $price,
                'priceSaleColor.*' => $salePrice,
                'capitalPriceColor.*' => $price,
            ];
        }

        return $rules + [
            'quantityOthers' => ['required', 'gt:0'],
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
                $product = ProductModel::find($this->input('pro_id'));

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
                            ->first(),
                    );

                    return;
                }

                foreach ($this->input('color_id', []) as $colorId) {
                    $this->checkPriceTrio(
                        $validator,
                        $product,
                        'priceColor.' . $colorId,
                        'priceSaleColor.' . $colorId,
                        'capitalPriceColor.' . $colorId,
                        $this->existingVariant($product, $colorId),
                    );
                }
            },
        ];
    }

    /**
     * Checks the three prices the variant will hold once saved, not the three that
     * were typed: a blank box keeps whatever the variant already has.
     */
    private function checkPriceTrio(
        Validator $validator,
        ProductModel $product,
        string $priceKey,
        string $saleKey,
        string $capitalKey,
        ?ProductQuantityModel $variant = null,
    ): void {
        $merged = $variant ? clone $variant : new ProductQuantityModel;

        foreach ([$priceKey => 'pro_price', $saleKey => 'pro_price_sale', $capitalKey => 'capital_price'] as $key => $column) {
            if ($this->filled($key)) {
                $merged->{$column} = (int) $this->input($key);
            }
        }

        $listPrice = $merged->listPrice($product);
        $salePrice = $merged->salePrice($product);
        $capitalPrice = $merged->capitalPrice($product);

        if ($listPrice <= $capitalPrice) {
            $validator->errors()->add($priceKey, 'Giá bán phải lớn hơn giá vốn!');
        }

        if ($salePrice != 0 && $salePrice >= $listPrice) {
            $validator->errors()->add($saleKey, 'Giá giảm phải thấp hơn giá bán!');
        }
    }

    /**
     * Prices are entered per colour, so any one of that colour's rows carries the
     * prices this delivery is about to merge into.
     */
    private function existingVariant(ProductModel $product, $colorId): ?ProductQuantityModel
    {
        return ProductQuantityModel::where('pro_id', $product->pro_id)
            ->where('color_id', $colorId)
            ->first();
    }

    /**
     * `pro_type` is 0 for a product sold in colours and sizes, 1 for one that
     * is not.
     */
    public function hasVariants(): bool
    {
        return (int) $this->input('pro_type', 0) === 0;
    }

    public function messages() {
        return [
            'pro_id.required' => 'Vui lòng chọn sản phẩm!',

            'quantity_date.required' => 'Vui lòng nhập ngày nhập hàng!',

            'quantityColorAndSize.required' => 'Vui lòng nhập số lượng!',
            'quantityColorAndSize.*.gt' => 'Vui lòng nhập số lượng lớn hơn 0!',

            'quantityOthers.required' => 'Vui lòng nhập số lượng!',
            'quantityOthers.gt' => 'Vui lòng nhập số lượng lớn hơn 0!',

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
        \Illuminate\Support\Facades\Session::flash('iconMessage', 'error');
        \Illuminate\Support\Facades\Session::flash('message', 'Nhập hàng thất bại!');

        parent::failedValidation($validator);
    }
}
