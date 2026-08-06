# Recruitment FAQ

## Can I create an employee from a hired application?
Not automatically. Recruitment records a hiring decision and onboarding recommendation; HR employee creation remains a separate HRMS step.

## Can one candidate apply to multiple openings?
Yes. Candidate profiles are independent; uniqueness is enforced per candidate-opening pair.

## Are interviews supported?
Yes. Interview stages, rounds, participants, evaluation templates, and structured evaluations are available under Recruitment.

## Is external job board publishing available?
Yes (Phase 11.6). Use **Job Board Publishing** under Recruitment Integrations. Providers include LinkedIn Jobs, Indeed, Naukri, and the Company Careers Site (placeholder adapters until live vendor credentials are configured). Only published openings may be published externally; closing an opening closes external listings.

## Where are resume files stored?
Candidate documents use the configured HRMS document disk with metadata in `candidate_documents`. Resume parsing uses a provider framework (internal placeholder today; Affinda/RChilli/Sovren catalogued for later).

## Are calendar and webhook integrations available?
Yes. Google Calendar and Outlook Calendar sync interview events (no historical sync). Outbound webhooks cover application, interview, offer, and hiring recommendation events with retry and delivery logs.

## Is Recruitment Version 1.0 feature complete?
Yes. Phase 11.6 completes the Recruitment Platform integrations and external ecosystem. See [release notes](release-notes/overview.md) and [integrations](integrations.md).

## Related Documentation
- [Integrations](integrations.md)
- [Job Boards](job-boards.md)
- [Calendar](calendar.md)
- [Webhooks](webhooks.md)
- [APIs](apis.md)
- [Overview](overview.md)
