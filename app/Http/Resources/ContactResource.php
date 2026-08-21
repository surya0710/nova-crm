<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'name' => $this->name,
            'title' => $this->title,
            'department' => $this->department,
            'email' => $this->email,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'is_primary' => (bool) $this->is_primary,
            'is_decision_maker' => (bool) $this->is_decision_maker,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer?->id,
                'display_name' => $this->customer?->display_name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
