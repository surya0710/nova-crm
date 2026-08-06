# SOP-MNT-006 — Log Rotation

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-MNT-006 |
| **Title** | Log Rotation |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Maintenance |
| **Owner** | DevOps |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Rotate and retain application and web server logs within disk and compliance limits.

## Scope

- **In scope:** Logrotate/Windows event or app log retention configuration.
- **Out of scope:** Error log review content (SOP-MON-004).

## Preconditions

- [ ] Disk thresholds known
- [ ] Retention policy documented

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Hosts / logging agent | DevOps | Configure rotation |

## Step-by-step Procedure

### 1. Configure rotation

1. Ensure daily/size-based rotation for `storage/logs` and web server logs.
2. Confirm compressed archives and retention days.
3. Alert when disk > threshold.

## Validation Checklist

- [ ] Rotation active
- [ ] Retention matches policy
- [ ] Disk headroom acceptable
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Manually free safe rotated logs; expand disk if needed; never delete active forensic logs during an incident.

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
| **Previous SOP** | [SOP-MNT-005 — Cache Cleanup](SOP-MNT-005-cache-cleanup.md) |
| **Next SOP** | [SOP-MNT-007 — Queue Recovery](SOP-MNT-007-queue-recovery.md) |
| **Related SOPs** | [SOP-MON-004](../monitoring/SOP-MON-004-error-log-review.md) |
| **Related Documents** | [Operations monitoring checklist](../../operations/monitoring-checklist.md) |
| **Required Forms** | Logging change ticket |
| **Required Checklists** | Log retention checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
