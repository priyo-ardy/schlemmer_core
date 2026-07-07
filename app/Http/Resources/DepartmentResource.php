<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return  [
            'id' => $this->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'remark' => $this->remark,
            'is_active' => $this->is_active
        ];
    }
}
