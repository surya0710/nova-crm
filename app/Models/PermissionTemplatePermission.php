<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionTemplatePermission extends Model
{
    protected $fillable = [
        'permission_template_role_id',
        'permission_slug',
    ];

    public function templateRole(): BelongsTo
    {
        return $this->belongsTo(PermissionTemplateRole::class, 'permission_template_role_id');
    }
}
