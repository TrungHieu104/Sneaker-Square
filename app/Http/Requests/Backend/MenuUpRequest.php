<?php

namespace App\Http\Requests\Backend;

use App\Models\MenuModel;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuUpRequest extends FormRequest
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
            'name' => ['required', Rule::unique('menu', 'menu_name')->ignore($this->route('menu'), 'menu_id')],
            'position' => ['nullable', 'min:0', 'max:100000000', 'numeric', 'integer'],
            'slug' => ['required'],
            'parent_id' => ['nullable', Rule::exists('menu', 'menu_id')->whereNull('deleted_at'), $this->notOwnDescendant(...)],
        ];
    }

    /**
     * A menu under itself, directly or further down, drops out of every tree
     * the storefront builds, children included.
     */
    private function notOwnDescendant(string $attribute, mixed $value, Closure $fail): void
    {
        $self = (int) $this->route('menu');
        $parentId = (int) $value;
        $seen = [];

        while ($parentId !== 0 && ! isset($seen[$parentId])) {
            if ($parentId === $self) {
                $fail('Không thể chọn chính menu này hoặc menu con của nó làm menu cha!');

                return;
            }
            $seen[$parentId] = true;
            $parentId = (int) MenuModel::withTrashed()->whereKey($parentId)->value('menu_parent_id');
        }
    }

    public function messages()
    {
        return [
            'name.required' => 'Vui lòng nhập tiêu đề!',
            'parent_id.exists' => 'Menu cha không tồn tại!',
            'name.unique' => 'Tiêu đề đã tồn tại!',
            'position.required' => 'Vui lòng nhập số thứ tự!',
            'slug.required' => 'Vui lòng nhập đường dẫn!',
            'position.min' => 'Vui lòng nhập số thứ tự lớn hơn 0!',
            'position.max' => 'Số thứ tự quá lớn!',
            'position.numeric' => 'Số thứ tự không được nhập chữ!',
            'position.integer' => 'Vui lòng nhập số nguyên',
            'position.unique' => 'Số thứ tự đã tồn tại!',
        ];
    }
}
