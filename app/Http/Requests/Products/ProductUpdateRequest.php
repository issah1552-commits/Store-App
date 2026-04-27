<?php

namespace App\Http\Requests\Products;

class ProductUpdateRequest extends ProductStoreRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['variants.*.id'] = ['nullable', 'integer', 'exists:product_variants,id'];
        $rules['variants.*.rolls'] = ['nullable', 'integer', 'gt:0'];

        return $rules;
    }
}
