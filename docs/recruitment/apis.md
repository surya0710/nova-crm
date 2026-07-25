# Recruitment REST API v1

## Purpose
Expose read-oriented recruitment resources for authenticated API consumers using Laravel Sanctum.

## Base Path
`/api/v1/recruitment`

## Endpoints
| Method | Path | Description |
|--------|------|-------------|
| GET | `/jobs` | Paginated job openings |
| GET | `/jobs/{job}` | Single opening |
| GET | `/applications` | Paginated applications |
| GET | `/applications/{application}` | Single application |
| GET | `/candidates` | Paginated candidates |
| GET | `/candidates/{candidate}` | Single candidate |
| GET | `/offers` | Paginated offer letters |
| GET | `/offers/{offer}` | Single offer |
| GET | `/reports` | Paginated saved reports |
| GET | `/reports/{report}` | Single saved report |

## Authentication and Tenancy
- **Auth:** Sanctum bearer token (`auth:sanctum`)
- **Organization:** `X-Organization-Id` (via `set.organization` / `ensure.organization` middleware)
- **API gate:** `api.access` permission (`organization.api` middleware group)
- **Recruitment permissions:** jobs/applications/candidates require `recruitment.view`; offers require `recruitment.offer.view`; reports require `recruitment.reports.view` (plus model policies)

Tokens and guidance are managed under Recruitment → Integrations → API Access (`recruitment.api.manage`).

## Multi-Tenancy
All queries are organization-scoped. Cross-tenant IDs return 404.

## Web Routes
Authenticated browser routes for recruitment UI (including Integrations) are documented in [api overview](api/overview.md).

## Related Documentation
See [integrations](integrations.md), [webhooks](webhooks.md), [api overview](api/overview.md), and [admin guide](admin-guide/overview.md).
