# SOP-MNT-005 — Cache Cleanup

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-MNT-005 |
| **Title** | Cache Cleanup |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Maintenance |
| **Owner** | DevOps |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Clear stale application caches that cause incorrect runtime behavior.

## Scope

- **In scope:** Artisan cache clears and store flushes when approved.
- **Out of scope:** Normal cache warming after deploy (SOP-DEP-006).

## Preconditions

- [ ] Symptom attributable to stale cache
- [ ] Change ticket open

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Application / Redis | DevOps | Clear caches |

## Step-by-step Procedure

### 1. Clear and rebuild

1. Clear config/route/view caches as needed.
2. Rebuild with `*:cache` commands.
3. Verify symptom resolved.

## Validation Checklist

- [ ] Caches rebuilt
- [ ] Symptom resolved or escalated
- [ ] No prolonged empty-cache thrash in production
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Rebuild caches from known-good config; escalate if widespread errors persist.

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
| **Previous SOP** | [SOP-MNT-004 — Database Maintenance](SOP-MNT-004-database-maintenance.md) |
| **Next SOP** | [SOP-MNT-006 — Log Rotation](SOP-MNT-006-log-rotation.md) |
| **Related SOPs** | [SOP-DEP-006](../deployment/SOP-DEP-006-cache.md) |
| **Related Documents** | [Maintenance procedures](../../operations/maintenance-procedures.md) |
| **Required Forms** | Cache cleanup ticket |
| **Required Checklists** | Cache clear/rebuild checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
