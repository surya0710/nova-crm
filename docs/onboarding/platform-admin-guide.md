# Platform Administrator Guide

**Audience:** Konnect Nex SaaS operators using `/platform` (not customer tenant admins).

## Responsibilities
- Organization provisioning and suspension
- Plans / licensing
- Platform monitoring and health
- Cross-tenant operational support (with privacy controls)

## Key areas
| Topic | Route / area | Docs |
|-------|--------------|------|
| Dashboard | `platform.dashboard` | [platform-administration.md](../frontend/platform-administration.md) |
| Organizations | Platform → Organizations | Onboarding playbook Phase B |
| Monitoring | `platform.monitoring.index` | [monitoring.md](../admin-guide/monitoring.md) |
| Deployment | Ops | [deployment/overview.md](../deployment/overview.md) |

## Security rules
- Never share platform credentials with customers
- Prefer impersonation / support tools with audit logs over password exchange
- Tenant data access only for authorized support cases