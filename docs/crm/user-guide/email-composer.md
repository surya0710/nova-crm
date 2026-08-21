# Email Composer

The composer is the same Blade form on customer, contact, opportunity, ticket, quotation, sales order, invoice, payment, and credit/debit note pages.

## Behavior

- Recipients: To, optional CC/BCC
- Organization default CC/BCC are merged and de-duplicated
- Commercial documents (quote, order, invoice, payment, adjustment note) can auto-CC the sender when that option is on
- Customer / contact / opportunity / ticket composer does **not** auto-CC the sender
- Optional template and signature
- Optional attachments stored under `storage/app/private/crm-email/{org}/{message}`
- Reply fields (`in_reply_to`, `thread_id`, `references`) keep the conversation together

Sends go through `CrmEmailService` → `SendCrmEmailJob` on the `mail` queue → `OrganizationMailer`.
