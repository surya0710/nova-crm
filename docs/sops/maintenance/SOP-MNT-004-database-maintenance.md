# SOP-MNT-004 — Database Maintenance

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-MNT-004 |
| **Title** | Database Maintenance |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Maintenance |
| **Owner** | Backend Lead / DevOps |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Perform routine database health tasks without destructive resets.

## Scope

- **In scope:** Forward migrations status review, index/health checks, and safe maintenance windows.
- **Out of scope:** Disaster recovery and application feature work.

## Preconditions

- [ ] Maintenance window if locking risk
- [ ] Recent backup available

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Database | DevOps / DBA | Maintenance commands |

## Step-by-step Procedure

### 1. Safe maintenance

1. Review `php artisan migrate:status`.
2. Apply only forward migrations with backup first.
3. Run vendor-approved health/optimize tasks; never wipe shared DBs.

See [Maintenance procedures](../../operations/maintenance-procedures.md).

## Validation Checklist

- [ ] Backup taken before risky maintenance
- [ ] No destructive wipe commands
- [ ] Application healthy after maintenance
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Stop maintenance; restore if corruption (SOP-MNT-003); declare incident if customer impact.

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
| **Previous SOP** | [SOP-MNT-003 — Restore](SOP-MNT-003-restore.md) |
| **Next SOP** | [SOP-MNT-005 — Cache Cleanup](SOP-MNT-005-cache-cleanup.md) |
| **Related SOPs** | [SOP-DEP-002](../deployment/SOP-DEP-002-production-deployment.md), [SOP-DR-001](../disaster-recovery/SOP-DR-001-database-recovery.md) |
| **Related Documents** | [Maintenance procedures](../../operations/maintenance-procedures.md) |
| **Required Forms** | DB maintenance ticket |
| **Required Checklists** | Pre-maintenance backup checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
