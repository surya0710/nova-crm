# CRM Email Architecture

CRM email is a thin layer on existing platforms. Do not add a second mailer, workflow engine, timeline, notification system, or reporting stack.

```
Composer / API / Workflow action
  → CrmEmailService (template + CC + message row)
  → SendCrmEmailJob (queue: mail)
  → OrganizationMailer (org SMTP/provider)
  → CrmEmailDeliveryService (status)
  → CrmActivityService::logEmail (timeline)
  → CrmEmailConversationService (thread)
```

Webhooks (`CrmEmailWebhookController`) only update delivery on the owning organization’s messages.

Reporting uses `CrmEmailMetricsService` with the existing dashboard widget `crm_email_metrics` and CRM reports hub.

RBAC reuses `customers.*`, `email_templates.*`, and `crm_email.view`.
