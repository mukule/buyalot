<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommissionPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:commission_plans,slug,' . $this->route('plan')?->id,
            'description' => 'nullable|string',
            'billing_cycle' => 'required|in:monthly,quarterly,annually',
            'base_fee' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'features' => 'nullable|array',
            'sort_order' => 'integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The plan name is required.',
            'slug.unique' => 'This slug is already in use.',
            'base_fee.required' => 'The base fee is required.',
            'billing_cycle.in' => 'Please select a valid billing cycle.',
        ];
    }
}
