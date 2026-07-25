<?php

namespace App\Http\Resources;

use App\Models\ProjectBudget;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectBudget */
class ProjectBudgetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'project_id' => $this->project_id,
            'name' => $this->name,
            'currency' => $this->currency,
            'planned_total' => $this->planned_total,
            'actual_total' => $this->actual_total,
            'forecast_total' => $this->forecast_total,
            'variance_total' => $this->variance_total,
            'status' => $this->status,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'budget_category_id' => $item->budget_category_id,
                'name' => $item->name,
                'planned' => $item->planned,
                'actual' => $item->actual,
                'forecast' => $item->forecast,
                'variance' => $item->variance,
                'currency' => $item->currency,
                'notes' => $item->notes,
                'sort_order' => $item->sort_order,
            ])->values()->all()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
