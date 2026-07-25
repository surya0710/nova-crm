# SOP-MNT-001 — Application Upgrade

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-MNT-001 |
| **Title** | Application Upgrade |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Maintenance |
| **Owner** | DevOps / Backend Lead |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Upgrade NovaCRM application versions with backup, migration, and smoke validation.

## Scope

- **In scope:** Version upgrade execution referencing UPGRADE.md and release notes.
- **Out of scope:** Feature development and hotfix triage outside scheduled upgrades.

## Preconditions

- [ ] [UPGRADE.md](../../../UPGRADE.md) and release notes read
- [ ] Backup complete (SOP-MNT-002)
- [ ] Maintenance window set

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Production | DevOps | Deploy upgrade |

## Step-by-step Procedure

### 1. Prepare

1. Read UPGRADE.md and release notes.
2. Backup DB and storage.
3. Use [Upgrade checklist](../../operations/upgrade-checklist.md).

### 2. Upgrade

1. Deploy code + forward migrate + warm caches + restart queues (align with SOP-DEP-002).
2. Smoke tests ([smoke.md](../../release/smoke.md)).

## Validation Checklist

- [ ] Upgrade logged
- [ ] Migrations applied forward-only
- [ ] Smoke passed
- [ ] Monitoring nominal
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Execute SOP-REL-004 Rollback; restore DB only if approved; RCA within 48 hours.

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
| **Previous SOP** | [SOP-DEP-002 — Production Deployment](../deployment/SOP-DEP-002-production-deployment.md) |
| **Next SOP** | [SOP-MNT-002 — Backup](SOP-MNT-002-backup.md) |
| **Related SOPs** | [SOP-REL-003](../release-management/SOP-REL-003-production-deployment.md), [SOP-REL-004](../release-management/SOP-REL-004-rollback.md) |
| **Related Documents** | [Upgrade checklist](../../operations/upgrade-checklist.md), [UPGRADE.md](../../../UPGRADE.md) |
| **Required Forms** | Upgrade change ticket |
| **Required Checklists** | [Upgrade checklist](../../operations/upgrade-checklist.md) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
