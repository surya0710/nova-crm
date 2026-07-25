# Recruitment Integrations

## Purpose
Connect recruitment workflows to external calendar, job board, resume parsing, background verification, webhook, and API consumers while keeping domain rules inside Recruitment services.

## Architecture
```
Controller → Form Request → Integration Service → Provider Adapter → External API
```

Orchestration lives in `RecruitmentIntegrationService` and domain services (`RecruitmentCalendarService`, `RecruitmentJobBoardService`, `ResumeParsingService`, `BackgroundVerificationService`, `RecruitmentWebhookService`, `RecruitmentApiService`). Provider adapters under `App\Services\Recruitment\Providers` call external APIs and never write Eloquent models.

## Platform Ownership vs Recruitment Ownership
- **Platform** owns credentials, OAuth/token patterns, health-check UX patterns, retry backoff conventions, and cross-module provider diagnostics.
- **Recruitment** owns when integrations run, which domain events trigger outbound calls, listing/event persistence, webhook endpoints for hiring events, and recruitment-scoped API resources.
- Adapter failures are logged and retried; they must never interrupt core hiring workflows.

## Provider Catalog
| Category | Providers | Status |
|----------|-----------|--------|
| Calendar | Google Calendar, Microsoft Outlook Calendar | Active |
| Job boards | LinkedIn Jobs, Indeed, Naukri, Company Careers Site | Active |
| Resume parsing | Internal Resume Parser | Active |
| Resume parsing | Affinda, RChilli, Sovren | Coming soon |
| Background verification | Placeholder BGV adapter | Active (placeholder) |
| Meeting | Google Meet, Microsoft Teams, Zoom | Coming soon (store links only when enabled) |

Catalog and drivers are defined in `config/recruitment.php`.

## Business Rules
- Only connected providers may create calendar events or publish job listings.
- Job board publish requires a **published** opening; closing an opening closes external listings.
- Calendar sync is forward-only for scheduled interviews (create/update/cancel); no historical calendar import.
- Background verification starts only after hiring approval.
- Outbound webhook and provider failures never block requisition, interview, offer, or hiring transitions.
- Multi-tenancy: all integration records use organization scopes.

## Diagnostics, Health, and Retries
- Integrations UI and Provider Health Center consume `RecruitmentIntegrationService::diagnostics()`.
- Per-provider connect, disconnect, and health-check actions are available under Recruitment → Integrations.
- Failed job board publishes and webhook deliveries enter a retry queue with exponential backoff (`60, 300, 900, 3600, 7200` seconds; max attempts configurable).
- Process retries via UI or `php artisan recruitment:process-integration-retries`.

## Permissions
- `recruitment.integration.view` — view providers, diagnostics, calendar/job-board/resume/BGV screens
- `recruitment.integration.manage` — connect providers, publish/sync/close, process retries, health checks
- `recruitment.communication.manage` — communication templates
- `recruitment.api.manage` — API access guidance
- `recruitment.webhook.view` — webhook endpoints and delivery logs

Permissions are synced by migration `2026_07_21_000043_sync_recruitment_integration_permissions`.

## Related Documentation
See [calendar](calendar.md), [job-boards](job-boards.md), [webhooks](webhooks.md), [apis](apis.md), [architecture overview](architecture/overview.md), and [admin guide](admin-guide/overview.md).
