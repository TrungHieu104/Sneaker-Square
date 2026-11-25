<?php

namespace App\Http\Requests\Backend;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PermissionRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'permission' => ['required', 'string', 'min:3', 'unique:permissions,name'],
        ];
    }

    public function messages()
    {
        return [
            'permission.required' => 'Vui lòng nhập tên quyền!',
            'permission.string' => 'Tên quyền không hợp lệ!',
            'permission.unique' => 'Tên quyền đã tồn tại!',
            'permission.min' => 'Tiêu đề quá ngắn!',
        ];
    }
}
