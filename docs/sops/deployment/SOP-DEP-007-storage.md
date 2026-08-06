# SOP-DEP-007 — Storage

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-DEP-007 |
| **Title** | Storage |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Deployment |
| **Owner** | DevOps / Platform Engineer |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Ensure application storage directories and object storage links are writable, linked, and backed up.

## Scope

- **In scope:** `storage/` and `bootstrap/cache/` permissions, `storage:link`, and backup inclusion.
- **Out of scope:** Storage disaster recovery (SOP-DR-002).

## Preconditions

- [ ] Host provisioned
- [ ] Backup policy known

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Application filesystem / S3-compatible store | DevOps | Configure disks |

## Step-by-step Procedure

### 1. Local storage

1. Ensure `storage/` and `bootstrap/cache/` are writable by the app user.
2. Run `php artisan storage:link` when public disk requires it.

### 2. Backup inclusion

1. Include `storage/app` with database backups (SOP-MNT-002).
2. Verify sample upload/download.

## Validation Checklist

- [ ] Writable storage confirmed
- [ ] Public link works when required
- [ ] Backup includes storage/app
- [ ] Sample upload succeeds
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Fix permissions; restore files from backup via SOP-DR-002 if data loss; notify affected customers via Support.

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
| **Previous SOP** | [SOP-DEP-006 — Cache](SOP-DEP-006-cache.md) |
| **Next SOP** | [SOP-DEP-008 — SSL](SOP-DEP-008-ssl.md) |
| **Related SOPs** | [SOP-DR-002](../disaster-recovery/SOP-DR-002-storage-recovery.md), [SOP-MNT-002](../maintenance/SOP-MNT-002-backup.md) |
| **Related Documents** | [SSL and storage](../../deployment/ssl-and-storage.md) |
| **Required Forms** | Storage change ticket |
| **Required Checklists** | Storage writability and link checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
