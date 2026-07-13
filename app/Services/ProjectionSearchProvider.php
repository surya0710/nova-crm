<?php

namespace App\Services;

use App\Models\MetadataFieldDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProjectionSearchProvider
{
    /**
     * Apply projection-backed metadata search constraints to an entity builder.
     *
     * @param  Collection<string, MetadataFieldDefinition>  $definitions
     */
    public function apply(
        Builder $builder,
        int $organizationId,
        string $entityType,
        Collection $definitions,
        string $normalizedTerm,
        string $mode = 'contains'
    ): void {
        if ($definitions->isEmpty() || $normalizedTerm === '') {
            return;
        }

        $entityTable = $builder->getModel()->getTable();

        $builder->where(function ($outer) use ($organizationId, $entityType, $definitions, $normalizedTerm, $mode, $entityTable) {
            foreach ($definitions as $definition) {
                $outer->orWhereExists(function ($query) use ($organizationId, $entityType, $definition, $normalizedTerm, $mode, $entityTable) {
                    $query->select(DB::raw(1))
                        ->from('metadata_value_projections')
                        ->where('metadata_value_projections.organization_id', $organizationId)
                        ->where('metadata_value_projections.entity_type', $entityType)
                        ->where('metadata_value_projections.field_key', $definition->key)
                        ->where('metadata_value_projections.is_sensitive', false)
                        ->whereColumn('metadata_value_projections.entity_id', $entityTable.'.id');

                    $this->applyMode($query, $normalizedTerm, $mode);
                });
            }
        });
    }

    protected function applyMode($query, string $normalizedTerm, string $mode): void
    {
        $escaped = $this->escapeLike($normalizedTerm);

        match ($mode) {
            'exact' => $query->where('metadata_value_projections.normalized_search_text', '=', $normalizedTerm),
            'starts_with' => $query->where('metadata_value_projections.normalized_search_text', 'like', $escaped.'%'),
            'contains' => $query->where('metadata_value_projections.normalized_search_text', 'like', '%'.$escaped.'%'),
            default => throw new InvalidArgumentException("Unsupported metadata search mode [{$mode}]."),
        };
    }

    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
