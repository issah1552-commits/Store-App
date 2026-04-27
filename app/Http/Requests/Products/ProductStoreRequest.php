<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.color' => ['required', 'string', 'max:255'],
            'variants.*.meter_length' => ['required', 'numeric', 'gt:0'],
            'variants.*.rolls' => ['required', 'integer', 'gt:0'],
            'variants.*.standard_cost_tzs' => ['required', 'numeric', 'gte:0'],
            'variants.*.wholesale_price_tzs' => ['required', 'numeric', 'gte:0'],
            'variants.*.retail_price_tzs' => ['required', 'numeric', 'gte:0'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'gte:0'],
        ];
    }
}
