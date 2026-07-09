<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetadataFieldOption extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'metadata_field_definition_id',
        'value',
        'label',
        'color',
        'sort_order',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function fieldDefinition(): BelongsTo
    {
        return $this->belongsTo(MetadataFieldDefinition::class, 'metadata_field_definition_id');
    }
}
