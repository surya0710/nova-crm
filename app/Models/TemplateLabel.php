<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateLabel extends Model
{
    use Auditable;

    protected $fillable = [
        'project_template_id',
        'template_task_id',
        'name',
        'color',
    ];

    public function projectTemplate(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class);
    }

    public function templateTask(): BelongsTo
    {
        return $this->belongsTo(TemplateTask::class);
    }
}
