# SOP-MON-001 — Daily Health Check

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-MON-001 |
| **Title** | Daily Health Check |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Monitoring |
| **Owner** | On-call / Ops |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Verify platform health each business day so issues are caught before customers report them.

## Scope

- **In scope:** Daily checks of uptime, auth, queues, scheduler, and error rates.
- **Out of scope:** Deep performance reviews (SOP-MON-005) and incident response.

## Preconditions

- [ ] Access to Platform Monitoring and `/up`
- [ ] [Monitoring checklist](../../operations/monitoring-checklist.md) available

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Platform → Monitoring | Ops | Read health |
| `GET /up` | Ops | Uptime probe |

## Step-by-step Procedure

### 1. Run daily checklist

1. Execute [Monitoring checklist](../../operations/monitoring-checklist.md).
2. Confirm `GET /up` green.
3. Spot-check login and one critical write path.
4. Log result on daily ops ticket or channel.

## Validation Checklist

- [ ] Uptime green
- [ ] Checklist completed
- [ ] Anomalies ticketed
- [ ] Evidence logged
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If health red, declare incident (SOP-SUP-002) and stop marking daily check complete until mitigated or accepted.

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
| **Previous SOP** | [SOP-DEP-009 — Domain Configuration](../deployment/SOP-DEP-009-domain-configuration.md) |
| **Next SOP** | [SOP-MON-002 — Queue Monitoring](SOP-MON-002-queue-monitoring.md) |
| **Related SOPs** | [SOP-MON-003](SOP-MON-003-scheduler-monitoring.md), [SOP-MON-004](SOP-MON-004-error-log-review.md), [SOP-SUP-002](../support/SOP-SUP-002-incident-response.md) |
| **Related Documents** | [Monitoring checklist](../../operations/monitoring-checklist.md) |
| **Required Forms** | Daily ops log |
| **Required Checklists** | [Monitoring checklist](../../operations/monitoring-checklist.md) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
