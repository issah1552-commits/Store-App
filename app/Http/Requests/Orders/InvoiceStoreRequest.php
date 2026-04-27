<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['nullable', 'exists:orders,id'],
            'discount_tzs' => ['nullable', 'numeric', 'gte:0'],
            'tax_tzs' => ['nullable', 'numeric', 'gte:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'gt:0'],
            'items.*.unit_price_tzs' => ['required_with:items', 'numeric', 'gte:0'],
        ];
    }
}
