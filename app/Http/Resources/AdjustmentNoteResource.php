<?php

namespace App\Http\Resources;

use App\Models\AdjustmentNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AdjustmentNote */
class AdjustmentNoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'customer_id' => $this->customer_id,
            'invoice_id' => $this->invoice_id,
            'opportunity_id' => $this->opportunity_id,
            'title' => $this->title,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'reason' => $this->reason,
            'reason_detail' => $this->reason_detail,
            'issue_date' => $this->issue_date?->toDateString(),
            'currency' => $this->currency,
            'subtotal' => (float) $this->subtotal,
            'tax_total' => (float) $this->tax_total,
            'total' => (float) $this->total,
            'applied_amount' => (float) $this->applied_amount,
            'applied_at' => $this->applied_at?->toIso8601String(),
            'notes' => $this->notes,
            'terms' => $this->terms,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->display_name,
            ]),
            'items' => CommercialLineItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
