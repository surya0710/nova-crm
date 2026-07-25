<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TaskLabel extends Pivot
{
    public $incrementing = true;

    public $timestamps = true;

    protected $table = 'task_labels';

    protected $fillable = [
        'task_id',
        'label_id',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function label(): BelongsTo
    {
        return $this->belongsTo(ProjectLabel::class, 'label_id');
    }
}
