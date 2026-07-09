<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetadataFieldLayoutField extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'metadata_field_layout_id',
        'metadata_field_definition_id',
        'tab_key',
        'section_key',
        'group_key',
        'sort_order',
        'width',
        'visibility_rules',
        'requirement_rules',
        'readonly_rules',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'visibility_rules' => 'array',
            'requirement_rules' => 'array',
            'readonly_rules' => 'array',
        ];
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(MetadataFieldLayout::class, 'metadata_field_layout_id');
    }

    public function fieldDefinition(): BelongsTo
    {
        return $this->belongsTo(MetadataFieldDefinition::class, 'metadata_field_definition_id');
    }
}
