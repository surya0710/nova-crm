<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\RecruitmentSavedReport */
class SavedReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->report_name,
            'report_type' => $this->report_type,
            'filters' => $this->filters_json,
            'is_shared' => $this->is_shared,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
