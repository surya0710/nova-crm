<?php

namespace App\Http\Resources;

use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SalesOrder */
class SalesOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'customer_id' => $this->customer_id,
            'quotation_id' => $this->quotation_id,
            'opportunity_id' => $this->opportunity_id,
            'title' => $this->title,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'order_date' => $this->order_date?->toDateString(),
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'currency' => $this->currency,
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'taxable_amount' => (float) $this->taxable_amount,
            'tax_total' => (float) $this->tax_total,
            'cgst_amount' => (float) $this->cgst_amount,
            'sgst_amount' => (float) $this->sgst_amount,
            'igst_amount' => (float) $this->igst_amount,
            'utgst_amount' => (float) $this->utgst_amount,
            'cess_amount' => (float) $this->cess_amount,
            'other_tax_amount' => (float) $this->other_tax_amount,
            'shipping_amount' => (float) $this->shipping_amount,
            'total' => (float) $this->total,
            'notes' => $this->notes,
            'terms' => $this->terms,
            'pricing_mode' => $this->pricing_mode,
            'tax_treatment' => $this->tax_treatment,
            'place_of_supply' => $this->place_of_supply,
            'billing_snapshot' => $this->billing_snapshot,
            'shipping_snapshot' => $this->shipping_snapshot,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->display_name,
                'gstin' => $this->customer?->gstin,
            ]),
            'items' => CommercialLineItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
