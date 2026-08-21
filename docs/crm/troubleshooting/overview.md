# CRM Troubleshooting Overview

## Problem
Unexpected behavior in CRM workflows or records.

## Symptoms
- Missing records or wrong stage state
- Failed quotation/invoice transitions
- Permission denied for expected action
- Ticket SLA / overdue not showing (priority SLA hours or `due_at` missing)
- Forecast widget empty (opportunities need `amount` and probability; closed deals are excluded from weighted pipeline)
- CRM email stays queued (mail worker not listening to the `mail` queue)
- Email never reaches Delivered on SMTP (only SendGrid/Mailgun webhooks advance delivery)
- Webhook 401 (bad signing secret or unsigned payload)
- Workflow email did not send (mail disabled, missing recipient address, or trigger not in `AppServiceProvider` listeners)

## Possible Causes
- Incomplete configuration
- Invalid data or status preconditions
- RBAC or policy restrictions

## Resolution
1. Confirm record state and required fields.
2. Verify permissions and policy mappings.
3. Re-run action after correcting constraints.

## Prevention
Use validated workflows, role reviews, and periodic config audits.
