# Recruitment Administrator Guide

## Purpose
Configure permissions, catalogs, and operational policies for the recruitment foundation.

## Required configuration
- HRMS departments and designations
- RBAC recruitment permissions assigned to HR roles
- Document storage disk (`HRMS_DOCUMENT_DISK`)

## Permissions
- `recruitment.view` — read access
- `recruitment.create` — create records
- `recruitment.edit` — edit records and move application stages
- `recruitment.delete` — soft-delete records
- `recruitment.manage` — approve requisitions and publish openings
- `recruitment.interview.view` — view interview stages, rounds, templates, evaluations
- `recruitment.interview.create` — create interview records and templates
- `recruitment.interview.edit` — schedule and manage interviews
- `recruitment.interview.delete` — delete interview records and templates
- `recruitment.evaluate` — submit structured candidate evaluations
- `recruitment.offer.view` — view offer templates, offers, approvals, negotiations, hiring decisions
- `recruitment.offer.create` — create templates and generate offers
- `recruitment.offer.edit` — edit offers and record negotiations
- `recruitment.offer.delete` — delete templates and draft offers
- `recruitment.offer.approve` — approve, reject, or return offers
- `recruitment.careers.manage` — manage careers site CMS
- `recruitment.portal.manage` — view candidate portal accounts
- `recruitment.portal.settings` — configure guest apply and portal access
- `recruitment.analytics.view` — view analytics dashboards and KPIs
- `recruitment.reports.view` — view executive/operational reports
- `recruitment.reports.export` — export CSV/Excel reports
- `recruitment.reports.manage` — create, share, and delete saved reports
- `recruitment.integration.view` — view providers, diagnostics, calendar/job-board/resume/BGV screens
- `recruitment.integration.manage` — connect providers, publish externally, health checks, retries
- `recruitment.communication.manage` — manage recruitment communication templates
- `recruitment.api.manage` — API access guidance and token documentation
- `recruitment.webhook.view` — view outbound webhook endpoints and delivery logs

Interview permissions are synced by migration `2026_07_21_000035_sync_interview_permissions`.
Offer permissions are synced by migration `2026_07_21_000037_sync_offer_permissions`.
Candidate portal permissions are synced by migration `2026_07_21_000039_sync_candidate_portal_permissions`.
Analytics permissions are synced by migration `2026_07_21_000041_sync_recruitment_analytics_permissions`.
Integration permissions are synced by migration `2026_07_21_000043_sync_recruitment_integration_permissions`.

## Dependencies
- HRMS foundation (departments, designations, employees for hiring managers)
- Audit logging infrastructure
- Notification service (database channel)
- Workflow platform (event-driven reactions)
- Recruitment operational data from Phases 11.1–11.5
- Platform integration patterns (credentials, health, retries)

## Configuration Steps
1. Grant recruitment permissions to HR and manager roles as needed.
2. Verify departments and designations exist before requisition creation.
3. Configure workflow automations on recruitment domain events if required.
4. Connect calendar and job board providers under Recruitment → Integrations.
5. Register outbound webhook endpoints and secrets; verify `X-NovaCRM-Signature` validation on the consumer.
6. Issue Sanctum API tokens with `api.access` plus recruitment permissions; document `X-Organization-Id` for consumers.
7. Schedule or run `php artisan recruitment:process-integration-retries` for failed publishes and deliveries.

## Integrations Administration
- Provider connect/disconnect and health checks require `recruitment.integration.manage`.
- Webhook endpoints and delivery logs require `recruitment.webhook.view`.
- API Access screen requires `recruitment.api.manage`.
- See [integrations](../integrations.md), [webhooks](../webhooks.md), and [apis](../apis.md).

## Best Practices
- Restrict `recruitment.manage` and `recruitment.integration.manage` to HR leadership.
- Use audit logs to review approval, publishing, and integration activity.
- Keep webhook secrets rotated and endpoints HTTPS-only.

## Troubleshooting
See the troubleshooting section for validation errors and permission issues.
