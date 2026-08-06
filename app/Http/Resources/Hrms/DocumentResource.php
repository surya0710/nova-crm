<?php

namespace App\Http\Resources\Hrms;

use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EmployeeDocument */
class DocumentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'category' => $this->category,
            'category_label' => $this->categoryLabel(),
            'title' => $this->title,
            'verification_status' => $this->verification_status,
            'expires_at' => $this->expires_at?->toDateString(),
            'current_version' => $this->whenLoaded('currentVersion', fn () => [
                'id' => $this->currentVersion?->id,
                'version_no' => $this->currentVersion?->version_no,
                'original_name' => $this->currentVersion?->original_name,
                'mime_type' => $this->currentVersion?->mime_type,
                'size' => $this->currentVersion?->size,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
