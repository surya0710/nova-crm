<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function templateRoles(): HasMany
    {
        return $this->hasMany(PermissionTemplateRole::class);
    }
}
