<?php

namespace App\Http\Requests\Backend;

use App\Models\ProductQuantityModel;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Rules for the form that edits one variant's prices on the stock screen.
 *
 * A blank box here clears the variant's own price, so the trio is checked against
 * the product's — the prices the variant will actually sell at once saved.
 */
class ProductVariantPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pro_price' => ['nullable', 'numeric', 'integer', 'min:1', 'max:9999999999'],
            'pro_price_sale' => ['nullable', 'numeric', 'integer', 'min:0', 'max:9999999999'],
            'capital_price' => ['nullable', 'numeric', 'integer', 'min:1', 'max:9999999999'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $variant = ProductQuantityModel::find($this->route('quantity_id'));
                $product = $variant?->getProducts;

                if (! $product) {
                    return;
                }

                // Checked on the three prices the variant will hold after saving —
                // here a blank box clears the column and the model resolves it back
                // to the product's.
                $merged = clone $variant;

                foreach (['pro_price', 'pro_price_sale', 'capital_price'] as $column) {
                    $value = $this->input($column);
                    $merged->{$column} = ($value === null || $value === '') ? null : (int) $value;
                }

                $listPrice = $merged->listPrice($product);
                $salePrice = $merged->salePrice($product);
                $capitalPrice = $merged->capitalPrice($product);

                if ($listPrice <= $capitalPrice) {
                    $validator->errors()->add('pro_price', 'Giá bán phải lớn hơn giá vốn!');
                }

                if ($salePrice != 0 && $salePrice >= $listPrice) {
                    $validator->errors()->add('pro_price_sale', 'Giá giảm phải thấp hơn giá bán!');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'pro_price.integer' => 'Giá bán phải là số nguyên!',
            'pro_price.min' => 'Giá bán phải lớn hơn 0!',
            'pro_price_sale.integer' => 'Giá giảm phải là số nguyên!',
            'pro_price_sale.min' => 'Giá giảm không được là số âm!',
            'capital_price.integer' => 'Giá vốn phải là số nguyên!',
            'capital_price.min' => 'Giá vốn phải lớn hơn 0!',
        ];
    }

    /**
     * The stock screen renders one of these forms per row, so the errors go into a
     * bag named after the variant. Sharing the default bag painted every modal on
     * the page red because one of them failed.
     */
    protected function prepareForValidation(): void
    {
        $this->errorBag = self::errorBagFor($this->route('quantity_id'));
    }

    public static function errorBagFor($quantityId): string
    {
        return 'variant-price-' . $quantityId;
    }

    protected function failedValidation(Validator $validator): void
    {
        \Illuminate\Support\Facades\Session::flash('iconMessage', 'error');
        \Illuminate\Support\Facades\Session::flash('message', 'Cập nhật giá thất bại!');

        parent::failedValidation($validator);
    }
}
