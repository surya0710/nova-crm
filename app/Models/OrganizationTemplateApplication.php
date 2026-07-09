<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationTemplateApplication extends Model
{
    protected $fillable = [
        'organization_id',
        'industry_template_id',
        'industry_template_version_id',
        'applied_by_platform_user_id',
        'application_type',
        'status',
        'payload_hash',
        'applied_sections',
        'skipped_sections',
        'summary',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'applied_sections' => 'array',
            'skipped_sections' => 'array',
            'summary' => 'array',
            'applied_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(IndustryTemplate::class, 'industry_template_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(IndustryTemplateVersion::class, 'industry_template_version_id');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'applied_by_platform_user_id');
    }
}
