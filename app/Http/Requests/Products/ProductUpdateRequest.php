<?php

namespace App\Http\Requests\Products;

class ProductUpdateRequest extends ProductStoreRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['color'] = ['nullable', 'string', 'max:255'];
        $rules['category_id'] = ['required', 'exists:categories,id'];
        $rules['variants.*.id'] = ['nullable', 'integer', 'exists:product_variants,id'];
        $rules['variants.*.rolls'] = ['nullable', 'integer', 'gt:0'];

        return $rules;
    }
}
