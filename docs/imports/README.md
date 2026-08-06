# Import Center

NovaCRM Release **1.1.2** introduces a centralized **Universal Import Center** under Administration.

## Architecture

```
Import Center (UI / API)
        ↓
Import Engine (ImportPlatformService)
        ↓
Import Definition (adapters registered in ImportEntityRegistry)
        ↓
Validation → Mapping → Preview → Queued/Sync Processing → Report + Audit
```

One reusable engine; each module registers an adapter implementing `ImportableEntityInterface`.

## Supported formats

- CSV
- Excel (`.xlsx`)

Large files (row count above `import.queue_threshold_rows`, default 100) are processed by `ProcessImportSessionJob`.

## Access

| Permission | Purpose |
|------------|---------|
| `imports.view` | View Import Center, history, previews, error reports |
| `imports.create` | Upload and execute imports |
| `imports.manage` | Full import management (includes all module scopes) |
| `imports.crm` | CRM entities |
| `imports.hrms` | HRMS entities |
| `imports.projects` | Project entities |
| `imports.administration` | Administration entities (users) |

Licensed modules must also be enabled for the organization.

## Workflow

1. Choose entity in **Administration → Import Center**
2. Download template (optional)
3. Upload CSV/XLSX
4. Validate + auto-map columns
5. Adjust field mapping if needed
6. Review preview (valid / invalid / duplicates)
7. Confirm and start import
8. Review completion status / error report

## Entities

### CRM
Leads, Customers, Opportunities

### HRMS
Employees, Departments, Designations, Branches, Shifts, Leave Types, Holiday Calendar

Employee rows may set `Create Login`, `Send Invitation`, and `Portal Access` to provision accounts through the Identity Platform (Release 1.1.1).

### Projects
Projects, Milestones, Tasks

### Administration
Users (invitation workflow only)

> Contacts are not currently modeled in NovaCRM and are not available as an import entity.

## Duplicate strategies

Configured per session (`skip` | `update` | `create`):

| Entity examples | Match keys |
|-----------------|------------|
| Leads / Customers | Email, phone |
| Employees | Employee code, work email |
| Projects | Project code |
| Departments / Branches | Code / name |

## Related docs

- [Import Templates](./import-templates.md)
- [Import Guides](./import-guides.md)
- [Troubleshooting](./import-troubleshooting.md)
- [API](../api/imports.md)
- [SOP-ONB-006](../sops/onboarding/SOP-ONB-006-initial-data-import.md)
