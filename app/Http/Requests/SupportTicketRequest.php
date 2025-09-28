<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupportTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:general,technical,billing,shipping,returns,product_inquiry',
            'priority' => 'required|in:low,medium,high,urgent',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ];
    }
}
