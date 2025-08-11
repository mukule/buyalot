<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // You can adjust this based on roles/permissions
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
            'specifications' => 'nullable|string',
            'whats_in_the_box' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'brand_id' => 'nullable|exists:brands,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'unit_id' => 'nullable|exists:units,id',
            'stock' => 'sometimes|required|integer|min:0',
            'variants' => 'nullable|string',
            'variant_categories' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'nullable|file|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
            'primary_image_index' => 'nullable|integer',
            'price' => 'sometimes|required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
