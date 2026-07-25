<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\OfferLetter */
class OfferLetterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'proposed_salary' => $this->proposed_salary,
            'joining_date' => $this->joining_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'candidate' => $this->whenLoaded('candidate', fn () => [
                'id' => $this->candidate?->id,
                'name' => $this->candidate?->fullName(),
            ]),
            'job_application_id' => $this->job_application_id,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
