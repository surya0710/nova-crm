# Recruitment Job Board Integrations

## Purpose
Publish, update, close, and status-sync job openings to external boards and the company careers listing channel.

## Supported Providers
- **LinkedIn Jobs**
- **Indeed**
- **Naukri**
- **Company Careers Site** (internal careers channel adapter)

## Operations
| Action | Behavior |
|--------|----------|
| Publish | Creates an external job and stores `external_job_id` |
| Update | Updates an existing listing when `external_job_id` is present |
| Close | Closes the remote listing and marks the local row closed |
| Sync | Refreshes remote status/metadata without republishing |

Managed through Recruitment → Integrations → Job Boards and automatically when openings close.

## External IDs and Retries
- Each opening + provider pair is stored as `RecruitmentJobBoardListing`.
- Successful publishes persist `external_job_id`, payload snapshot, and timestamps.
- Failures set status `failed`, record `last_error`, and schedule `next_retry_at` with platform backoff.
- Retries run from the Integrations UI or `php artisan recruitment:process-integration-retries`.
- Max publish attempts default to 5 (`RECRUITMENT_JOB_BOARD_MAX_ATTEMPTS`).

## Business Rules
- **Only published openings** may be published externally.
- Closing a job opening closes all active external listings for that opening.
- Provider must be connected before publish/update/close via adapter.
- Draft or pending openings cannot be listed on boards.
- Provider failures never block the local opening close/publish workflow.

## Listing Statuses
`pending`, `published`, `updated`, `closed`, `failed`

## Permissions
- `recruitment.integration.view` — view listings and sync history
- `recruitment.integration.manage` — publish, sync, and close listings
- Opening publish/close still requires `recruitment.manage` on the opening itself

## Related Documentation
See [integrations](integrations.md), [careers-site](careers-site.md), [webhooks](webhooks.md), and [business-process overview](business-process/overview.md).
