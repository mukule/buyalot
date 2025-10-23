<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ulid,
            'reference' => $this->reference,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'provider' => $this->provider,
            'method' => $this->method,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'payable' => [
                'type' => class_basename($this->payable_type),
                'id' => $this->payable_id,
            ],
            'metadata' => $this->metadata,
            'failure_reason' => $this->failure_reason,
            'expires_at' => $this->expires_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
