<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'revision' => $this->revision,
            'code' => $this->code,
            'name' => $this->name,
            'alias' => $this->alias,
            'tax_number' => $this->tax_number,
            'tier_level' => $this->tier_level,
            'csr_reference_doc' => $this->csr_reference_doc,
            'risk_profile' => $this->risk_profile,
            'email' => $this->email,
            'phone' => $this->phone,
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'is_active' => $this->is_active,
            'remark' => $this->remark,
        ];
    }
}
