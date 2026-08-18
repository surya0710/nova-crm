<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommercialLineItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'unit' => $this->unit,
            'hsn_sac' => $this->hsn_sac,
            'description' => $this->description,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'tax_rate' => (float) $this->tax_rate,
            'discount_percent' => (float) $this->discount_percent,
            'line_subtotal' => (float) $this->line_subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'taxable_amount' => (float) $this->taxable_amount,
            'tax_amount' => (float) $this->tax_amount,
            'cgst_rate' => (float) $this->cgst_rate,
            'cgst_amount' => (float) $this->cgst_amount,
            'sgst_rate' => (float) $this->sgst_rate,
            'sgst_amount' => (float) $this->sgst_amount,
            'igst_rate' => (float) $this->igst_rate,
            'igst_amount' => (float) $this->igst_amount,
            'utgst_rate' => (float) $this->utgst_rate,
            'utgst_amount' => (float) $this->utgst_amount,
            'cess_rate' => (float) $this->cess_rate,
            'cess_amount' => (float) $this->cess_amount,
            'tax_inclusive' => (bool) $this->tax_inclusive,
            'line_total' => (float) $this->line_total,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
