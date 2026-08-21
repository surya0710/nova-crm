<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CrmEmailMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'related_type' => $this->related_type,
            'related_id' => $this->related_id,
            'customer_id' => $this->customer_id,
            'contact_id' => $this->contact_id,
            'template_id' => $this->template_id,
            'from_email' => $this->from_email,
            'from_name' => $this->from_name,
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'subject' => $this->subject,
            'body' => $this->body,
            'attachments' => $this->attachments,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'provider' => $this->provider,
            'rfc_message_id' => $this->rfc_message_id,
            'in_reply_to' => $this->in_reply_to,
            'thread_id' => $this->thread_id,
            'queued_at' => $this->queued_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
            'bounced_at' => $this->bounced_at?->toIso8601String(),
            'bounce_type' => $this->bounce_type,
            'bounce_reason' => $this->bounce_reason,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
