<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\GoalCheckinFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalCheckin extends Model
{
    /** @use HasFactory<GoalCheckinFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'goal_id',
        'summary',
        'progress',
        'risks',
        'next_steps',
        'checked_in_by',
    ];

    protected function casts(): array
    {
        return [
            'goal_id' => 'integer',
            'checked_in_by' => 'integer',
        ];
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class, 'goal_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
