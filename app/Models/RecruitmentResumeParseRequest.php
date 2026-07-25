<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentResumeParseRequest extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'recruitment_provider_id',
        'candidate_id',
        'candidate_resume_id',
        'status',
        'parsed_data',
        'applied_to_candidate',
        'overwrite_confirmed',
        'last_error',
        'requested_by',
    ];

    protected function casts(): array
    {
        return [
            'parsed_data' => 'array',
            'applied_to_candidate' => 'boolean',
            'overwrite_confirmed' => 'boolean',
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

    public function resume(): BelongsTo
    {
        return $this->belongsTo(CandidateResume::class, 'candidate_resume_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
