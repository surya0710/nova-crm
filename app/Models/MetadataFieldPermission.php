<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetadataFieldPermission extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'metadata_field_definition_id',
        'role_id',
        'action',
        'allowed',
    ];

    protected function casts(): array
    {
        return [
            'allowed' => 'boolean',
        ];
    }

    public function fieldDefinition(): BelongsTo
    {
        return $this->belongsTo(MetadataFieldDefinition::class, 'metadata_field_definition_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
