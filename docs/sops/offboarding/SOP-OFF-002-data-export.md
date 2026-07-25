# SOP-OFF-002 — Data Export

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-OFF-002 |
| **Title** | Data Export |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Offboarding |
| **Owner** | Implementation / Ops |
| **Reviewer** | Operations Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Provide customer data export packages required by contract before disable/deletion.

## Scope

- **In scope:** Scoped data export, secure transfer, and customer acknowledgment.
- **Out of scope:** Internal backups (SOP-OFF-003) and permanent deletion.

## Preconditions

- [ ] Export required by contract or customer request
- [ ] Secure transfer channel agreed

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Export tools | Ops / Implementation | Generate export |

## Step-by-step Procedure

### 1. Export

1. Generate agreed datasets (CRM, files metadata, HR as scoped).
2. Transfer via secure channel; do not email unencrypted PII.
3. Customer acknowledges receipt on ticket.

## Validation Checklist

- [ ] Export generated
- [ ] Secure transfer completed
- [ ] Customer acknowledgment recorded
- [ ] Checksum/manifest attached
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Regenerate export if corrupt; extend disable date if needed with Ops Lead approval.

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
| **Previous SOP** | [SOP-OFF-001 — Subscription Closure](SOP-OFF-001-subscription-closure.md) |
| **Next SOP** | [SOP-OFF-003 — Backup](SOP-OFF-003-backup.md) |
| **Related SOPs** | [SOP-MNT-002](../maintenance/SOP-MNT-002-backup.md), [SOP-OFF-005](SOP-OFF-005-data-retention.md) |
| **Related Documents** | Contract data return clause |
| **Required Forms** | Export manifest |
| **Required Checklists** | Export acceptance checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
