<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentJobBoardListing extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'recruitment_provider_id',
        'job_opening_id',
        'external_job_id',
        'status',
        'last_error',
        'attempt_count',
        'next_retry_at',
        'payload',
        'metadata',
        'published_at',
        'closed_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'metadata' => 'array',
            'attempt_count' => 'integer',
            'next_retry_at' => 'datetime',
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(RecruitmentProvider::class, 'recruitment_provider_id');
    }

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }
}
