<?php

namespace App\Http\Requests\Backend;

use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Session;

/**
 * Rules for adding one variant from the stock screen of a product.
 *
 * A blank price box means the variant follows the product, as everywhere else on
 * this screen.
 */
class StockVariantRequest extends FormRequest
{
    public const ERROR_BAG = 'stock-variant';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'size_id' => ['nullable', 'exists:size,size_id'],
            'color_id' => ['nullable', 'exists:color,color_id'],
            'quantity' => ['required', 'numeric', 'integer', 'gt:0', 'max:999999'],
            'quantity_date' => ['required', 'date'],
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
                $product = ProductModel::where('pro_slug', $this->route('pro_slug'))->first();

                if (! $product) {
                    return;
                }

                $this->checkNotAlreadyStocked($validator, $product);
                $this->checkPrices($validator, $product);
            },
        ];
    }

    /**
     * MySQL counts each NULL as distinct, so the unique index on
     * (pro_id, size_id, color_id) lets a second unsized, uncoloured row through.
     */
    private function checkNotAlreadyStocked(Validator $validator, ProductModel $product): void
    {
        $exists = ProductQuantityModel::where('pro_id', $product->pro_id)
            ->where(fn ($query) => $this->matchColumn($query, 'size_id'))
            ->where(fn ($query) => $this->matchColumn($query, 'color_id'))
            ->exists();

        if ($exists) {
            $validator->errors()->add('size_id', 'Biến thể này đã có trong kho, hãy nhập thêm hàng cho nó!');
        }
    }

    private function matchColumn($query, string $column): void
    {
        $value = $this->identifier($column);

        $value === null ? $query->whereNull($column) : $query->where($column, $value);
    }

    private function checkPrices(Validator $validator, ProductModel $product): void
    {
        $variant = new ProductQuantityModel;

        foreach (['pro_price', 'pro_price_sale', 'capital_price'] as $column) {
            $variant->{$column} = $this->identifier($column);
        }

        $listPrice = $variant->listPrice($product);
        $salePrice = $variant->salePrice($product);
        $capitalPrice = $variant->capitalPrice($product);

        if ($listPrice <= $capitalPrice) {
            $validator->errors()->add('pro_price', 'Giá bán phải lớn hơn giá vốn!');
        }

        if ($salePrice != 0 && $salePrice >= $listPrice) {
            $validator->errors()->add('pro_price_sale', 'Giá giảm phải thấp hơn giá bán!');
        }
    }

    public function identifier(string $key): ?int
    {
        $value = $this->input($key);

        return ($value === null || $value === '') ? null : (int) $value;
    }

    public function messages(): array
    {
        return [
            'size_id.exists' => 'Size không tồn tại!',
            'color_id.exists' => 'Màu sắc không tồn tại!',
            'quantity.required' => 'Vui lòng nhập số lượng!',
            'quantity.gt' => 'Vui lòng nhập số lượng lớn hơn 0!',
            'quantity.integer' => 'Số lượng phải là số nguyên!',
            'quantity_date.required' => 'Vui lòng nhập ngày nhập hàng!',
            'quantity_date.date' => 'Ngày nhập hàng không hợp lệ!',
            'pro_price.integer' => 'Giá bán phải là số nguyên!',
            'pro_price.min' => 'Giá bán phải lớn hơn 0!',
            'pro_price_sale.integer' => 'Giá giảm phải là số nguyên!',
            'pro_price_sale.min' => 'Giá giảm không được là số âm!',
            'capital_price.integer' => 'Giá vốn phải là số nguyên!',
            'capital_price.min' => 'Giá vốn phải lớn hơn 0!',
        ];
    }

    /**
     * Its own bag, so a failure here does not paint every price modal on the page
     * red.
     */
    protected function prepareForValidation(): void
    {
        $this->errorBag = self::ERROR_BAG;
    }

    protected function failedValidation(Validator $validator): void
    {
        Session::flash('iconMessage', 'error');
        Session::flash('message', 'Thêm biến thể thất bại!');

        parent::failedValidation($validator);
    }
}
