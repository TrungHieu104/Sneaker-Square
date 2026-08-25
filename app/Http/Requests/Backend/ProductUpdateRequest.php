<?php

namespace App\Http\Requests\Backend;

/**
 * Rules for editing an existing product.
 *
 * Identical to ProductRequest except that the image is optional — an edit that
 * keeps the current picture uploads nothing.
 *
 * The controller checked uniqueness by hand instead, in a chain that read
 * `if (the name changed) … elseif (the code changed) …`. Changing both at once
 * therefore checked only the name, and a duplicate product code went straight
 * through.
 */
class ProductUpdateRequest extends ProductRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'pro_img' => ['nullable', 'image'],
        ]);
    }
}
