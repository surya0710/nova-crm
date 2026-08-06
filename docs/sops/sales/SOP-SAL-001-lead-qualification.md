# SOP-SAL-001 — Lead Qualification

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SAL-001 |
| **Title** | Lead Qualification |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Sales |
| **Owner** | SDR / BDR Lead |
| **Reviewer** | Sales Manager |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Qualify inbound and outbound leads so only ICP-fit opportunities enter the discovery pipeline within one business day.

## Scope

- **In scope:** Lead capture, BANT-lite scoring, disposition (Qualified / Nurture / Disqualified), and CRM hygiene for new leads.
- **Out of scope:** Discovery calls (SOP-SAL-002), demos, proposals, and contract work.

## Preconditions

- [ ] CRM access with leads permissions
- [ ] Lead source and contact details available
- [ ] Current ICP definition reviewed (Sales Manager)

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| CRM Leads | SDR / BDR | Create and update leads |
| Knowledge Center | Read | ICP and product fit references |

## Step-by-step Procedure

### 1. Capture the lead

1. Create or update the Lead record with source, company, contact, need, timeline, and budget signal.
2. Assign an owner within 4 business hours of intake.
3. Log the intake channel (inbound web, referral, outbound, partner).

### 2. Score with BANT-lite

1. Score **Need**, **Authority**, **Timeline**, and **Fit** within 1 business day.
2. Mark **Qualified** when Need + Fit are strong and a next meeting can be booked.
3. Mark **Nurture** when timing is weak but ICP fit exists; create a follow-up task.
4. Mark **Disqualified** with a primary reason (out of ICP, no need, competitor lock-in, etc.).

### 3. Route next action

1. Qualified → book discovery and advance to SOP-SAL-002.
2. Nurture → schedule cadence task; do not create Opportunity yet.
3. Disqualified → close lead; optionally set re-engage date.

## Validation Checklist

- [ ] Lead status updated in CRM
- [ ] Owner assigned
- [ ] Next activity scheduled or lead closed with reason
- [ ] Qualification notes recorded on the lead
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If a lead was incorrectly disqualified, reopen the lead, correct disposition, and notify the prior owner. If incorrectly qualified, move to Nurture or Disqualified with reason before discovery is held.

## Exceptions

| Exception | Handling | Approver |
|-----------|----------|----------|
| Strategic / partner-referred lead | Skip nurture queue; AE owns within 4 hours | Sales Manager |

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
| **Previous SOP** | None (lifecycle start) |
| **Next SOP** | [SOP-SAL-002 — Discovery Call](SOP-SAL-002-discovery-call.md) |
| **Related SOPs** | [SOP-SAL-002](SOP-SAL-002-discovery-call.md), [SOP-CS-001](../customer-success/SOP-CS-001-welcome-process.md) |
| **Related Documents** | [Pricing Guide](../../sales/pricing-guide.md), [Company Profile](../../sales/company-profile.md) |
| **Required Forms** | Lead intake form / web-to-lead payload |
| **Required Checklists** | BANT-lite scorecard (inline above) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
