<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CandidateAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class CandidateAccount extends Authenticatable
{
    /** @use HasFactory<CandidateAccountFactory> */
    use Auditable, BelongsToOrganization, HasFactory, Notifiable;

    protected $fillable = [
        'organization_id',
        'candidate_id',
        'email',
        'password',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function savedJobs(): HasMany
    {
        return $this->hasMany(CandidateSavedJob::class);
    }

    public function jobAlerts(): HasMany
    {
        return $this->hasMany(CandidateJobAlert::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(CandidateNotification::class)->latest();
    }
}
