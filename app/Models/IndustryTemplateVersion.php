<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndustryTemplateVersion extends Model
{
    protected $fillable = [
        'industry_template_id',
        'version',
        'schema_version',
        'payload',
        'payload_hash',
        'changelog',
        'status',
        'published_by_platform_user_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'schema_version' => 'integer',
            'payload' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(IndustryTemplate::class, 'industry_template_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'published_by_platform_user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(OrganizationTemplateApplication::class, 'industry_template_version_id');
    }

    public function statusLabel(): string
    {
        return config("industry_templates.version_statuses.{$this->status}", $this->status);
    }
}
