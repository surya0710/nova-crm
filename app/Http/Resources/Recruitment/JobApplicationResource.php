<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\JobApplication */
class JobApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stage' => $this->stage,
            'status' => $this->status,
            'source' => $this->source,
            'applied_date' => $this->applied_date?->toDateString(),
            'candidate' => $this->whenLoaded('candidate', fn () => [
                'id' => $this->candidate?->id,
                'name' => $this->candidate?->fullName(),
                'email' => $this->candidate?->email,
            ]),
            'job_opening' => $this->whenLoaded('jobOpening', fn () => [
                'id' => $this->jobOpening?->id,
                'title' => $this->jobOpening?->title,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
