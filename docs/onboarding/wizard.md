# Organization Onboarding Wizard

Konnect Nex Release **1.1.5** provides a guided Organization Onboarding Wizard for Platform Administrators.

## Architecture

```
Platform → Onboarding Wizard → Existing Services
  OrganizationManagementService
  PlatformLicensingService / PlatformSubscriptionService
  OrganizationMemberService / UserInvitationService
  Import Center (deep links)
  OrganizationBrandingService
  OrganizationMailConfig / OrganizationMailer
  PlatformProviderService
```

The wizard orchestrates; it does not duplicate business logic.

## Steps

1. Organization Information
2. Subscription & Modules
3. Organization Structure
4. Users & Employees
5. Data Import
6. Branding
7. Communication Settings
8. Provider Integrations
9. Go-Live Checklist

## UI

- **Platform → Onboarding** — session list + start
- Wizard workspace with progress, draft/resume, previous/next, skip
- Platform dashboard widget: pending / in progress / ready / failed

## APIs

Under platform auth: `/platform/api/onboarding/*`

See [API docs](../api/onboarding.md).

## Best practices

1. Prefer the wizard for net-new customers; keep classic Create Organization for quick sandbox tenants.
2. Invite the organization admin via Identity (step 4) instead of sharing temporary passwords.
3. Defer heavy imports; complete them after admin accepts invite and you impersonate.
4. Use `log` mail driver in non-production to validate configuration without SMTP.

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Cannot skip organization/modules | Required steps |
| Go-live blocked | Resolve required checklist failures |
| Invite not emailed | Configure org mail (step 7) or set notify=false and share accept link from Identity |
| Empty module list | Plan may not include modules; assign enterprise or explicit modules |

## Related SOPs

- SOP-ONB-001 … SOP-ONB-007 under `docs/sops/onboarding/`
