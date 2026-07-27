# Onboarding API

Base: `/platform/api/onboarding` (platform session auth)

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/` | Create onboarding |
| `GET` | `/{onboarding}` | Resume / show |
| `POST` | `/{onboarding}/steps` | Complete or skip step |
| `GET` | `/{onboarding}/progress` | Progress + steps |
| `GET` | `/{onboarding}/validation` | Go-live checklist |
| `POST` | `/{onboarding}/finish` | Complete wizard |

## Complete step

```json
{
  "step": "organization",
  "skip": false,
  "payload": {
    "name": "Acme",
    "plan": "enterprise",
    "timezone": "UTC",
    "currency": "USD"
  }
}
```

Requires `platform.organizations.manage` (view endpoints need `platform.organizations.view`).
