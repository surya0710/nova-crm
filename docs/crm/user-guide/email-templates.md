# CRM Email Templates

Templates live under **Settings → Email templates**. They are organization-scoped, category-licensed, and rendered by `CrmEmailVariableRenderer` before `CrmEmailService` queues the send.

## Variables

Use `{{customer.name}}`, `{{customer.email}}`, and the category keys listed in the template editor. Unavailable variables are left blank rather than leaking other-tenant data.

## Modules and license

Categories can require the CRM (or HRMS) module. Inactive or out-of-license templates cannot be selected in the composer or workflow actions.

## Permissions

- `email_templates.view` — read templates
- `email_templates.manage` — create, update, delete

## API

See [CRM email API](../api/email.md).
