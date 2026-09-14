<?php

namespace App\Http\Requests\Backend;

use App\Models\ProductQuantityModel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Session;

/**
 * Rules for moving one variant's stock up or down.
 *
 * Prices are left out on purpose: the row has its own form for those, and a
 * delivery that quietly repriced the variant is how the intake form loses a
 * per-size price.
 */
class StockAdjustRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A date belongs to a delivery, not to a write-off: the column it fills is
     * "ngày nhập hàng gần nhất".
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', 'in:in,out'],
            'quantity' => ['required', 'numeric', 'integer', 'gt:0', 'max:999999'],
            'quantity_date' => ['required_if:mode,in', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->reducing()) {
                    return;
                }

                $inStock = (int) ProductQuantityModel::where('quantity_id', $this->route('quantity_id'))
                    ->value('quantity');

                if ((int) $this->input('quantity') > $inStock) {
                    $validator->errors()->add(
                        'quantity',
                        'Chỉ còn '.$inStock.' trong kho, không giảm nhiều hơn được!'
                    );
                }
            },
        ];
    }

    public function reducing(): bool
    {
        return $this->input('mode') === 'out';
    }

    public function messages(): array
    {
        return [
            'mode.required' => 'Vui lòng chọn nhập thêm hay giảm bớt!',
            'mode.in' => 'Lựa chọn không hợp lệ!',
            'quantity.required' => 'Vui lòng nhập số lượng!',
            'quantity.gt' => 'Vui lòng nhập số lượng lớn hơn 0!',
            'quantity.integer' => 'Số lượng phải là số nguyên!',
            'quantity_date.required_if' => 'Vui lòng nhập ngày nhập hàng!',
            'quantity_date.date' => 'Ngày nhập hàng không hợp lệ!',
        ];
    }

    /**
     * One of these forms per row, so the errors go into a bag named after the
     * variant rather than reddening every modal on the page.
     */
    protected function prepareForValidation(): void
    {
        $this->errorBag = self::errorBagFor($this->route('quantity_id'));
    }

    public static function errorBagFor($quantityId): string
    {
        return 'stock-adjust-'.$quantityId;
    }

    protected function failedValidation(Validator $validator): void
    {
        Session::flash('iconMessage', 'error');
        Session::flash('message', 'Điều chỉnh tồn kho thất bại!');

        parent::failedValidation($validator);
    }
}
