<?php

namespace App\Http\Resources\Vaccination;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VaccinationClinicalInsightResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->when($this->id !== null, $this->id),
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}
