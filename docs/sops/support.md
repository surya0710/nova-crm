# SOP — Support

---
**Document control**
| Field | Value |
|-------|-------|
| Version | 1.0 |
| Owner | Operations |
| Review cadence | Quarterly |
| Last reviewed | 2026-07-25 |
| Status | Approved for launch use |

## Purpose
Handle customer support consistently across tickets, priority, escalation, incidents, bugs, feature requests, SLAs, and release communication.

Detailed procedures: [Support Handbook](../support/README.md)

## 1. Ticket handling
1. Acknowledge within SLA response time.
2. Classify: How-to, Defect, Incident, Feature request, Account/billing.
3. Reproduce with org context (use secure channel for credentials).
4. Resolve or escalate; document resolution.
5. Confirm customer acceptance before close.

## 2. Priority classification
| Priority | Definition | Examples |
|----------|------------|----------|
| P1 Critical | Production down / data loss risk | Login outage, org-wide payment failure |
| P2 High | Major feature blocked | Cannot create invoices; payroll blocked |
| P3 Medium | Degraded / workaround exists | Slow report; UI glitch |
| P4 Low | Cosmetic / question | Label typo; how-to |

## 3. Escalation
| From | To | When |
|------|-----|------|
| L1 Support | L2 Engineering | Confirmed defect or needs code/logs |
| L2 | On-call / Platform | P1 or security |
| Any | Product | Feature request / prioritization |

Escalate with: org ID, steps, expected vs actual, timestamps, screenshot/HAR if UI.

## 4. Incident response
Follow [Incident Response Plan](../operations/incident-response-plan.md).

## 5. Root cause analysis
For P1/P2: RCA within 5 business days — timeline, impact, root cause, fix, preventions.

## 6. Bug reporting
Include environment, organization, user role, steps, expected vs actual, severity, release version.

## 7. Feature requests
Log impact, workaround, module, pilot willingness. Route to Product — do not promise dates in support chat.

## 8. Customer communication
Acknowledge → diagnose → update → resolve → confirm. P1 updates at least every 60 minutes until mitigated.

## 9. SLA handling
See [SLA Matrix](../support/sla-matrix.md). Breach: notify Support Lead; document reason and recovery.

## 10. Release communication
- [ ] Release notes drafted
- [ ] Known issues listed
- [ ] Maintenance window communicated (if downtime)
- [ ] Support briefed on changes

## Exit criteria
Ticket resolved with customer confirmation, or parked with owner and due date.