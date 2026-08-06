# Recruitment Outbound Webhooks

## Purpose
Deliver signed HTTP callbacks to customer endpoints when key recruitment domain events occur.

## Outbound Events
| Event key | Workflow trigger |
|-----------|------------------|
| `application_submitted` | `recruitment.application_submitted` |
| `interview_scheduled` | `recruitment.interview_scheduled` |
| `interview_completed` | `recruitment.interview_completed` |
| `offer_sent` | `recruitment.offer_sent` |
| `offer_accepted` | `recruitment.offer_accepted` |
| `candidate_hired_recommendation` | `recruitment.hiring_approved` |

Configured in `config/recruitment.php` under `webhooks.events`.

## Endpoints
Administrators register endpoints under Recruitment → Integrations → Webhooks:
- Name and target URL
- Selected event keys
- Optional shared secret
- Active flag

Each matching active endpoint receives a JSON POST with:

```json
{
  "event": "application_submitted",
  "organization_id": 1,
  "occurred_at": "2026-07-21T12:00:00+00:00",
  "data": { }
}
```

Additional headers:
- `X-NovaCRM-Event` — event key
- `X-NovaCRM-Delivery` — delivery ID
- `X-NovaCRM-Signature` — HMAC-SHA256 of the raw JSON body using the endpoint secret (when a secret is set)

## Retry Backoff and Delivery Logs
- Immediate delivery attempt on dispatch; failed deliveries set `next_retry_at`.
- Backoff seconds: `60`, `300`, `900`, `3600`, `7200` (max attempts default 5).
- Delivery logs store attempt count, HTTP status, truncated response body, and last error.
- Manual retry from the webhooks UI or batch retries via:

```bash
php artisan recruitment:process-integration-retries
```

Optional: `--organization=` to limit to one tenant.

## Business Rules
- Webhook dispatch is best-effort; failures never interrupt recruitment workflows.
- Repeated failures (from attempt 3) notify the endpoint creator.
- Invalid event keys are rejected when creating or updating endpoints.

## Permissions
- `recruitment.webhook.view` — view endpoints and delivery logs
- Creating endpoints and retrying deliveries also requires integration manage capabilities as enforced by routes

## Related Documentation
See [integrations](integrations.md), [apis](apis.md), [business-process overview](business-process/overview.md), and [architecture overview](architecture/overview.md).
