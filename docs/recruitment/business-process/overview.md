# Recruitment Business Processes

## Purpose
Describe the foundation hiring lifecycle and ownership boundaries.

## Requisition to Opening
1. Hiring manager or HR creates a draft requisition.
2. Requisition is submitted for approval (`pending_approval`).
3. HR approves or rejects the requisition.
4. Approved requisitions may spawn one or more job openings.
5. Openings are published internally; recruiters may then publish to connected job boards (LinkedIn, Indeed, Naukri, Company Careers Site).
6. Closing an opening closes corresponding external job board listings.

## Candidate and Application
1. Candidate profile is created once per email within the organization.
2. Applications link a candidate to a published opening.
3. Pipeline stages are updated manually: Applied → Screening → Interview → Evaluation → Offer → Hired/Rejected/Withdrawn.
4. `application_submitted` outbound webhooks fire for subscribed endpoints.

## Interviews and Calendar
1. Interview rounds are scheduled; connected Google/Outlook calendars create or update external events and store meeting links.
2. Reschedule updates the external event; cancel cancels it. No historical calendar sync.
3. `interview_scheduled` / `interview_completed` webhooks fire when configured.
4. Meeting providers (Meet/Teams/Zoom) remain future; store links only when enabled.

## Offer and Hiring Decision
1. Recommended candidates (hire/strong_hire evaluation) may receive an offer.
2. Offer is generated, submitted for approval, sent, and accepted or rejected.
3. Negotiation history is maintained until offer acceptance locks further changes.
4. `offer_sent` / `offer_accepted` webhooks fire when configured.
5. Hiring decision (Hire/Hold/Reject) is recorded; Hire with accepted offer generates onboarding recommendation only.
6. `candidate_hired_recommendation` webhook fires on hiring approval; background verification may be submitted afterward.

## Ownership Rules
- Recruitment never creates employee records.
- Successful hiring emits `recruitment.hiring_approved` for downstream HR workflow in later phases.
- Analytics and reports are read-only consumers of recruitment data.
- Integration adapter failures never interrupt hiring workflows.

## Analytics & Reporting Process
1. Leaders open the Executive Dashboard or Analytics pages.
2. Filters (today/week/month/quarter/year/custom) scope KPI and trend calculations.
3. Reports can be generated on demand, saved for reuse, shared inside the organization, and exported.
4. Export and saved-report actions are audited.

## Integrations Process
1. Admins connect providers and register webhook endpoints under Recruitment → Integrations.
2. Publishing and calendar actions run from recruiter UI or domain listeners.
3. Failed job board publishes and webhook deliveries retry with backoff (`recruitment:process-integration-retries`).
4. See [integrations](../integrations.md), [job-boards](../job-boards.md), [calendar](../calendar.md), and [webhooks](../webhooks.md).

## Audit and Notifications
All create, update, delete, and restore operations write audit entries. Approval requests, published openings, and new applications trigger database notifications. Report create/share/delete/export events are audited. Integration sync and webhook delivery outcomes are audited. Scheduled email reporting remains a future placeholder.
