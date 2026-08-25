<?php

namespace App\Http\Requests\Backend;

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
     * Get the validation rules that apply to the request.
     *
     * Which quantity field is required depends on the kind of product: one with
     * colours and sizes carries a quantity per variant, a plain one carries a
     * single number. The controller used to assemble these two shapes itself,
     * with `$rule += [...]` followed by its own Validator::make call.
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

        if ($this->hasVariants()) {
            return $rules + [
                'quantityColorAndSize' => ['required'],
                'quantityColorAndSize.*' => ['gt:0'],
            ];
        }

        return $rules + [
            'quantityOthers' => ['required', 'gt:0'],
        ];
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
        ];
    }

    /**
     * Keeps the toast the stock form has always shown on a failed entry.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        \Illuminate\Support\Facades\Session::flash('iconMessage', 'error');
        \Illuminate\Support\Facades\Session::flash('message', 'Nhập hàng thất bại!');

        parent::failedValidation($validator);
    }
}
