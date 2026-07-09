<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IndustryTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'industry',
        'description',
        'status',
        'visibility',
        'sort_order',
        'current_version_id',
        'draft_payload',
        'draft_schema_version',
        'created_by_platform_user_id',
        'updated_by_platform_user_id',
        'published_by_platform_user_id',
        'published_at',
        'archived_by_platform_user_id',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'draft_payload' => 'array',
            'draft_schema_version' => 'integer',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(IndustryTemplateVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(IndustryTemplateVersion::class, 'current_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'created_by_platform_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'updated_by_platform_user_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'published_by_platform_user_id');
    }

    public function archiver(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'archived_by_platform_user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(OrganizationTemplateApplication::class);
    }

    public function isSelectable(): bool
    {
        return $this->status === 'published' && $this->current_version_id !== null;
    }

    public function statusLabel(): string
    {
        return config("industry_templates.statuses.{$this->status}", $this->status);
    }

    public function visibilityLabel(): string
    {
        return config("industry_templates.visibility.{$this->visibility}", $this->visibility);
    }
}
