# SOP-DR-001 — Database Recovery

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-DR-001 |
| **Title** | Database Recovery |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Disaster Recovery |
| **Owner** | DevOps / DBA |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Recover the database after corruption, loss, or regional failure.

## Scope

- **In scope:** Restore DB from backup to production or standby and validate integrity.
- **Out of scope:** Application-only rollbacks without DB restore.

## Preconditions

- [ ] Disaster or data-loss declared
- [ ] Backup identified
- [ ] Incident commander engaged

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Database / backups | DevOps | Restore |

## Step-by-step Procedure

### 1. Recover

1. Follow incident command; take app offline if serving corrupt data.
2. Restore from verified backup (SOP-MNT-003 patterns).
3. Validate critical tables/counts; bring app up; smoke.

## Validation Checklist

- [ ] DB restored from intended backup
- [ ] Validation samples pass
- [ ] App healthy
- [ ] Customer comms sent
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Attempt prior backup generation; escalate vendor support; keep app down rather than serve corrupt data.

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
| **Previous SOP** | [SOP-DR-005 — Disaster Checklist](SOP-DR-005-disaster-checklist.md) |
| **Next SOP** | [SOP-DR-002 — Storage Recovery](SOP-DR-002-storage-recovery.md) |
| **Related SOPs** | [SOP-MNT-003](../maintenance/SOP-MNT-003-restore.md), [SOP-SUP-002](../support/SOP-SUP-002-incident-response.md) |
| **Related Documents** | [Incident Response Plan](../../operations/incident-response-plan.md), [Backup Verification](../../operations/backup-verification.md) |
| **Required Forms** | DR restore approval |
| **Required Checklists** | DB recovery validation checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
