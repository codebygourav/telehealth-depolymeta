<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->data ?? [];

        return [
            'id' => $this->id,
            'group' => $this->category ?? 'system',
            'event_type' => $this->event_type ?? $data['event_type'] ?? $this->type,
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
            'entity' => $data['entity'] ?? null,
            'data' => $data['meta'] ?? [],
        ];
    }
}
