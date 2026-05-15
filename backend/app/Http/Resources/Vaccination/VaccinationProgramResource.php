<?php

namespace App\Http\Resources\Vaccination;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VaccinationProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $targetType = $this->target_type instanceof \App\Enums\VaccinationProgramTargetType ? $this->target_type->value : $this->target_type;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'target_type' => $targetType,
            'target_type_label' => $this->target_type instanceof \App\Enums\VaccinationProgramTargetType ? $this->target_type->label() : ucfirst((string) $this->target_type),
            'is_active' => $this->is_active,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
