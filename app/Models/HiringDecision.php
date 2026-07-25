<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\HiringDecisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HiringDecision extends Model
{
    /** @use HasFactory<HiringDecisionFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'job_application_id',
        'recommendation',
        'decision_date',
        'decision_by',
        'final_notes',
        'onboarding_recommended',
        'onboarding_recommended_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'decision_date' => 'date',
            'onboarding_recommended' => 'boolean',
            'onboarding_recommended_at' => 'datetime',
        ];
    }

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function decisionMaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function recommendationLabel(): string
    {
        return config('hrms.recruitment.hiring_recommendations.'.$this->recommendation, $this->recommendation);
    }
}
