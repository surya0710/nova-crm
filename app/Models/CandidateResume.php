<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CandidateResumeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateResume extends Model
{
    /** @use HasFactory<CandidateResumeFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'candidate_id',
        'name',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'is_default',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'uploaded_at' => 'datetime',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
