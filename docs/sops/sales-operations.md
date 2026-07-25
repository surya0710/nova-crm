# SOP — Sales Operations

> **Superseded for execution by Phase 15.1.1 numbered SOPs.**  
> Use [INDEX.md](INDEX.md) → Sales (`SOP-SAL-001` … `SOP-SAL-007`). This family document is retained for deep-link compatibility.

---
**Document control**
| Field | Value |
|-------|-------|
| Version | 1.1 |
| Owner | Operations |
| Review cadence | Quarterly |
| Last reviewed | 2026-07-25 |
| Status | Legacy reference (see INDEX) |

## Purpose
Standardize the full sales lifecycle from lead intake through contract execution and customer handoff.

## Roles
| Role | Responsibility |
|------|----------------|
| SDR / BDR | Lead qualification |
| Account Executive (AE) | Discovery, demo, proposal, close |
| Sales Manager | Pipeline, pricing approval |
| Solutions / SE | Complex demos and technical fit |
| Legal / Ops | Contract execution |
| Customer Success | Post-signature handoff |

## Prerequisites
- CRM access with leads/opportunities permissions
- Current [Pricing Guide](../sales/pricing-guide.md)
- Demo environment ready ([Demo Data Guide](../demos/data-guide.md))
- Proposal template available

## 1. Lead qualification
**Entry:** New lead (inbound, referral, outbound, partner).

1. Capture source, company, contact, need, timeline, and budget signal in CRM.
2. Apply BANT-lite score within 1 business day (Need, Authority, Timeline, Fit).
3. Disposition: **Qualified** → book discovery; **Nurture** → follow-up task; **Disqualified** → close with reason.

**Exit:** Lead status updated; next activity scheduled.

## 2. Discovery call checklist
- [ ] Confirm attendees and roles
- [ ] Confirm current tools and pain points
- [ ] Map modules of interest (CRM, Projects, HRMS, Marketing, Analytics)
- [ ] Confirm users / seats / branches / locations
- [ ] Confirm integrations (email, ads, SSO, payroll, storage)
- [ ] Confirm success metrics
- [ ] Confirm decision process and competitors
- [ ] Agree demo scope and date
- [ ] Log notes on Opportunity / Lead activity

## 3. Requirement gathering
1. Convert qualified lead to Opportunity when budget/timeline confirmed.
2. Record amount estimate, expected close, stage, owner.
3. Attach discovery notes and any RFP / checklist.
4. Flag implementation complexity (data import, custom roles, multi-branch).

## 4. Product demonstration
1. Use [Master Demo Script](../demos/master-demo-script.md) or matching [industry scenario](../demos/industry-scenarios.md).
2. Prefer shared Nova Enterprises demo org — never production customer data.
3. Limit to agreed modules; park deep-dives for follow-up.
4. Capture objections as CRM activities + optional feature request ticket.

## 5. Proposal preparation
1. Clone [Proposal Template](../sales/proposal-template.md).
2. Include scope, modules, seats, implementation package, timeline, assumptions, exclusions.
3. Align pricing with [Pricing Guide](../sales/pricing-guide.md).
4. Peer-review with Sales Manager before customer send.

## 6. Pricing approval
| Discount / exception | Approver |
|----------------------|----------|
| 0–10% list | AE |
| 11–20% | Sales Manager |
| >20% or custom terms | Sales Director + Finance |
| Free months / pilots | Sales Director |

Log approval on the Opportunity before quotation send.

## 7. Quotation generation
1. Create Quotation in CRM linked to Opportunity / Customer.
2. Line items must match approved proposal SKUs.
3. Validity period default: 30 days.
4. Status flow: Draft → Sent → Accepted / Rejected / Expired.

## 8. Contract execution
1. Issue MSA + Order Form (or equivalent).
2. Confirm legal entity, billing contact, payment terms, start date.
3. Collect signatures (e-sign preferred).
4. Store executed PDF in deal record.
5. Update Opportunity stage to **Closed Won**.

## 9. Customer handoff
Within **2 business days** of signature:
- [ ] Create onboarding ticket with signed Order Form attached
- [ ] Introduce CS / Implementation owner to customer
- [ ] Transfer discovery notes and success metrics
- [ ] Confirm go-live target date
- [ ] Schedule kickoff

Use [Customer Handoff Checklist](../onboarding/handoff-checklist.md).

## 10. Deal closure
**Closed Won:** Opportunity Closed Won; Quotation Accepted; handoff ticket opened.  
**Closed Lost:** Record primary loss reason, competitor, optional re-engage date.

## 11. Sales pipeline management
| Cadence | Action |
|---------|--------|
| Daily | Update next steps on active deals |
| Weekly | Pipeline review with Sales Manager |
| Monthly | Forecast accuracy vs closed revenue |
| Quarterly | ICP and stage conversion review |

**Hygiene:** No Opportunity without owner/amount/close date; stale deals (>14 days no activity) updated or recycled.

## Exit criteria
Contract signed **or** Closed Lost with reason; handoff complete for wins.