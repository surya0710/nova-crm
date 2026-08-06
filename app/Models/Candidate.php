<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CandidateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidate extends Model
{
    /** @use HasFactory<CandidateFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'current_company',
        'current_designation',
        'experience',
        'notice_period',
        'availability_date',
        'preferred_locations',
        'current_salary',
        'expected_salary',
        'skills',
        'education',
        'work_experience',
        'languages',
        'certifications',
        'resume_path',
        'linkedin',
        'github',
        'portfolio',
        'profile_photo_path',
        'source',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'current_salary' => 'decimal:2',
            'expected_salary' => 'decimal:2',
            'availability_date' => 'date',
            'education' => 'array',
            'work_experience' => 'array',
            'languages' => 'array',
            'certifications' => 'array',
            'preferred_locations' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CandidateDocument::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function account(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CandidateAccount::class);
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(CandidateResume::class);
    }

    public function defaultResume(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CandidateResume::class)->where('is_default', true);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function sourceLabel(): string
    {
        return config('hrms.recruitment.candidate_sources.'.$this->source, $this->source ?? '—');
    }
}
