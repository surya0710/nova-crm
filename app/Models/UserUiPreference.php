<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserUiPreference extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'user_id',
        'theme',
        'density',
        'sidebar_collapsed',
        'last_workspace',
        'landing_page',
        'favorites',
        'pinned_pages',
        'recent_pages',
        'recent_searches',
        'recent_commands',
        'dashboard_layout',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'sidebar_collapsed' => 'boolean',
            'favorites' => 'array',
            'pinned_pages' => 'array',
            'recent_pages' => 'array',
            'recent_searches' => 'array',
            'recent_commands' => 'array',
            'dashboard_layout' => 'array',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
