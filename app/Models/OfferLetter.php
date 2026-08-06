<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\OfferLetterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferLetter extends Model
{
    /** @use HasFactory<OfferLetterFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'candidate_id',
        'job_application_id',
        'offer_template_id',
        'reporting_manager_id',
        'proposed_salary',
        'variable_pay',
        'benefits',
        'joining_date',
        'expiry_date',
        'status',
        'generated_content',
        'sent_at',
        'accepted_at',
        'rejected_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'proposed_salary' => 'decimal:2',
            'variable_pay' => 'decimal:2',
            'joining_date' => 'date',
            'expiry_date' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function offerTemplate(): BelongsTo
    {
        return $this->belongsTo(OfferTemplate::class);
    }

    public function reportingManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reporting_manager_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(OfferApproval::class)->latest();
    }

    public function negotiations(): HasMany
    {
        return $this->hasMany(OfferNegotiation::class)->latest();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function statusLabel(): string
    {
        return config('hrms.recruitment.offer_statuses.'.$this->status, $this->status);
    }

    public function isActive(): bool
    {
        return in_array($this->status, config('hrms.recruitment.active_offer_statuses', []), true);
    }

    public function isNegotiationLocked(): bool
    {
        return in_array($this->status, ['accepted', 'rejected', 'expired', 'withdrawn'], true);
    }
}
