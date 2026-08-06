# SOP-ADM-002 — Organization Administration

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ADM-002 |
| **Title** | Organization Administration |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Administration |
| **Owner** | Platform Operator |
| **Reviewer** | Implementation Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Administer organization lifecycle settings (status, profile, and operational flags) without breaking tenant isolation.

## Scope

- **In scope:** Org profile updates, status changes (active/suspended), and platform-side org admin actions.
- **Out of scope:** Subscription commercial changes (Billing) and permanent deletion (Offboarding).

## Preconditions

- [ ] Organization exists
- [ ] Change request with org ID and rationale

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Platform Organizations | Platform Operator | Update org settings |

## Step-by-step Procedure

### 1. Identify tenant

1. Confirm org ID and legal name match the ticket.
2. Snapshot current settings before change.

### 2. Apply change

1. Update profile or status as approved.
2. If suspending, coordinate with Billing/CS and Support.
3. Record before/after on the ticket.

## Validation Checklist

- [ ] Correct org modified
- [ ] Before/after recorded
- [ ] Stakeholders notified for status changes
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Restore previous status/profile from ticket snapshot; notify stakeholders.

## Exceptions

| Exception | Handling | Approver |
|-----------|----------|----------|
| None documented | Follow change-management exception path | Operations Lead |

## Audit Trail

Record the following for every execution:

| Field | Source |
|-------|--------|
| Date / time (UTC) | Ticket or change record |
| Operator | Authenticated user |
| Organization / environment | Ticket fields |
| Actions taken | Procedure steps completed |
| Evidence links | Attachments / URLs |
| Approval (if required) | Approver name + timestamp |

## Cross References

| Relation | Reference |
|----------|-----------|
| **Previous SOP** | [SOP-ADM-001 — Platform Administrator Creation](SOP-ADM-001-platform-administrator-creation.md) |
| **Next SOP** | [SOP-ADM-003 — Subscription Assignment](SOP-ADM-003-subscription-assignment.md) |
| **Related SOPs** | [SOP-ONB-002](../onboarding/SOP-ONB-002-organization-provisioning.md), [SOP-OFF-004](../offboarding/SOP-OFF-004-account-disable.md) |
| **Related Documents** | [Platform Admin Guide](../../onboarding/platform-admin-guide.md) |
| **Required Forms** | Org change request ticket |
| **Required Checklists** | Org identity verification checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
