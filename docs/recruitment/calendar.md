# Recruitment Calendar Integrations

## Purpose
Sync interview rounds to external calendars so interviewers receive events and optional meeting links without leaving NovaCRM.

## Supported Providers
- **Google Calendar** — OAuth calendar events and meeting links
- **Microsoft Outlook Calendar** — OAuth calendar events and meeting links

Meeting-only providers (Google Meet, Microsoft Teams, Zoom) are catalogued as coming soon; when enabled they store meeting links only.

## Lifecycle
1. An interview round is scheduled (`recruitment.interview_scheduled`).
2. Connected calendar providers create or update an external event via `RecruitmentCalendarService`.
3. NovaCRM stores `external_event_id`, `meeting_link`, and sync status on `RecruitmentCalendarEvent`.
4. Meeting link and provider are copied onto the interview round when available.
5. Cancellation (`recruitment.interview_cancelled`) cancels the external event and marks the local record cancelled.
6. Reschedule updates the existing external event when an `external_event_id` is already stored.

Manual sync is available from Recruitment → Integrations → Calendar.

## Storage
- External event IDs and meeting links are persisted per interview round and provider.
- Payload snapshots (title, times, attendees, location) are stored for audit and retry context.
- Statuses: `pending`, `synced`, `updated`, `cancelled`, `failed`.

## Business Rules
- Calendar events require a scheduled interview datetime.
- Provider must be connected before sync.
- **No historical sync** — past interviews are not imported from Google or Outlook.
- Sync failures notify the actor when known; workflows continue regardless.
- Adapters never write Eloquent; the calendar service owns persistence.

## Meeting Providers (Future)
Google Meet, Microsoft Teams, and Zoom appear in the provider catalog as coming soon. Scope is limited to storing meeting links on interview rounds; full meeting lifecycle APIs are out of scope for Version 1.0.

## Permissions
- `recruitment.integration.view` — view calendar sync status
- `recruitment.integration.manage` — connect calendar providers and trigger sync
- Interview schedule/cancel still requires existing `recruitment.interview.*` permissions

## Related Documentation
See [integrations](integrations.md), [interview-management](interview-management.md), [interview-process](interview-process.md), and [webhooks](webhooks.md).
