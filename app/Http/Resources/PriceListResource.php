<?php

namespace App\Http\Resources;

use App\Models\PriceList;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PriceList */
class PriceListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'currency' => $this->currency,
            'is_default' => (bool) $this->is_default,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'starts_at' => $this->starts_at?->toDateString(),
            'ends_at' => $this->ends_at?->toDateString(),
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'unit_price' => (float) $item->unit_price,
                'min_quantity' => $item->min_quantity !== null ? (float) $item->min_quantity : null,
                'max_quantity' => $item->max_quantity !== null ? (float) $item->max_quantity : null,
                'tax_inclusive' => (bool) $item->tax_inclusive,
                'starts_at' => $item->starts_at?->toDateString(),
                'ends_at' => $item->ends_at?->toDateString(),
                'product' => $item->relationLoaded('product') ? [
                    'id' => $item->product?->id,
                    'name' => $item->product?->name,
                    'sku' => $item->product?->sku,
                ] : null,
            ])->all()),
            'customers' => $this->whenLoaded('customers', fn () => $this->customers->map(fn ($customer) => [
                'id' => $customer->id,
                'name' => $customer->display_name,
                'priority' => (int) ($customer->pivot->priority ?? 0),
            ])->all()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
