# Hiring Decisions

## Purpose
Record final hiring recommendations before HR handoff. Recruitment owns hiring decisions; HRMS owns employee creation.

## Recommendations
- **Hire** — candidate approved for onboarding recommendation (requires accepted offer)
- **Hold** — decision deferred
- **Reject** — candidate not proceeding

## HR Handoff
When a **Hire** decision is recorded and an accepted offer exists:
- An onboarding recommendation is generated (`onboarding_recommended` flag)
- No employee record is created
- The `recruitment.hiring_approved` workflow event is emitted

Employee master data creation remains in HRMS and is out of scope for this phase.

## Permissions
Uses offer management permissions (`recruitment.offer.view`, `recruitment.offer.create`).

## Audit
All hiring decisions are audit-logged with recommendation, decision date, and onboarding recommendation status.

## Related Documentation
See [offer-management](offer-management.md) and the recruitment business process guide.
