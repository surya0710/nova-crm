# CRM Architecture

## Purpose
Technical blueprint for CRM module internals.

## Diagram
CRM UI -> CRM Controllers -> Form Requests -> Services -> Models (no repositories)

## Database Tables
Leads, customers, product_categories, products, opportunities, quotations, quotation_items, invoices, invoice_items, payments.

Commercial documents store GST snapshot columns (CGST/SGST/IGST/UTGST/cess) plus JSON `billing_snapshot` / `shipping_snapshot`. Conversion copies snapshots and does not recalculate tax.

## Services
- Product / ProductCategory catalog
- TaxDeterminationService + TaxCalculationService
- QuotationService, InvoiceService, QuotationConversionService
- QuotationPdfService, InvoicePdfService
- ClientEmailCc + OrganizationMailer
- CommercialTimelineService, CommercialMetricsService

## Controllers
HTTP controllers for lead, customer, opportunity, product, quotation, and invoice domains. API counterparts under `App\Http\Controllers\Api`.

## Policies
Role-based access for sales, finance, and admin functions. Categories use `products.*`. Send uses update. Convert requires `invoices.create` and `quotations.view`.

## Workflow Events
Lead created, customer created/updated, opportunity created/won/lost/stage changed, quotation created/accepted, sales order confirmed/status changed, invoice issued, first invoice, payment received, first payment, ticket created/assigned/status changed.

## Notifications
Assignment alerts, follow-up reminders, invoice issued/cancelled, workflow `notify_user` actions.

## Audit
Auditable models log created/status events. Customer timeline merges notes, ticket notes, CRM activities, and commercial audit events.

## RBAC
Map permissions for sales executive, manager, and finance roles. Contacts, tickets, and sales activities reuse `customers.*`. All queries are organization-scoped.

## Extension Points
Custom fields (metadata), Workflow triggers/actions (`change_customer_lifecycle`), dashboard widgets (`commercial_quotations`, `commercial_invoices`, `commercial_revenue`, `crm_tickets`, `sales_forecast`).

## Future Improvements
Pipeline analytics and advanced segmentation support.
