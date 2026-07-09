<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetadataFieldLayout extends Model
{
    use Auditable, BelongsToOrganization, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'entity_type',
        'context',
        'name',
        'layout',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'layout' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(MetadataFieldLayoutField::class, 'metadata_field_layout_id')
            ->orderBy('sort_order');
    }
}
