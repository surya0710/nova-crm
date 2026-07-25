# Recruitment User Guide

## Purpose
Guide recruiters and hiring managers through requisitions, openings, candidates, and applications.

## Who should use this feature
- HR recruiters
- Hiring managers
- HR administrators

## Prerequisites
- Organization membership with recruitment permissions
- HRMS departments and designations configured

## Step-by-step instructions
1. Create a job requisition with department, designation, and business justification.
2. Submit the requisition for approval.
3. After approval, create a job opening from the requisition.
4. Publish the opening when ready to accept applications.
5. Add or select candidates and submit applications.
6. Configure interview stages and evaluation templates.
7. Schedule interview rounds and assign interviewers.
8. Collect structured evaluations and track candidate progress on the timeline.
9. Create offer templates and generate offers for recommended candidates.
10. Submit offers for approval, send to candidates, and record negotiations.
11. Record hiring decisions; accepted offers with Hire decisions generate onboarding recommendations.
12. Publish the careers site and configure candidate portal settings for public applications.
13. Open Recruitment → Analytics to review funnel, sources, recruiter performance, and trends.
14. Open Executive Summary or Dashboard KPI tiles for leadership metrics.
15. Generate reports, save configurations, and export CSV/Excel as needed.
16. Open Recruitment → Integrations to connect calendar and job board providers (when permitted), publish openings externally, review sync status, and inspect webhook deliveries.

## Expected result
Approved requisitions produce openings; candidates can apply via the public careers portal or through recruiter entry; candidates progress through interviews, offers, and hiring decisions with audit trails, notifications, and workflow events. Analytics and reports remain read-only. Connected calendars sync scheduled interviews; published openings can be listed on job boards; outbound webhooks notify external systems. No employee records are created.

## Analytics & Reporting
See [analytics](../analytics.md), [reporting](../reporting.md), and [executive-dashboard](../executive-dashboard.md).

## Integrations
Recruiters with integration permissions use Recruitment → Integrations to:
- Connect Google Calendar or Outlook so scheduled interviews create external events and meeting links
- Publish published openings to LinkedIn, Indeed, Naukri, or the company careers channel
- Review failed syncs and ask an admin to process retries if needed
- Confirm webhook deliveries for application, interview, offer, and hiring events (view-only unless managing endpoints)

See [integrations](../integrations.md), [calendar](../calendar.md), and [job-boards](../job-boards.md).

## Candidate-facing portal
Candidates use `{organization-slug}/careers` to browse jobs, register, manage profiles/resumes, save jobs, subscribe to alerts, and track applications. See [candidate-portal](../candidate-portal.md).

## Best Practices
- Keep candidate email unique per organization.
- Only publish openings when the requisition is approved.
- Publish to job boards only after the opening is published internally.
- Assign recruiters on high-volume openings.
- Filter analytics by period before exporting for leadership reviews.

## Common Mistakes
- Creating openings from draft requisitions (blocked by validation).
- Applying the same candidate twice to one opening (blocked by uniqueness).
- Submitting applications to draft openings (blocked by service rules).

## FAQ
**Can recruitment create employees?** No. Employee creation remains in HRMS and is out of scope for this phase.
