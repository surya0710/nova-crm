# SOP-SUP-003 — Bug Escalation

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SUP-003 |
| **Title** | Bug Escalation |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Support |
| **Owner** | Support Agent / L2 |
| **Reviewer** | Engineering Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Escalate confirmed defects to engineering with complete reproduction packages.

## Scope

- **In scope:** Escalation path L1→L2→Platform/on-call and bug ticket quality bar.
- **Out of scope:** Feature requests (SOP-SUP-004) and security incidents (SOP-SEC-004).

## Preconditions

- [ ] Issue reproduced or strongly evidenced
- [ ] Priority classified

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Engineering issue tracker | L2 Support | Create bug |
| Logs | L2 | Attach sanitized logs |

## Step-by-step Procedure

### 1. Escalate with package

Include: org ID, steps, expected vs actual, timestamps, screenshot/HAR if UI, environment, release version, severity.

### 2. Routing

| From | To | When |
|------|-----|------|
| L1 Support | L2 Engineering | Confirmed defect or needs code/logs |
| L2 | On-call / Platform | P1 or security |
| Any | Product | Feature request / prioritization |

See [Escalation](../../support/escalation.md).

## Validation Checklist

- [ ] Escalation package complete
- [ ] Priority correct
- [ ] Customer updated with ticket link
- [ ] Owner assigned in engineering tracker
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If escalated incorrectly, reclassify, notify engineering owner, and continue on correct path without dropping SLA clock ownership.

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
| **Previous SOP** | [SOP-SUP-002 — Incident Response](SOP-SUP-002-incident-response.md) |
| **Next SOP** | [SOP-SUP-004 — Feature Requests](SOP-SUP-004-feature-requests.md) |
| **Related SOPs** | [SOP-SUP-001](SOP-SUP-001-ticket-handling.md), [SOP-REL-001](../release-management/SOP-REL-001-release-preparation.md) |
| **Related Documents** | [Escalation guide](../../support/escalation.md) |
| **Required Forms** | Bug report template |
| **Required Checklists** | Escalation package checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
