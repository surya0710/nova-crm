# CRM Business Process Overview

## Overview
Defines standard flow from lead capture to payment completion.

## Actors
- Sales Representative
- Sales Manager
- Finance Officer
- Customer

## Workflow Diagram
Lead -> Opportunity -> Customer -> Quotation -> Sales Order -> Invoice -> Payment

Credit and debit notes adjust outstanding without changing stored invoice totals. Scheduled reminders cover due/overdue invoices, quote expiry, payment confirmation, and sales order status.

## Detailed Steps
1. Capture and qualify leads.
2. Convert qualified lead to opportunity and customer.
3. Issue quotation (price list resolution applies) and negotiate terms.
4. Convert accepted quotation to sales order, then to invoice.
5. Collect payment; apply credit/debit notes when needed.
6. Reconcile receivables aging and publish reports.
7. Log sales activities and tickets on the customer; lifecycle stage advances from opportunity/quote/order/invoice/payment milestones via Workflow (see lifecycle-automation).

## Exceptions
- Duplicate lead detected
- Quotation rejected or expired
- Partial payment or dispute

## Related Modules
Finance, workflow automation, metadata, and notifications.

## Best Practices
Standardize stage definitions and approval checkpoints.
