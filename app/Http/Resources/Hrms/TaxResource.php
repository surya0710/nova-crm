<?php

namespace App\Http\Resources\Hrms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $model = $this->resource;

        return [
            'id' => $model->id ?? null,
            'employee_id' => $model->employee_id ?? null,
            'regime' => $model->regime ?? null,
            'status' => $model->status ?? null,
            'effective_from' => isset($model->effective_from) ? (string) $model->effective_from : null,
            'projected_gross' => $model->projected_gross ?? null,
            'projected_taxable' => $model->projected_taxable ?? null,
            'annual_tax_liability' => $model->annual_tax_liability ?? null,
            'monthly_tds' => $model->monthly_tds ?? null,
            'remaining_tds' => $model->remaining_tds ?? null,
            'tds_already_deducted' => $model->tds_already_deducted ?? null,
            'category' => $model->category ?? null,
            'title' => $model->title ?? null,
            'declared_amount' => $model->declared_amount ?? null,
            'claimed_amount' => $model->claimed_amount ?? null,
            'approved_amount' => $model->approved_amount ?? null,
            'financial_year' => $this->when(
                method_exists($model, 'relationLoaded') && $model->relationLoaded('financialYear'),
                fn () => [
                    'id' => $model->financialYear?->id,
                    'name' => $model->financialYear?->label ?? $model->financialYear?->code,
                    'code' => $model->financialYear?->code,
                ]
            ),
            'items' => $this->when(
                method_exists($model, 'relationLoaded') && $model->relationLoaded('items'),
                fn () => collect($model->items)->map(fn ($item) => [
                    'id' => $item->id,
                    'category' => $item->category,
                    'section' => $item->section,
                    'label' => $item->label,
                    'declared_amount' => $item->declared_amount,
                ])->values()->all()
            ),
            'calculated_at' => isset($model->calculated_at) ? optional($model->calculated_at)->toIso8601String() : null,
            'created_at' => isset($model->created_at) ? optional($model->created_at)->toIso8601String() : null,
        ];
    }
}
