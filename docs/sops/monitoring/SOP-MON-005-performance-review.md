# SOP-MON-005 — Performance Review

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-MON-005 |
| **Title** | Performance Review |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Monitoring |
| **Owner** | Backend Lead / Ops |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Periodically review performance signals to prevent chronic degradation.

## Scope

- **In scope:** Weekly/monthly review of latency, DB slow queries, and capacity.
- **Out of scope:** Emergency incident performance firefighting.

## Preconditions

- [ ] Metrics available for period under review

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| APM / DB metrics | Backend / Ops | Analyze trends |

## Step-by-step Procedure

### 1. Review trends

1. Compare latency and error rates vs prior period.
2. Identify top slow endpoints/queries.
3. Create improvement tickets; feed release planning (SOP-REL-001).

## Validation Checklist

- [ ] Review notes published
- [ ] Top issues ticketed
- [ ] Capacity risks flagged to Ops
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If review blocked by missing metrics, restore monitoring first (SOP-MON-001) then reschedule review.

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
| **Previous SOP** | [SOP-MON-004 — Error Log Review](SOP-MON-004-error-log-review.md) |
| **Next SOP** | [SOP-REL-001 — Release Preparation](../release-management/SOP-REL-001-release-preparation.md) |
| **Related SOPs** | [SOP-MON-001](SOP-MON-001-daily-health-check.md) |
| **Related Documents** | [Monitoring checklist](../../operations/monitoring-checklist.md) |
| **Required Forms** | Performance review template |
| **Required Checklists** | Capacity risk checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
