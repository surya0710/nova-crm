<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetadataValueProjection extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'metadata_field_definition_id',
        'entity_type',
        'entity_id',
        'field_key',
        'field_type',
        'value_hash',
        'value_string',
        'value_text',
        'value_number',
        'value_decimal',
        'value_boolean',
        'value_date',
        'value_datetime',
        'value_time',
        'value_json',
        'normalized_search_text',
        'is_sensitive',
        'definition_status',
        'source_updated_at',
        'projected_at',
    ];

    protected function casts(): array
    {
        return [
            'value_number' => 'integer',
            'value_decimal' => 'decimal:6',
            'value_boolean' => 'boolean',
            'value_date' => 'date',
            'value_datetime' => 'datetime',
            'value_json' => 'array',
            'is_sensitive' => 'boolean',
            'source_updated_at' => 'datetime',
            'projected_at' => 'datetime',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(MetadataFieldDefinition::class, 'metadata_field_definition_id');
    }
}
