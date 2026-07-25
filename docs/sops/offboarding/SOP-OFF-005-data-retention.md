# SOP-OFF-005 — Data Retention

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-OFF-005 |
| **Title** | Data Retention |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Offboarding |
| **Owner** | Ops / Legal |
| **Reviewer** | Operations Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Hold disabled customer data for the contracted retention period before deletion.

## Scope

- **In scope:** Retention calendar, legal hold checks, and access restrictions.
- **Out of scope:** Permanent deletion execution (SOP-OFF-006).

## Preconditions

- [ ] Account disabled
- [ ] Retention policy / contract term known

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Retention register | Ops / Legal | Track holds |

## Step-by-step Procedure

### 1. Retain

1. Enter org into retention register with deletion eligibility date.
2. Block deletion if legal hold present.
3. Review monthly for eligibility.

## Validation Checklist

- [ ] Register updated
- [ ] Legal hold checked
- [ ] Deletion eligibility date set
- [ ] Access remains restricted
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If register error found, correct dates before any deletion; notify Legal.

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
| **Previous SOP** | [SOP-OFF-004 — Account Disable](SOP-OFF-004-account-disable.md) |
| **Next SOP** | [SOP-OFF-006 — Permanent Deletion](SOP-OFF-006-permanent-deletion.md) |
| **Related SOPs** | [SOP-OFF-003](SOP-OFF-003-backup.md) |
| **Related Documents** | Retention policy / contract |
| **Required Forms** | Retention register entry |
| **Required Checklists** | Monthly retention review checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
