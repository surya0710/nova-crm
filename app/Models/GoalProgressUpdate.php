<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\GoalProgressUpdateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalProgressUpdate extends Model
{
    /** @use HasFactory<GoalProgressUpdateFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'goal_id',
        'progress_value',
        'achievement_percentage',
        'notes',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'goal_id' => 'integer',
            'progress_value' => 'decimal:4',
            'achievement_percentage' => 'decimal:2',
            'updated_by' => 'integer',
        ];
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class, 'goal_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
