# Offer Management

## Purpose
Manage reusable offer templates, offer letter generation, approval workflows, negotiation history, and offer lifecycle transitions from draft through acceptance or rejection.

## Core Features
- Reusable offer templates with placeholder substitution
- Offer generation for recommended candidates only
- Configurable multi-approver workflow
- Negotiation history with full audit trail
- Offer statuses: Draft, Pending Approval, Approved, Sent, Accepted, Rejected, Expired, Withdrawn

## Business Rules
- One active offer per application
- Offers require a hire/strong_hire evaluation recommendation
- Offers cannot be sent before approval
- Expired offers cannot be accepted
- Accepted offers lock further negotiations
- No employee records are created during offer management

## Permissions
- `recruitment.offer.view` — view templates, offers, approvals, negotiations
- `recruitment.offer.create` — create templates and generate offers
- `recruitment.offer.edit` — edit offers and record negotiations
- `recruitment.offer.delete` — delete templates and draft offers
- `recruitment.offer.approve` — approve, reject, or return offers

## Workflow Events
- `recruitment.offer_generated`
- `recruitment.offer_approved`
- `recruitment.offer_sent`
- `recruitment.offer_accepted`
- `recruitment.offer_rejected`
- `recruitment.offer_expired`

## Template Placeholders
- `{{candidate_name}}`, `{{position}}`, `{{salary}}`, `{{variable_pay}}`
- `{{joining_date}}`, `{{reporting_manager}}`, `{{benefits}}`, `{{expiry_date}}`

## Related Documentation
See [hiring-decisions](hiring-decisions.md) and the recruitment user guide.
