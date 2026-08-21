# Workflow Email

Workflows send CRM email through the existing engine: `RunTriggeredWorkflows` → `SendCrmEmailAction` → `CrmEmailService`.

## Actions

- **Send CRM email** — subject, body, optional template ID, CC/BCC
- **Send CRM email template** — requires template ID

Recipients: customer, primary contact, or record owner. Organization default CC/BCC still apply. The workflow actor is the sender. Sends are not auto-CC’d to the actor.

## Triggers (CRM)

Lead created; customer created; customer lifecycle changed; opportunity stage / won / lost; quotation sent / accepted; sales order confirmed; invoice created / due soon / overdue; payment received; ticket created / escalated.

Idempotency: one workflow execution per event, plus `crm_email_messages.idempotency_key` for the action. Email remains at-least-once if the provider accepts a retry after a successful SMTP handoff.
