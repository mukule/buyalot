<?php

namespace App\Http\Requests;

use App\Models\Orders\Order;
use App\Models\Payment\PaymentProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @method input(string $string)
 * @method merge(array $array)
 * @method validated()
 */
class InitiatePaymentRequest  extends FormRequest
{
//    public function authorize(): bool
//    {
//        return true;
//    }

    public function rules(): array
    {
        return [
            'payable_type' => ['required', 'string', 'in:order,subscription,invoice'],
            'payable_id' => ['required', 'integer', 'exists:orders,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['sometimes', 'string', 'size:3', 'in:KES,USD,EUR'],
            'provider' => ['required', 'string', Rule::enum(PaymentProvider::class)],
            'method' => ['required', 'string', 'in:mobile_money,card,bank_transfer,cash_on_delivery'],
            'phone' => ['required_if:provider,mpesa', 'nullable', 'string'],
            'email' => ['sometimes', 'nullable', 'email'],
            'metadata' => ['sometimes', 'array'],
            'callback_url' => ['sometimes', 'nullable', 'url'],
            'return_url' => ['sometimes', 'nullable', 'url'],
        ];
    }

    public function getPayable(): Model
    {
        $type = $this->input('payable_type');
        $id = $this->input('payable_id');

        return match ($type) {
            'order' => Order::findOrFail($id),
            // TODO Add other payable
            default => throw new \InvalidArgumentException("Unsupported payable type: {$type}"),
        };
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency' => $this->input('currency', 'KES'),
        ]);
    }
}
