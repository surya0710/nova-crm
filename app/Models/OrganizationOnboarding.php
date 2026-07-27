<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationOnboarding extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'organization_id',
        'initiated_by_platform_user_id',
        'status',
        'current_step',
        'progress_percent',
        'completed_steps',
        'skipped_steps',
        'step_data',
        'checklist',
        'metadata',
        'last_error',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_steps' => 'array',
            'skipped_steps' => 'array',
            'step_data' => 'array',
            'checklist' => 'array',
            'metadata' => 'array',
            'progress_percent' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'initiated_by_platform_user_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ], true);
    }

    public function isStepCompleted(string $step): bool
    {
        return in_array($step, $this->completed_steps ?? [], true);
    }

    public function isStepSkipped(string $step): bool
    {
        return in_array($step, $this->skipped_steps ?? [], true);
    }

    /**
     * @return list<string>
     */
    public static function stepKeys(): array
    {
        return array_keys(config('onboarding.steps', []));
    }
}
