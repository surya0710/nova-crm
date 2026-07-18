<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\AssignmentHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentHistory extends Model
{
    /** @use HasFactory<AssignmentHistoryFactory> */
    use BelongsToOrganization, HasFactory;

    public const REASON_AUTOMATIC = 'automatic';

    public const REASON_MANUAL = 'manual';

    public const REASON_REASSIGNED = 'reassigned';

    public const REASON_IMPORTED = 'imported';

    public const REASON_API = 'api';

    protected $fillable = [
        'organization_id',
        'entity_type',
        'entity_id',
        'previous_owner_id',
        'new_owner_id',
        'strategy',
        'assignment_rule_id',
        'assignment_pool_id',
        'assigned_by',
        'reason',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    public function previousOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'previous_owner_id');
    }

    public function newOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'new_owner_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AssignmentRule::class, 'assignment_rule_id');
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(AssignmentPool::class, 'assignment_pool_id');
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
