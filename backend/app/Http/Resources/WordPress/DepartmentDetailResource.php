<?php

namespace App\Http\Resources\WordPress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'department_featured' => $this->department_featured,
            'is_tab_layout' => $this->is_tab_layout,
            'additional_information' => $this->additional_information,
            'faqs' => $this->faqs,
            'publications' => $this->publications,
            'tabs' => $this->whenLoaded('tabs', function () {
                return $this->tabs->map(fn($tab) => [
                    'id' => $tab->id,
                    'tab_title' => $tab->tab_title,
                    'tab_content' => $tab->tab_content,
                    'order' => $tab->order,
                ]);
            }),
        ];
    }
}
