# Pilot Risk Register

| Risk ID | Category | Description | Likelihood | Impact | Mitigation | Owner | Status |
|---------|----------|-------------|------------|--------|------------|-------|--------|
| RISK-P15.8-001 | Deployment | GA claimed without staging/production deploy evidence | Medium | High | Conditional GA on app; hard gate on infra report | DevOps | Open |
| RISK-P15.8-002 | Data | Customers expect HRMS/Projects CSV import at go-live | Medium | Medium | Set expectations in Order Form; use seeder/UI; backlog adapters | Sales / Product | Open |
| RISK-P15.8-003 | Adoption | Pilot cohort too homogeneous | Low | Medium | Profiles A–E cover distinct module mixes | CS | Mitigated |
| RISK-P15.8-004 | Security | MFA not enforced on all pilots | Medium | Medium | Enable MFA policy for enterprise pilots before GA | Security | Watch |
| RISK-P15.8-005 | Ops | Local queue/cache success mistaken for production readiness | Medium | High | Explicit language in deploy report; launch checklist split | Program Lead | Mitigated |
| RISK-P15.8-006 | Compatibility | `organization:upgrade --all` on large fleet takes long | Low | Medium | Dry-run; per-org flag; monitor duration in staging | Engineering | Watch |
