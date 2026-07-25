# SOP-OFF-003 — Backup

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-OFF-003 |
| **Title** | Backup |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Offboarding |
| **Owner** | DevOps |
| **Reviewer** | Operations Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Take a final retention backup before account disable/deletion for legal hold compliance.

## Scope

- **In scope:** Final org backup labeling, storage in restricted retention vault, and hold flags.
- **Out of scope:** Customer-facing export (SOP-OFF-002).

## Preconditions

- [ ] Offboarding ticket active
- [ ] Retention period known

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Backup vault | DevOps | Store restricted backup |

## Step-by-step Procedure

### 1. Final backup

1. Backup DB subset/org data and storage as designed.
2. Label with org ID, offboarding ticket, retention end date.
3. Restrict access to Ops/Legal only.

## Validation Checklist

- [ ] Backup stored
- [ ] Retention end date labeled
- [ ] Access restricted
- [ ] Ticket updated with location
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If backup fails, do not proceed to permanent deletion; escalate Ops Lead.

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
| **Previous SOP** | [SOP-OFF-002 — Data Export](SOP-OFF-002-data-export.md) |
| **Next SOP** | [SOP-OFF-004 — Account Disable](SOP-OFF-004-account-disable.md) |
| **Related SOPs** | [SOP-MNT-002](../maintenance/SOP-MNT-002-backup.md), [SOP-OFF-005](SOP-OFF-005-data-retention.md) |
| **Related Documents** | [Backup Verification](../../operations/backup-verification.md) |
| **Required Forms** | Offboarding backup record |
| **Required Checklists** | Final backup checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
