<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\JobOpening */
class JobOpeningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'location' => $this->location,
            'employment_type' => $this->employment_type,
            'publish_date' => $this->publish_date?->toDateString(),
            'closing_date' => $this->closing_date?->toDateString(),
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department?->id,
                'name' => $this->department?->name,
            ]),
            'designation' => $this->whenLoaded('designation', fn () => [
                'id' => $this->designation?->id,
                'name' => $this->designation?->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
