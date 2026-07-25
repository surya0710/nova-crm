# Interview Management

## Purpose
Manage the recruitment interview pipeline including customizable stages, interview rounds, interviewer assignments, and structured evaluations.

## Core Features
- Tenant-customizable interview stages with default pipeline
- Multiple interview rounds per application
- Internal and external interviewer assignments
- Evaluation templates and scorecards
- Candidate timeline with interview and evaluation history
- Meeting providers for remote interviews

## Meeting Providers

Interview rounds can generate meeting links through the Provider Platform (`InterviewMeetingProviderInterface`):

| Provider | Notes |
|----------|-------|
| Google Meet | Requires connected provider credentials |
| Microsoft Teams | Requires connected provider credentials |
| Zoom | Requires connected provider credentials |
| Jitsi Meet | Stateless room generation |
| Custom Meeting URL | Paste any valid URL |

Stored on the interview round:

- `meeting_link`
- `meeting_provider`
- `meeting_id`
- `join_instructions`

Invitation notifications include the meeting URL and join instructions.

Permission: `recruitment.meeting.manage` (plus existing interview permissions).

## Permissions
- `recruitment.interview.view` — view stages, rounds, templates, evaluations
- `recruitment.interview.create` — create interview records and templates
- `recruitment.interview.edit` — schedule, reschedule, and complete interviews
- `recruitment.interview.delete` — delete interview records and templates
- `recruitment.evaluate` — submit structured candidate evaluations
- `recruitment.meeting.manage` — manage meeting providers and generated links

## Related Documentation
See evaluation-templates, interview-process, calendar integrations, and the recruitment user guide.
