<?php

namespace App\Http\Requests\Backend;

use App\Models\FaqGroupModel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FaqGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $editing = $this->route('group');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('faq_groups', 'group_name')->ignore($editing instanceof FaqGroupModel ? $editing->group_id : null, 'group_id')],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'status' => ['required', 'in:0,1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên nhóm!',
            'name.max' => 'Tên nhóm dài tối đa 100 ký tự!',
            'name.unique' => 'Tên nhóm đã tồn tại!',
            'position.integer' => 'Thứ tự phải là số!',
            'position.min' => 'Thứ tự không được âm!',
            'position.max' => 'Thứ tự tối đa là 999!',
            'status.in' => 'Trạng thái hiển thị không hợp lệ!',
        ];
    }
}
