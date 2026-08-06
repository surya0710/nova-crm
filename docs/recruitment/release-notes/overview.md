# Recruitment Release Notes

## Version
- Release: v1.0.0
- Label: Feature Complete
- Phase: 11.6 Recruitment Integrations
- Date: 2026-07-21
- Owner: Recruitment Team

## Highlights
- Recruitment Integrations hub: provider catalog, connect/disconnect, health checks, diagnostics, and retries
- Google Calendar and Outlook sync for interview create/update/cancel with external event IDs and meeting links (no historical sync)
- Job board publish/update/close/sync for LinkedIn, Indeed, Naukri, and Company Careers Site (published openings only)
- Internal resume parsing and placeholder background verification after hiring approval
- Outbound webhooks with HMAC `X-NovaCRM-Signature`, delivery logs, and retry backoff
- REST API v1: `/api/v1/recruitment/jobs|applications|candidates|offers|reports` (Sanctum, `X-Organization-Id`, `api.access`)
- Artisan command `recruitment:process-integration-retries`
- RBAC: `recruitment.integration.view`, `recruitment.integration.manage`, `recruitment.communication.manage`, `recruitment.api.manage`, `recruitment.webhook.view`
- Meeting providers (Meet/Teams/Zoom) catalogued as coming soon

## Breaking Changes
- None. Additive module extension completing Version 1.0.

## Upgrade Notes
- Run forward migrations: `2026_07_21_000042_create_recruitment_integration_tables` and `2026_07_21_000043_sync_recruitment_integration_permissions`.
- Assign integration/webhook/API permissions to roles that should manage external connections.
- Schedule or periodically run `php artisan recruitment:process-integration-retries`.

## Validation
- Confirm calendar sync creates/updates/cancels external events for scheduled interviews.
- Confirm only published openings can be published to job boards; closing an opening closes listings.
- Confirm webhook deliveries include `X-NovaCRM-Signature` when a secret is set and retries advance on failure.
- Confirm REST API endpoints respect Sanctum, organization header, and recruitment permissions.
- Confirm integration failures do not block interview scheduling or opening close.
- Run `php artisan docs:validate` and the full PHPUnit suite.

## Prior Release
### v11.5.0 — Analytics & Reporting
- Executive recruitment dashboard with live KPI widgets
- Funnel, source, recruiter, candidate, opening, and department analytics
- Named executive reports, saved report configurations, sharing, and CSV/Excel exports
- RBAC analytics/report permissions and cached aggregations

### v11.4.0 — Careers & Candidate Portal
- Public careers site and candidate portal introduced.
