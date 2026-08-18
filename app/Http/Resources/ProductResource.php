<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Services\MetadataApiPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'unit_price' => (float) $this->unit_price,
            'cost_price' => $this->cost_price !== null ? (float) $this->cost_price : null,
            'currency' => $this->currency,
            'unit' => $this->unit,
            'tax_rate' => (float) $this->tax_rate,
            'default_discount_percent' => (float) $this->default_discount_percent,
            'hsn_sac' => $this->hsn_sac,
            'tax_inclusive' => (bool) $this->tax_inclusive,
            'cess_rate' => (float) $this->cess_rate,
            'category' => $this->category,
            'product_category_id' => $this->product_category_id,
            'product_category' => $this->whenLoaded('productCategory', fn () => [
                'id' => $this->productCategory?->id,
                'name' => $this->productCategory?->name,
            ]),
            'status' => $this->status,
            'status_label' => $this->status_label,
            'custom_fields' => app(MetadataApiPresenter::class)->customFieldsFor(
                (int) $this->organization_id,
                'product',
                $this->custom_fields,
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
