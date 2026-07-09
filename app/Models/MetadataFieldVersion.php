<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetadataFieldVersion extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'metadata_field_definition_id',
        'version',
        'event',
        'snapshot',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'snapshot' => 'array',
        ];
    }

    public function fieldDefinition(): BelongsTo
    {
        return $this->belongsTo(MetadataFieldDefinition::class, 'metadata_field_definition_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
