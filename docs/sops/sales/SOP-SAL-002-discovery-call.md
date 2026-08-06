# SOP-SAL-002 — Discovery Call

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SAL-002 |
| **Title** | Discovery Call |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Sales |
| **Owner** | Account Executive |
| **Reviewer** | Sales Manager |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Confirm business pain, module fit, decision process, and demo scope so proposals and demos are scoped accurately.

## Scope

- **In scope:** Pre-call prep, discovery agenda, requirement capture, and opportunity creation.
- **Out of scope:** Product demonstration delivery (SOP-SAL-003) and pricing.

## Preconditions

- [ ] Lead marked Qualified (SOP-SAL-001)
- [ ] Discovery meeting booked with buyer and technical stakeholder when possible
- [ ] AE has reviewed lead notes and ICP

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| CRM Opportunities | AE | Create/update opportunities |
| Demo environment docs | Read | Prep module scope |

## Step-by-step Procedure

### 1. Prepare

1. Review lead notes, company site, and likely modules.
2. Confirm attendees and roles 24 hours before the call.
3. Prepare discovery agenda (pain → current tools → modules → success metrics → decision process).

### 2. Run discovery checklist

- [ ] Confirm attendees and roles
- [ ] Confirm current tools and pain points
- [ ] Map modules of interest (CRM, Projects, HRMS, Marketing, Analytics)
- [ ] Confirm users / seats / branches / locations
- [ ] Confirm integrations (email, ads, SSO, payroll, storage)
- [ ] Confirm success metrics
- [ ] Confirm decision process and competitors
- [ ] Agree demo scope and date
- [ ] Log notes on Opportunity / Lead activity

### 3. Convert to Opportunity

1. When budget/timeline signals are present, convert Lead → Opportunity.
2. Record amount estimate, expected close, stage, and owner.
3. Flag implementation complexity (data import, custom roles, multi-branch).

## Validation Checklist

- [ ] Discovery notes logged
- [ ] Demo scope and date agreed (or next step documented)
- [ ] Opportunity created when criteria met
- [ ] Complexity flags recorded
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If opportunity was created prematurely, move stage back to Qualification, keep discovery notes, and return lead status to Nurture if needed.

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
| **Previous SOP** | [SOP-SAL-001 — Lead Qualification](SOP-SAL-001-lead-qualification.md) |
| **Next SOP** | [SOP-SAL-003 — Product Demonstration](SOP-SAL-003-product-demonstration.md) |
| **Related SOPs** | [SOP-SAL-001](SOP-SAL-001-lead-qualification.md), [SOP-SAL-003](SOP-SAL-003-product-demonstration.md) |
| **Related Documents** | [Master Demo Script](../../demos/master-demo-script.md), [Industry Scenarios](../../demos/industry-scenarios.md) |
| **Required Forms** | Discovery notes template (CRM activity) |
| **Required Checklists** | Discovery call checklist (inline) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
