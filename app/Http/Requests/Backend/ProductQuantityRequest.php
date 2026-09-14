<?php

namespace App\Http\Requests\Backend;

use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

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
     * @return array<string, ValidationRule|array<mixed>|string>
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
                            ->get(),
                    );

                    return;
                }

                foreach ($this->input('color_id', []) as $colorId) {
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
        Session::flash('iconMessage', 'error');
        Session::flash('message', 'Nhập hàng thất bại!');

        parent::failedValidation($validator);
    }
}
