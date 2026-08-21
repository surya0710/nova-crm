# CRM Email Configuration

Organization email is configured once in **Settings → Email**. Outbound CRM mail always uses `OrganizationMailer` and `CrmEmailService` — there is no second mail engine.

## Providers

- SMTP, Gmail, Outlook, SendGrid, Mailgun, or log (development)
- From name/address and optional reply-to
- Default CC/BCC applied to every CRM send
- Optional HTML signature
- Enable/disable sending for the organization

SMTP passwords are encrypted at rest. The UI and APIs expose `has_password` only — never the secret.

## Isolation

Mail settings live on the organization record. Switching workspace loads that organization’s provider, from-address, and signature. Webhook tokens are also per organization.

## Related

- [Templates](email-templates.md)
- [Composer](email-composer.md)
- [Delivery tracking](email-delivery.md)
- [Webhook setup](email-webhooks.md)
