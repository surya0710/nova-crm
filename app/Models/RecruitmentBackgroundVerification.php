<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentBackgroundVerification extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'recruitment_provider_id',
        'candidate_id',
        'hiring_decision_id',
        'external_verification_id',
        'status',
        'documents',
        'result',
        'last_error',
        'requested_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'documents' => 'array',
            'result' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(RecruitmentProvider::class, 'recruitment_provider_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function hiringDecision(): BelongsTo
    {
        return $this->belongsTo(HiringDecision::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            default => ucfirst((string) $this->status),
        };
    }
}
