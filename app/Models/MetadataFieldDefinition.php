<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetadataFieldDefinition extends Model
{
    use Auditable, BelongsToOrganization, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'metadata_field_group_id',
        'entity_type',
        'key',
        'label',
        'description',
        'type',
        'status',
        'default_value',
        'validation_rules',
        'visibility_rules',
        'display_rules',
        'permission_rules',
        'is_required',
        'is_unique',
        'is_searchable',
        'is_filterable',
        'is_sortable',
        'is_reportable',
        'is_exportable',
        'is_api_visible',
        'is_sensitive',
        'is_system',
        'sort_order',
        'source',
        'source_type',
        'source_identifier',
        'created_by',
        'updated_by',
        'published_at',
        'activated_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'default_value' => 'array',
            'validation_rules' => 'array',
            'visibility_rules' => 'array',
            'display_rules' => 'array',
            'permission_rules' => 'array',
            'is_required' => 'boolean',
            'is_unique' => 'boolean',
            'is_searchable' => 'boolean',
            'is_filterable' => 'boolean',
            'is_sortable' => 'boolean',
            'is_reportable' => 'boolean',
            'is_exportable' => 'boolean',
            'is_api_visible' => 'boolean',
            'is_sensitive' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
            'activated_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MetadataFieldGroup::class, 'metadata_field_group_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(MetadataFieldOption::class, 'metadata_field_definition_id')
            ->orderBy('sort_order')
            ->orderBy('label');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(MetadataFieldPermission::class, 'metadata_field_definition_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MetadataFieldVersion::class, 'metadata_field_definition_id')
            ->orderByDesc('version');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isOptionBacked(): bool
    {
        return in_array($this->type, config('metadata.option_field_types', []), true);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }
}
