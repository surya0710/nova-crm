# CRM Email API

All routes are under `/api/v1` with Sanctum, `X-Organization-Id`, `api.access`, and the entity permission. Other-organization IDs return **404**. SMTP passwords, webhook secrets, encryption keys, and attachment disk paths are never returned.

| Method | Path | Permission |
|--------|------|------------|
| POST | `/api/v1/crm/email/send` | `customers.create` or `customers.update` |
| GET | `/api/v1/crm/email/messages` | `crm_email.view` or `customers.view` |
| GET | `/api/v1/crm/email/messages/{id}` | same |
| GET | `/api/v1/crm/email/conversations` | same |
| GET | `/api/v1/crm/email/conversations/{id}` | same |
| GET | `/api/v1/crm/email/conversations/{id}/messages` | same |
| GET | `/api/v1/crm/email/metrics` | same |
| GET | `/api/v1/crm/email/templates` | `email_templates.view` |
| POST/PUT/PATCH/DELETE | `/api/v1/crm/email/templates` | `email_templates.manage` |

## Send body

`related_type` (`customer`, `contact`, `lead`, `opportunity`, `ticket`, `quotation`, `sales_order`, `invoice`, `payment`, `adjustment_note`), `related_id`, `email`, optional `cc`, `bcc`, `subject`, `message`, `template_id`, `include_signature`.

Query: `search`, `status`, `customer_id`, `template_id`, `sent_by`, `from`, `to`, `per_page`.

Metrics include queued/sent/delivered/failed/bounced counts, delivery and failure rates, and breakdowns by salesperson, customer, template, date, and opportunity.
