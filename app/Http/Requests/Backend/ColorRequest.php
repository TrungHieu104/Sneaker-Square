<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rules for adding or renaming a colour.
 *
 * The unique rules used to ignore `request()->id`, a parameter no colour route
 * ever defines, so on an update every row was compared against — including the
 * one being edited. Saving a colour without changing its name failed, which is
 * why the controller had grown a chain of hand-rolled Validator calls to work
 * around it. Ignoring the id the route actually carries removes the need for
 * all of that.
 */
class ColorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Title-cases the Vietnamese name before it is checked.
     *
     * The controller did this after validating and before saving, so what was
     * checked for uniqueness was not what got stored.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('color_vn')) {
            $this->merge([
                'color_vn' => mb_convert_case((string) $this->input('color_vn'), MB_CASE_TITLE, 'UTF-8'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'color' => ['required', 'max:50', Rule::unique('color', 'color')->ignore($this->route('color_id'), 'color_id')],
            'color_vn' => ['required', 'max:50', Rule::unique('color', 'color_vn')->ignore($this->route('color_id'), 'color_id')],
        ];
    }

    public function messages(){
        return [
            'color.required' => 'Vui lòng nhập tên màu bằng tiếng Anh!',
            'color.unique' => 'Màu đã tồn tại!',
            'color.max' => 'Tên màu quá dài!',

            'color_vn.required' => 'Vui lòng nhập tên màu bằng tiếng Việt!',
            'color_vn.unique' => 'Màu đã tồn tại!',
            'color_vn.max' => 'Tên màu quá dài!',
        ];
    }
}
