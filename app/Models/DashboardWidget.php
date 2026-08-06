<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DashboardWidget extends Model
{
    protected $fillable = [
        'organization_id',
        'section_id',
        'module',
        'widget_key',
        'name',
        'description',
        'icon',
        'permission_slug',
        'subscription_module',
        'default_width',
        'default_height',
        'default_position',
        'data_provider',
        'configuration',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_width' => 'integer',
            'default_height' => 'integer',
            'default_position' => 'integer',
            'configuration' => 'array',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(DashboardSection::class, 'section_id');
    }

    public function organizationWidgets(): HasMany
    {
        return $this->hasMany(OrganizationDashboardWidget::class, 'widget_id');
    }

    public function userPreferences(): HasMany
    {
        return $this->hasMany(UserDashboardPreference::class, 'widget_id');
    }
}
