<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionCalculationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seller_id' => $this->seller_id,
            'rule_name' => $this->rule->name,
            'sale_amount' => $this->sale_amount,
            'commission_amount' => $this->commission_amount,
            'commission_rate' => $this->commission_rate,
            'status' => $this->status,
            'calculated_at' => $this->calculated_at,
            'due_at' => $this->due_at,
            'calculation_details' => $this->calculation_details,
            'seller' => [
                'id' => $this->seller->id,
                'name' => $this->seller->name,
                'email' => $this->seller->email,
            ],
            'calculable' => [
                'type' => $this->calculable_type,
                'id' => $this->calculable_id,
            ],
        ];
    }
}
