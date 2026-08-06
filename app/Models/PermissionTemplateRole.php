<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionTemplateRole extends Model
{
    protected $fillable = [
        'permission_template_id',
        'role_name',
        'role_slug',
        'role_description',
        'hierarchy_level',
        'color',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'hierarchy_level' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PermissionTemplate::class, 'permission_template_id');
    }

    public function templatePermissions(): HasMany
    {
        return $this->hasMany(PermissionTemplatePermission::class);
    }
}
