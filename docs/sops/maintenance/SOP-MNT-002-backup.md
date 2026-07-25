# SOP-MNT-002 — Backup

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-MNT-002 |
| **Title** | Backup |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Maintenance |
| **Owner** | DevOps |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Create and verify recoverable backups of database and critical storage.

## Scope

- **In scope:** Scheduled and on-demand backups, verification, and retention labeling.
- **Out of scope:** Restore execution (SOP-MNT-003) and offboarding backups (SOP-OFF-003).

## Preconditions

- [ ] Backup tooling configured
- [ ] Retention policy known

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Backup storage | DevOps | Write backups |

## Step-by-step Procedure

### 1. Take backup

1. Follow [Backup Verification](../../operations/backup-verification.md).
2. Include database and `storage/app` as designed.
3. Label backup with timestamp, environment, and ticket ID.

### 2. Verify

1. Confirm artifact size/checksum.
2. Periodically perform restore test in non-production.

## Validation Checklist

- [ ] Backup artifact stored
- [ ] Checksum/size recorded
- [ ] Ticket updated with location
- [ ] Retention label applied
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If backup failed, halt dependent changes (deploys/imports); escalate P1; retry with alternate method.

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
| **Previous SOP** | [SOP-MNT-001 — Application Upgrade](SOP-MNT-001-application-upgrade.md) |
| **Next SOP** | [SOP-MNT-003 — Restore](SOP-MNT-003-restore.md) |
| **Related SOPs** | [SOP-DR-001](../disaster-recovery/SOP-DR-001-database-recovery.md), [SOP-OFF-003](../offboarding/SOP-OFF-003-backup.md) |
| **Related Documents** | [Backup Verification](../../operations/backup-verification.md) |
| **Required Forms** | Backup job record |
| **Required Checklists** | [Backup Verification](../../operations/backup-verification.md) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
