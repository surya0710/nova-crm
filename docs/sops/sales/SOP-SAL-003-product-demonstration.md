# SOP-SAL-003 — Product Demonstration

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SAL-003 |
| **Title** | Product Demonstration |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Sales |
| **Owner** | Account Executive / Solutions Engineer |
| **Reviewer** | Sales Manager |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Deliver a scoped, professional demo that maps Konnect Nex capabilities to the prospect's agreed discovery outcomes without using production customer data.

## Scope

- **In scope:** Demo environment selection, scripted walkthrough, objection capture, and follow-up tasks.
- **Out of scope:** Proposal drafting (SOP-SAL-004) and technical POCs beyond standard demo orgs.

## Preconditions

- [ ] Discovery complete with agreed demo scope (SOP-SAL-002)
- [ ] Demo environment ready ([Demo Data Guide](../../demos/data-guide.md))
- [ ] Master or industry demo script selected

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Nova Enterprises demo org | Demo presenter | Never use production customer data |
| CRM | AE | Log demo outcome |

## Step-by-step Procedure

### 1. Select script and environment

1. Use [Master Demo Script](../../demos/master-demo-script.md) or matching [industry scenario](../../demos/industry-scenarios.md).
2. Prefer the shared Nova Enterprises demo org.
3. Limit modules to the agreed discovery scope; park deep-dives for follow-up.

### 2. Deliver the demo

1. Restate prospect goals in the first 5 minutes.
2. Walk primary workflows end-to-end; avoid feature tourism.
3. Capture objections as CRM activities.
4. Optionally open a feature-request ticket for product gaps (do not promise dates).

### 3. Close the meeting

1. Confirm interest level and remaining stakeholders.
2. Agree proposal timeline or next technical session.
3. Update Opportunity stage and next step.

## Validation Checklist

- [ ] Demo completed against agreed scope
- [ ] Objections and next steps logged in CRM
- [ ] No production customer data used
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If wrong environment or data was shown, end the share immediately, rotate any exposed credentials per SOP-SEC-003, notify Sales Manager, and reschedule with the correct demo org.

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
| **Previous SOP** | [SOP-SAL-002 — Discovery Call](SOP-SAL-002-discovery-call.md) |
| **Next SOP** | [SOP-SAL-004 — Proposal Creation](SOP-SAL-004-proposal-creation.md) |
| **Related SOPs** | [SOP-SAL-002](SOP-SAL-002-discovery-call.md), [SOP-SAL-004](SOP-SAL-004-proposal-creation.md), [SOP-SUP-004](../support/SOP-SUP-004-feature-requests.md) |
| **Related Documents** | [Master Demo Script](../../demos/master-demo-script.md), [Demo Data Guide](../../demos/data-guide.md) |
| **Required Forms** | Demo feedback form (optional) |
| **Required Checklists** | Demo scope checklist from discovery |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
