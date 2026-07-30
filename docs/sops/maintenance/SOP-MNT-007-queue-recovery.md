# SOP-MNT-007 — Queue Recovery

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-MNT-007 |
| **Title** | Queue Recovery |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Maintenance |
| **Owner** | DevOps / Backend Lead |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Recover stuck, failed, or flooded queues to restore asynchronous processing.

## Scope

- **In scope:** Failed job retry/flush decisions, worker restart, and backlog drain.
- **Out of scope:** Normal worker setup (SOP-DEP-004).

## Preconditions

- [ ] Queue anomaly detected (SOP-MON-002)
- [ ] Impact assessed

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Queue / failed_jobs | DevOps / Backend | Retry or flush with approval |

## Step-by-step Procedure

### 1. Diagnose

1. Check worker liveness, all configured queue depths/oldest ages, `php artisan queue:failed`, and worker logs.
2. Identify poison messages, dependency outages, timeout/retry-after mismatch, and systemic failures before retrying.

### 2. Recover

1. Stop or fix the failing producer/dependency, then run `php artisan queue:restart`.
2. Retry a reviewed failure with `php artisan queue:retry {uuid-or-id}`; retry larger sets only in observed batches.
3. Use `queue:forget {uuid-or-id}` only for an unrecoverable job after retaining evidence. `queue:flush` permanently discards all failed-job records and is prohibited as routine recovery.
4. Run the benign queue canary from [Queues and Scheduler](../../deployment/queues-and-scheduler.md).
5. Confirm depth and oldest age trend down, failures stop increasing, and affected work completed exactly once.

## Validation Checklist

- [ ] Workers healthy
- [ ] Backlog trending down
- [ ] Failed-job retry outcomes recorded
- [ ] Canary processed and no new failures created
- [ ] Customer-impacting jobs unblocked or communicated
- [ ] Root cause noted
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Stop retries if amplifying failure; disable producer if needed; escalate incident (SOP-SUP-002).

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
| **Previous SOP** | [SOP-MNT-006 — Log Rotation](SOP-MNT-006-log-rotation.md) |
| **Next SOP** | [SOP-MON-002 — Queue Monitoring](../monitoring/SOP-MON-002-queue-monitoring.md) |
| **Related SOPs** | [SOP-DEP-004](../deployment/SOP-DEP-004-queue-workers.md), [SOP-SUP-002](../support/SOP-SUP-002-incident-response.md) |
| **Related Documents** | [Technical operations notes](../technical-operations.md) |
| **Required Forms** | Queue recovery ticket |
| **Required Checklists** | Failed job triage checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
