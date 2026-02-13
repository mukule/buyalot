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
        // Decode JSON strings if sent as text
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

        if ($this->has('variant_images') && is_string($this->variant_images)) {
            $this->merge([
                'variant_images' => json_decode($this->variant_images, true) ?? [],
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
                'video_url' => 'nullable|url|regex:/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[\w\-]+$/i',
            ],

            // Step 3: Variants
            3 => [
                'variant_categories' => 'required|array|min:1',
                'variant_categories.*.id' => 'required|integer|exists:variant_categories,id',
                'variant_categories.*.name' => 'required|string|max:255',

                'variant_rows' => 'required|array|min:1',
                'variant_rows.*.values' => 'required|array',
                'variant_rows.*.marked_price' => 'required|numeric|min:0',
                'variant_rows.*.buying_price' => 'required|numeric|min:0',
                'variant_rows.*.stock' => 'required|integer|min:0',
            ],

            // Step 4: Product Images
            4 => [
                'images' => 'nullable|array',
                'images.*' => 'image|mimes:jpg,jpeg,png,gif,webp|max:10240',
                'primary_image_index' => 'nullable|integer|min:0',
            ],

            // Step 5: Variant Images
            5 => [
                'variant_images' => 'required|array|min:1',
                'variant_images.*.variant_id' => 'required|integer|exists:product_variants,id',
                'variant_images.*.file' => 'nullable|file|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
                'variant_images.*.is_primary' => 'required|boolean',
                'variant_images.*.sort_order' => 'required|integer|min:0',
            ],

            // Default: no rules
            default => [],
        };
    }

    public function messages(): array
    {
        return [
            'variant_rows.*.values.required' => 'Each variant row must have values.',
            'variant_rows.*.marked_price.required' => 'Each variant row must have a marked price.',
            'variant_rows.*.buying_price.required' => 'Each variant row must have a buying price.',
            'variant_rows.*.stock.required' => 'Each variant row must have a stock quantity.',
            'video_url.url' => 'The video URL must be a valid URL.',
            'video_url.regex' => 'The video URL must be a valid YouTube link.',
            'variant_images.*.variant_id.required' => 'Each variant image must have a variant ID.',
            'variant_images.*.is_primary.required' => 'Each variant image must specify if it is primary.',
            'variant_images.*.sort_order.required' => 'Each variant image must have a sort order.',
        ];
    }
}
