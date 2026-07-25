# Project Metadata Integration

## Purpose
Document how projects participate in the Metadata Platform using entity key `project`.

## Entity Key
```
project
```

Projects store canonical custom field values in the `projects.metadata` JSON column. `ProjectService` persists metadata through `MetadataEntityFormService::persistValidatedValues()` on create and update.

## Write Path
1. Form or API supplies validated metadata values keyed by field slug
2. `ProjectService` calls `MetadataEntityFormService`
3. Values merge into `projects.metadata` JSON
4. Projection rows synchronize for queryable fields

## Read Path
- Detail views read JSON directly from the project model
- List filters and reports may use projection-backed queries when field definitions are published

## Field Definitions
Administrators create metadata field definitions scoped to entity type `project`. Supported field types follow `config/metadata.php` (`text`, `select`, `date`, `user`, etc.).

## Projection Support
When projection support is enabled for `project`, `MetadataProjectionService` rebuilds rows from entity JSON. Drift detection and rebuild commands accept `--entity_type=project`.

## Workflow Integration
Metadata updates on projects emit change tracking through `ProjectUpdated` when metadata values change, enabling workflow automations that react to custom field updates.

## Permissions
Metadata field visibility and editability respect metadata permission actions (`view`, `edit`, `export`, `report`, `api_read`, `api_write`, `view_sensitive`) in addition to project RBAC permissions.

## Related Documentation
See [developer/metadata.md](../developer/metadata.md) and [developer-guide](developer-guide.md).
