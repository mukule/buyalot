<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'         => 'required|string|max:255',
            'last_name'          => 'required|string|max:255',
            'phone'              => 'nullable|string|max:20',
            'address_line_1'     => 'required|string|max:255',
            'region_id'          => 'required|exists:regions,id',
            'delivery_mode'      => 'required|string|in:pickup,door',
            'pickup_warehouse_id'=> 'required_if:delivery_mode,pickup|nullable|exists:warehouses,id',
            'latitude'           => 'nullable|numeric',
            'longitude'          => 'nullable|numeric',
            'is_default'         => 'sometimes|boolean',
        ];
    }

    /**
     * Ensure `is_default` is always present in validated data.
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        // Force is_default to 1 if present, 0 otherwise
        $data['is_default'] = $this->has('is_default') ? 1 : 0;

        return $data;
    }
}
