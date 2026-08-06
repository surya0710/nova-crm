<?php

namespace App\Http\Resources\Hrms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruitmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $model = $this->resource;

        return [
            'id' => $model->id ?? null,
            'title' => $model->title ?? $model->name ?? null,
            'code' => $model->code ?? $model->job_code ?? null,
            'status' => $model->status ?? null,
            'email' => $model->email ?? null,
            'phone' => $model->phone ?? null,
            'full_name' => $model->full_name ?? trim(($model->first_name ?? '').' '.($model->last_name ?? '')) ?: null,
            'department' => $this->when(
                method_exists($model, 'relationLoaded') && $model->relationLoaded('department'),
                fn () => [
                    'id' => $model->department?->id,
                    'name' => $model->department?->name,
                ]
            ),
            'designation' => $this->when(
                method_exists($model, 'relationLoaded') && $model->relationLoaded('designation'),
                fn () => [
                    'id' => $model->designation?->id,
                    'name' => $model->designation?->name,
                ]
            ),
            'candidate' => $this->when(
                method_exists($model, 'relationLoaded') && $model->relationLoaded('candidate'),
                fn () => [
                    'id' => $model->candidate?->id,
                    'full_name' => $model->candidate?->full_name ?? $model->candidate?->name,
                    'email' => $model->candidate?->email,
                ]
            ),
            'job_opening' => $this->when(
                method_exists($model, 'relationLoaded') && $model->relationLoaded('jobOpening'),
                fn () => [
                    'id' => $model->jobOpening?->id,
                    'title' => $model->jobOpening?->title,
                    'code' => $model->jobOpening?->code ?? $model->jobOpening?->job_code,
                ]
            ),
            'created_at' => isset($model->created_at) ? optional($model->created_at)->toIso8601String() : null,
            'updated_at' => isset($model->updated_at) ? optional($model->updated_at)->toIso8601String() : null,
        ];
    }
}
