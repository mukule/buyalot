<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // adjust if needed based on roles/permissions
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('variant_categories') && is_string($this->variant_categories)) {
            $this->merge([
                'variant_categories' => json_decode($this->variant_categories, true) ?? [],
            ]);
        }

        if ($this->has('variant_rows') && is_string($this->variant_rows)) {
            $this->merge([
                'variant_rows' => json_decode($this->variant_rows, true) ?? [],
            ]);
        }
    }

    public function rules(): array
    {
        $step = (int) $this->input('step', 1);

        return match ($step) {
            // Step 1: Basic info
            1 => [
                'product_code' => [
                    'nullable',
                    'string',
                    'max:100',
                    Rule::unique('products', 'product_code')->ignore($this->route('product')),
                ],
                'name' => 'required|string|max:255',
                'brand_id' => 'required|exists:brands,id',
                'category_id' => 'required|exists:categories,id',
                'unit_id' => 'required|exists:units,id',
            ],

            // Step 2: Descriptions, features, specs
            2 => [
                'description' => 'nullable|string',
                'features' => 'nullable|string',
                'specifications' => 'nullable|string',
                'whats_in_the_box' => 'nullable|string',
                'meta_title' => 'nullable|string|max:255',
                'meta_keywords' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
            ],

            // Step 3: Variants
            3 => [
                'variant_categories' => 'required|array|min:1',
                'variant_categories.*.id' => 'required|integer|exists:variant_categories,id',
                'variant_categories.*.name' => 'required|string|max:255',

                'variant_rows' => 'required|array|min:1',
                'variant_rows.*.values' => 'required|array',
                'variant_rows.*.regular_price' => 'required|numeric|min:0',
                'variant_rows.*.selling_price' => 'required|numeric|min:0',
                'variant_rows.*.stock' => 'required|integer|min:0',
            ],

            // Step 4: Images
            4 => [
                'images' => 'nullable|array',
                'images.*' => 'image|mimes:jpg,jpeg,png,gif,webp|max:10240',
                'primary_image_index' => 'nullable|integer|min:0',
            ],

            default => [],
        };
    }

    public function messages(): array
    {
        return [
            'variant_rows.*.values.required' => 'Each variant row must have values.',
            'variant_rows.*.regular_price.required' => 'Each variant row must have a regular price.',
            'variant_rows.*.selling_price.required' => 'Each variant row must have a selling price.',
            'variant_rows.*.stock.required' => 'Each variant row must have a stock quantity.',
        ];
    }
}
