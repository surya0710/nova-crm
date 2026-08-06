# SOP-SAL-004 — Proposal Creation

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SAL-004 |
| **Title** | Proposal Creation |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Sales |
| **Owner** | Account Executive |
| **Reviewer** | Sales Manager |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Produce a clear, priced proposal that matches discovery scope, approved SKUs, and implementation assumptions.

## Scope

- **In scope:** Proposal drafting, peer review, and alignment to pricing guide.
- **Out of scope:** Discount approval (SOP-SAL-005) and contract execution (SOP-SAL-006).

## Preconditions

- [ ] Discovery and demo outcomes logged
- [ ] Current [Pricing Guide](../../sales/pricing-guide.md) available
- [ ] Proposal template available

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Proposal template | AE | Clone from docs/sales |
| CRM Opportunity | AE | Attach proposal |

## Step-by-step Procedure

### 1. Draft

1. Clone [Proposal Template](../../sales/proposal-template.md).
2. Include scope, modules, seats, implementation package, timeline, assumptions, and exclusions.
3. Align line pricing with the Pricing Guide.

### 2. Peer review

1. Sales Manager reviews before customer send.
2. Resolve scope/price conflicts; do not send unapproved discounts (see SOP-SAL-005).

### 3. Send and track

1. Send via agreed channel; set validity (default 30 days).
2. Attach PDF to Opportunity; set stage to Proposal / Negotiation.

## Validation Checklist

- [ ] Proposal matches discovery scope
- [ ] Pricing aligned to guide or pending approval ticket
- [ ] Manager peer-review completed
- [ ] Proposal attached to Opportunity
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If an incorrect proposal was sent, issue a corrected revision with a new version number, notify the prospect, and void the prior PDF in CRM.

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
| **Previous SOP** | [SOP-SAL-003 — Product Demonstration](SOP-SAL-003-product-demonstration.md) |
| **Next SOP** | [SOP-SAL-005 — Pricing Approval](SOP-SAL-005-pricing-approval.md) |
| **Related SOPs** | [SOP-SAL-005](SOP-SAL-005-pricing-approval.md), [SOP-BIL-001](../billing/SOP-BIL-001-new-subscription.md) |
| **Related Documents** | [Proposal Template](../../sales/proposal-template.md), [Pricing Guide](../../sales/pricing-guide.md) |
| **Required Forms** | Proposal Template |
| **Required Checklists** | Proposal peer-review checklist (scope, SKUs, assumptions, exclusions) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
