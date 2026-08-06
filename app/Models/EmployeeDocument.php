<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\EmployeeDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDocument extends Model
{
    /** @use HasFactory<EmployeeDocumentFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'category',
        'title',
        'expires_at',
        'verification_status',
        'verified_by',
        'verified_at',
        'verification_notes',
        'current_version_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EmployeeDocumentVersion::class)->orderByDesc('version_no');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(EmployeeDocumentVersion::class, 'current_version_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExpiringSoon(): bool
    {
        if ($this->expires_at === null || $this->isExpired()) {
            return false;
        }

        $days = (int) config('hrms.documents.expiring_soon_days', 30);

        return $this->expires_at->lte(now()->addDays($days));
    }

    public function categoryLabel(): string
    {
        return config('hrms.document_categories.'.$this->category, $this->category);
    }

    public function verificationStatusLabel(): string
    {
        return config('hrms.document_verification_statuses.'.$this->verification_status, $this->verification_status);
    }
}
