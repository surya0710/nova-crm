# Incident Response Plan

## Severity
Use Support SOP priorities (P1–P4).

## P1 workflow
1. **Detect** — monitoring, customer, or internal
2. **Declare** — incident channel + Incident Commander
3. **Mitigate** — restore service (rollback, scale, feature flag, config)
4. **Communicate** — status to Support/CS every ≤60 minutes
5. **Resolve** — confirm with monitoring + customer path
6. **RCA** — within 5 business days

## Roles
| Role | Duty |
|------|------|
| Incident Commander | Decisions, comms cadence |
| Tech Lead | Diagnosis / fix |
| Comms | Customer-facing updates via Support/CS |
| Scribe | Timeline notes |

## Evidence to capture
Timestamps, deploys in window, error samples, org impact scope, customer IDs (if any).

## Do not
- Delete logs prematurely
- Run destructive DB commands under pressure without approval
- Speculate root cause in customer channels