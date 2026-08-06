<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateNotification extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'candidate_account_id',
        'title',
        'message',
        'action_url',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function candidateAccount(): BelongsTo
    {
        return $this->belongsTo(CandidateAccount::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
