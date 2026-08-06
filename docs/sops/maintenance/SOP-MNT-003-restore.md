# SOP-MNT-003 — Restore

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-MNT-003 |
| **Title** | Restore |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Maintenance |
| **Owner** | DevOps |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Restore database and/or storage from a verified backup with explicit approval.

## Scope

- **In scope:** Point-in-time or full restore into target environment.
- **Out of scope:** Routine backups and customer data export (SOP-OFF-002).

## Preconditions

- [ ] Approved restore ticket with target time and scope
- [ ] Backup artifact identified
- [ ] Stakeholders notified of data loss window

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Database / storage | DevOps | Perform restore |

## Step-by-step Procedure

### 1. Approve and prepare

1. Confirm environment (never restore production backup over wrong host).
2. Put application in maintenance if production.

### 2. Restore

1. Restore DB and/or storage per runbook.
2. Validate row counts / sample files.
3. `php artisan up` when healthy; smoke test.

## Validation Checklist

- [ ] Correct backup used
- [ ] Validation samples pass
- [ ] Stakeholders notified of completion
- [ ] Post-restore monitoring watch started
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If restore corrupt, stop serving traffic; attempt prior backup; escalate disaster recovery (SOP-DR-001/002).

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
| **Previous SOP** | [SOP-MNT-002 — Backup](SOP-MNT-002-backup.md) |
| **Next SOP** | [SOP-MNT-004 — Database Maintenance](SOP-MNT-004-database-maintenance.md) |
| **Related SOPs** | [SOP-DR-001](../disaster-recovery/SOP-DR-001-database-recovery.md), [SOP-DR-002](../disaster-recovery/SOP-DR-002-storage-recovery.md) |
| **Related Documents** | [Backup Verification](../../operations/backup-verification.md), [Rollback](../../deployment/rollback.md) |
| **Required Forms** | Restore approval form |
| **Required Checklists** | Restore validation checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
