<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateTask extends Model
{
    use Auditable;

    protected $fillable = [
        'project_template_id',
        'template_milestone_id',
        'parent_template_task_id',
        'title',
        'description',
        'priority',
        'offset_days',
        'duration_days',
        'estimated_hours',
        'assignee_role',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'offset_days' => 'integer',
            'duration_days' => 'integer',
            'estimated_hours' => 'integer',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function projectTemplate(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class);
    }

    public function templateMilestone(): BelongsTo
    {
        return $this->belongsTo(TemplateMilestone::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_template_task_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_template_task_id')->orderBy('sort_order');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(TemplateChecklist::class)->orderBy('sort_order');
    }

    public function labels(): HasMany
    {
        return $this->hasMany(TemplateLabel::class);
    }
}
