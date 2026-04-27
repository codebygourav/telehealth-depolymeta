<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->data ?? [];

        return [
            'id' => $this->id,
            'title' => $data['title'] ?? null,
            'desc' => $data['message'] ?? null,
            'is_read' => $this->read_at !== null,
            'created_at' => $this->created_at->diffForHumans(),
            'group' => $this->category ?? 'system',
        ];
    }
}
