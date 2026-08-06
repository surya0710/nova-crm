<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'candidate_id',
        'job_opening_id',
        'stage',
        'status',
        'is_draft',
        'candidate_resume_id',
        'profile_snapshot',
        'submission_type',
        'applied_date',
        'source',
        'assigned_recruiter_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'applied_date' => 'date',
            'is_draft' => 'boolean',
            'profile_snapshot' => 'array',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }

    public function assignedRecruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_recruiter_id');
    }

    public function interviewRounds(): HasMany
    {
        return $this->hasMany(InterviewRound::class)->orderByDesc('scheduled_at');
    }

    public function offerLetters(): HasMany
    {
        return $this->hasMany(OfferLetter::class)->latest();
    }

    public function hiringDecisions(): HasMany
    {
        return $this->hasMany(HiringDecision::class)->latest('decision_date');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function stageLabel(): string
    {
        return config('hrms.recruitment.application_stages.'.$this->stage, $this->stage);
    }

    public function statusLabel(): string
    {
        return config('hrms.recruitment.application_statuses.'.$this->status, $this->status);
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(CandidateResume::class, 'candidate_resume_id');
    }

    public function portalStatusLabel(): string
    {
        if ($this->stage === 'withdrawn') {
            return 'Withdrawn';
        }

        if ($this->stage === 'rejected') {
            return 'Rejected';
        }

        if ($this->stage === 'hired') {
            return 'Offer Accepted';
        }

        if ($this->stage === 'offer') {
            $offer = $this->offerLetters()->first();

            return $offer && $offer->status === 'sent' ? 'Offer Sent' : 'Offer Sent';
        }

        if ($this->stage === 'interview') {
            $latestRound = $this->interviewRounds()->first();

            if ($latestRound && $latestRound->status === 'completed') {
                return 'Interview Completed';
            }

            if ($latestRound && in_array($latestRound->status, ['scheduled', 'confirmed'], true)) {
                return 'Interview Scheduled';
            }
        }

        if (in_array($this->stage, ['screening', 'evaluation'], true)) {
            return 'Under Review';
        }

        return config('hrms.recruitment.portal_application_statuses.'.$this->stage, 'Applied');
    }

    public function portalTimeline(): array
    {
        $steps = config('hrms.recruitment.portal_timeline_steps', []);
        $current = $this->portalStatusLabel();
        $terminal = in_array($this->stage, ['rejected', 'withdrawn'], true);

        return collect($steps)->map(function (array $step) use ($current, $terminal): array {
            $reached = $terminal
                ? $step['label'] === $current
                : array_search($current, array_column($steps, 'label'), true) >= array_search($step['label'], array_column($steps, 'label'), true);

            return array_merge($step, ['reached' => $reached]);
        })->all();
    }

    public function canCandidateEdit(): bool
    {
        return ! in_array($this->stage, ['withdrawn', 'rejected', 'hired'], true)
            && $this->status === 'active';
    }
}
