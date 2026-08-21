# CRM Architecture Overview

## Purpose
Technical structure of CRM internals and integrations.

## Diagram
CRM UI -> CRM Controllers -> CRM Services -> CRM Database

## Database Tables
Leads, contacts, customers, tickets, `crm_activities`, `customer_lifecycle_milestones`, `sales_targets`, product catalog, price lists, opportunities (`opportunity_contacts`, `opportunity_products`), quotations, sales orders, invoices, payments, credit/debit notes, reminder dispatches.

## Services
Lifecycle management (`CustomerLifecycleService` on the Workflow engine), GST tax engine, quotation/invoice/sales-order/adjustment PDFs, billing orchestration, receivables/ledger, price resolution, commercial automation, commercial timeline, `SalesForecastService`, reporting widgets.

## Workflow Events
Lead created, opportunity created/won/lost, quotation sent/accepted, sales order confirmed, invoice issued/due/overdue, first invoice/payment, ticket created/assigned/escalated/status changed, customer lifecycle changed, plus existing commercial triggers wired to `RunTriggeredWorkflows`. Workflow email actions call `CrmEmailService`.

## Controllers
HTTP controllers for lead, customer, opportunity, catalog, and billing domains (web + `/api/v1`). Portal billing JSON reuses the same portal auth stack under `/api/v1/portal/{slug}`.

## Policies
Role-based authorization for sales and finance operations; tenant isolation via organization scope. Receivables and ledger accept `invoices.view` or `finance.view`. Accounts-receivable finance reports are mapped to the CRM workspace so they follow the CRM plan, not Analytics. Contacts, tickets, and sales activities reuse `customers.*`.

## Notifications
Assignment reminders, approval alerts, due/overdue invoice reminders, quote expiry, and payment confirmations. Record email uses `OrganizationMailer` + `CrmEmailService` (see [email architecture](email.md)).

## Audit
Track edits on pipeline, quotations, sales orders, invoices, payments, and notes. Surface commercial events (including portal activity and reminders) on the customer timeline, scoped by `organization_id`.

## RBAC
Sales executive, manager, support, finance (`finance.view`), and admin permission scopes (`products.*`, `quotations.*`, `sales_orders.*`, `invoices.*`, `payments.*`, `adjustment_notes.*`, `price_lists.*`).

## Extension Points
Custom fields, automation triggers, dashboard widget providers, and REST APIs.

## Future Improvements
Automated risk scoring and quota planning beyond monthly `sales_targets`.
