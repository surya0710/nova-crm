<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CrmEmailConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'thread_id' => $this->thread_id,
            'customer_id' => $this->customer_id,
            'contact_id' => $this->contact_id,
            'related_type' => $this->related_type,
            'related_id' => $this->related_id,
            'message_count' => $this->message_count,
            'last_status' => $this->last_status,
            'last_status_label' => $this->lastStatusLabel(),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer?->id,
                'display_name' => $this->customer?->display_name,
            ]),
            'contact' => $this->whenLoaded('contact', fn () => [
                'id' => $this->contact?->id,
                'name' => $this->contact?->name,
            ]),
            'messages' => CrmEmailMessageResource::collection($this->whenLoaded('messages')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
