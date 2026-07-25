<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateChecklist extends Model
{
    use Auditable;

    protected $fillable = [
        'template_task_id',
        'title',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function templateTask(): BelongsTo
    {
        return $this->belongsTo(TemplateTask::class);
    }
}
