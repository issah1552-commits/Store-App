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
            'color' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
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

    protected function prepareForValidation(): void
    {
        if (! $this->filled('color') || ! is_array($this->input('variants'))) {
            return;
        }

        $color = $this->string('color')->trim()->toString();

        $this->merge([
            'color' => $color,
            'variants' => collect($this->input('variants'))
                ->map(fn ($variant) => is_array($variant)
                    ? array_merge($variant, ['color' => $variant['color'] ?? $color])
                    : $variant)
                ->all(),
        ]);
    }
}
