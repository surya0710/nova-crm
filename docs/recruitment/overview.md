# Recruitment Overview

## Purpose
The Recruitment platform manages the hiring lifecycle from internal job requisition through candidate application tracking, interviews, offers, careers portal, executive analytics, and external integrations.

## Core Areas
- Job requisitions (internal hiring requests)
- Job openings (vacancies from approved requisitions)
- Candidates (independent talent profiles)
- Applications (candidate-to-opening pipeline)
- Public careers site and candidate portal
- Interview stages, rounds, and evaluations
- Evaluation templates (reusable scorecards)
- Offer templates, offers, approvals, and negotiations
- Hiring decisions and onboarding recommendations
- Recruitment analytics, KPIs, funnel intelligence, and executive reporting
- Saved reports and CSV/Excel exports
- **Integrations** — calendar, job boards, resume parsing, background verification, outbound webhooks, and REST API v1

## Version 1.0 Feature Complete
Phase 11.6 completes Version 1.0 of Recruitment: operational hiring, careers portal, analytics, and external integrations (calendar sync, job board publish, webhooks, and API access) form a production-ready hiring stack. Meeting providers (Meet/Teams/Zoom) remain catalogued as coming soon.

## Platform Ownership
Recruitment owns hiring data, recruitment analytics, and recruitment integration orchestration. Platform owns credentials, OAuth/token patterns, health, and retry conventions. HRMS owns employee analytics. CRM owns sales analytics. Analytics consumes recruitment data but never modifies business records. Candidates become employees only after an approved hiring process in a later phase.

## Related Documentation
See [integrations](integrations.md), [calendar](calendar.md), [job-boards](job-boards.md), [webhooks](webhooks.md), [apis](apis.md), [analytics](analytics.md), [reporting](reporting.md), [executive-dashboard](executive-dashboard.md), [user-guide](user-guide/overview.md), [admin-guide](admin-guide/overview.md), [business-process](business-process/overview.md), [architecture](architecture/overview.md), [api](api/overview.md), [configuration](configuration/overview.md), [troubleshooting](troubleshooting/overview.md), [faq](faq/overview.md), and [release-notes](release-notes/overview.md).
