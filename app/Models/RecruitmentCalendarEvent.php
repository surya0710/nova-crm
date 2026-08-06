<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentCalendarEvent extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'recruitment_provider_id',
        'interview_round_id',
        'external_event_id',
        'meeting_link',
        'meeting_provider',
        'status',
        'last_error',
        'attempt_count',
        'payload',
        'metadata',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'metadata' => 'array',
            'synced_at' => 'datetime',
            'attempt_count' => 'integer',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(RecruitmentProvider::class, 'recruitment_provider_id');
    }

    public function interviewRound(): BelongsTo
    {
        return $this->belongsTo(InterviewRound::class);
    }
}
