<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DashboardQuickAction extends Model
{
    protected $fillable = [
        'organization_id',
        'module',
        'action_key',
        'name',
        'icon',
        'route',
        'permission_slug',
        'subscription_module',
        'sort_order',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function organizationActions(): HasMany
    {
        return $this->hasMany(OrganizationQuickAction::class, 'quick_action_id');
    }
}
