<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\TaskRecurrenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskRecurrence extends Model
{
    /** @use HasFactory<TaskRecurrenceFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'task_id',
        'recurrence_type',
        'interval',
        'days_of_week',
        'end_type',
        'end_date',
        'occurrences',
        'generated_count',
        'skip_holidays',
        'copy_attachments',
        'is_active',
        'last_generated_at',
        'next_run_at',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'interval' => 'integer',
            'days_of_week' => 'array',
            'end_date' => 'date',
            'occurrences' => 'integer',
            'generated_count' => 'integer',
            'skip_holidays' => 'boolean',
            'copy_attachments' => 'boolean',
            'is_active' => 'boolean',
            'last_generated_at' => 'datetime',
            'next_run_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
