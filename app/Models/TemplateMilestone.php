<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateMilestone extends Model
{
    use Auditable;

    protected $fillable = [
        'project_template_id',
        'name',
        'description',
        'sequence',
        'offset_days',
        'duration_days',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'offset_days' => 'integer',
            'duration_days' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function projectTemplate(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class);
    }

    public function templateTasks(): HasMany
    {
        return $this->hasMany(TemplateTask::class)->orderBy('sort_order');
    }
}
