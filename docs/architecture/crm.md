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
Lead created, opportunity updated, quotation sent/accepted/converted, invoice issued, payment posted.

## Notifications
Assignment alerts, follow-up reminders, invoice issued/cancelled.

## Audit
Auditable models log created/status events. Customer timeline merges notes with quotation and invoice audit events.

## RBAC
Map permissions for sales executive, manager, and finance roles. All queries are organization-scoped.

## Extension Points
Custom fields (metadata), automations, and dashboard widgets (`commercial_quotations`, `commercial_invoices`, `commercial_revenue`).

## Future Improvements
Pipeline analytics and advanced segmentation support.
