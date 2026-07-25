# SOP-SAL-006 — Contract Execution

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SAL-006 |
| **Title** | Contract Execution |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Sales |
| **Owner** | Account Executive / Legal Ops |
| **Reviewer** | Sales Manager |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Execute binding commercial agreements and mark the opportunity Closed Won with complete billing and legal artifacts.

## Scope

- **In scope:** MSA / Order Form issuance, signature collection, deal record storage, and Closed Won update.
- **Out of scope:** Onboarding kickoff (SOP-SAL-007 / SOP-ONB-001).

## Preconditions

- [ ] Approved proposal / quotation
- [ ] Legal entity and billing contact confirmed
- [ ] Pricing approval complete when applicable

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| E-sign / contract vault | AE / Legal Ops | Issue and store signed PDF |
| CRM | AE | Close Opportunity |

## Step-by-step Procedure

### 1. Issue documents

1. Issue MSA + Order Form (or equivalent).
2. Confirm legal entity, billing contact, payment terms, and start date.

### 2. Collect signatures

1. Prefer e-sign.
2. Store executed PDF on the deal record.
3. Create Quotation in CRM if not already present; set Accepted.

### 3. Close the deal

1. Update Opportunity stage to **Closed Won**.
2. Trigger sales handover within 2 business days (SOP-SAL-007).

## Validation Checklist

- [ ] Signed Order Form / MSA stored
- [ ] Opportunity Closed Won
- [ ] Quotation Accepted
- [ ] Billing contact and start date recorded
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If signature was on incorrect terms, do not provision the customer. Void or amend via countersigned change order before onboarding starts.

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
| **Previous SOP** | [SOP-SAL-005 — Pricing Approval](SOP-SAL-005-pricing-approval.md) |
| **Next SOP** | [SOP-SAL-007 — Sales Handover](SOP-SAL-007-sales-handover.md) |
| **Related SOPs** | [SOP-SAL-007](SOP-SAL-007-sales-handover.md), [SOP-BIL-001](../billing/SOP-BIL-001-new-subscription.md), [SOP-ONB-001](../onboarding/SOP-ONB-001-customer-onboarding.md) |
| **Related Documents** | [Proposal Template](../../sales/proposal-template.md) |
| **Required Forms** | MSA, Order Form, Accepted Quotation |
| **Required Checklists** | Contract completeness (entity, billing, terms, start date, signatures) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
