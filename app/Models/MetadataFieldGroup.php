<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetadataFieldGroup extends Model
{
    use Auditable, BelongsToOrganization, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'entity_type',
        'key',
        'label',
        'description',
        'sort_order',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(MetadataFieldDefinition::class, 'metadata_field_group_id')
            ->orderBy('sort_order')
            ->orderBy('label');
    }
}
