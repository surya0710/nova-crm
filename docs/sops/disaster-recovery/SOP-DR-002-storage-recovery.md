# SOP-DR-002 — Storage Recovery

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-DR-002 |
| **Title** | Storage Recovery |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Disaster Recovery |
| **Owner** | DevOps |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Recover application file storage after loss or corruption.

## Scope

- **In scope:** Restore `storage/app` or object storage prefixes from backup.
- **Out of scope:** Database recovery (SOP-DR-001).

## Preconditions

- [ ] Storage loss confirmed
- [ ] Backup available

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Object/local storage backups | DevOps | Restore files |

## Step-by-step Procedure

### 1. Restore files

1. Restore storage backup to correct disk/bucket.
2. Fix permissions / `storage:link`.
3. Spot-check customer uploads.

## Validation Checklist

- [ ] Files restored
- [ ] Permissions correct
- [ ] Sample downloads OK
- [ ] Customers notified if gaps remain
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Retry alternate backup; document unrecoverable objects for customer communication.

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
| **Previous SOP** | [SOP-DR-001 — Database Recovery](SOP-DR-001-database-recovery.md) |
| **Next SOP** | [SOP-DR-003 — Server Recovery](SOP-DR-003-server-recovery.md) |
| **Related SOPs** | [SOP-DEP-007](../deployment/SOP-DEP-007-storage.md), [SOP-MNT-002](../maintenance/SOP-MNT-002-backup.md) |
| **Related Documents** | [SSL and storage](../../deployment/ssl-and-storage.md) |
| **Required Forms** | Storage DR ticket |
| **Required Checklists** | Storage recovery checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
