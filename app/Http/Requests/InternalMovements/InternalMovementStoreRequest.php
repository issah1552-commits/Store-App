<?php

namespace App\Http\Requests\InternalMovements;

use Illuminate\Foundation\Http\FormRequest;

class InternalMovementStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => ['required', 'exists:locations,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'gt:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
