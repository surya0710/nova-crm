<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\JobOpeningFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOpening extends Model
{
    /** @use HasFactory<JobOpeningFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'job_requisition_id',
        'title',
        'department_id',
        'designation_id',
        'employment_type',
        'location',
        'description',
        'responsibilities',
        'requirements',
        'skills',
        'salary_range_min',
        'salary_range_max',
        'experience',
        'education',
        'status',
        'publish_date',
        'closing_date',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'salary_range_min' => 'decimal:2',
            'salary_range_max' => 'decimal:2',
            'publish_date' => 'date',
            'closing_date' => 'date',
        ];
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(JobRequisition::class, 'job_requisition_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function statusLabel(): string
    {
        return config('hrms.recruitment.opening_statuses.'.$this->status, $this->status);
    }
}
