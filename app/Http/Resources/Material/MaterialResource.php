<?php

namespace App\Http\Resources\Material;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaterialResource extends JsonResource
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
            'uuid' => $this->uuid,
            'revision' => $this->revision,
            'category' => $this->category,
            'code' => $this->code,
            'name' => $this->name,
            'specification' => $this->specification,
            'customer_part_name' => $this->customer_part_name,
            'unit_id' => $this->unit_id,
            'grade' => $this->grade,
            'density' => $this->density,
            'melt_flow_index' => $this->melt_flow_index,
            'color' => $this->color,
            'drawing_change' => $this->drawing_change,
            'shrinkage_rate' => $this->shrinkage_rate,
            'gross_weight' => $this->gross_weight,
            'net_weight' => $this->net_weight,
            'sprue_weight' => $this->sprue_weight,
            'has_rohs' => $this->has_rohs,
            'imds_number' => $this->imds_number,
            'msds_doc_path' => $this->msds_doc_path,
            'risk_profile' => $this->risk_profile,
            'is_active' => $this->is_active,
            'remark' => $this->remark,
        ];
    }
}
