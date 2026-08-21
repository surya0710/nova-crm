# CRM User Guide - Reports

## Purpose
Use CRM reports and dashboard widgets for pipeline, quotations, sales orders, invoices, receivables, and revenue.

## Who should use this feature
Sales managers, operations analysts, and leadership users.

## Prerequisites
- `reports.view`, `quotations.view`, `sales_orders.view`, `invoices.view`, and/or `finance.view`
- Dashboard widgets enabled for the CRM module
- CRM subscription module on the organization plan

## Commercial metrics
Dashboard widgets (CRM section), filtered by organization, plan, and the viewer’s RBAC:

- **Quotations** — count, value, accepted quotation value, conversion rate (converted ÷ accepted + converted)
- **Sales Orders** — count, value, confirmed count/value
- **Invoices** — count/value, paid, outstanding, overdue
- **Receivables** — outstanding and overdue receivables plus collected revenue (`invoices.view` or `finance.view`)
- **Commercial revenue** — collected revenue, collection rate, monthly revenue, and breakdowns by customer, product, and salesperson
- **CRM email** — queued, sent, delivered, failed, bounced, delivery/failure rates (`crm_email.view`)

The CRM home revenue summary also shows outstanding AR, invoice/payment counts, quotation conversion, and overdue invoices.

Open **CRM → Reports → Email** for the same metrics with a date range. The Communications Center is the thread inbox, not a second reporting engine.

Finance AR reports (`reports.finance` and outstanding/revenue CSV) are CRM-licensed, not Analytics. They follow `reports.view` or `finance.view` and stay available on starter CRM plans. Cross-module analytics reports remain on the Analytics module (professional+).

## Step-by-step instructions
1. Open Home Workspace or Dashboard and add the commercial widgets if missing (reset layout / provision widgets).
2. Open **Reports → Finance** for outstanding and revenue exports.
3. Apply owner, customer, and date filters for comparable periods.

## Expected result
Teams see quotation conversion, order volume, invoice health, collection rate, and revenue mix without leaving the dashboard.

## Best Practices
Use the same date filters when comparing conversion rate and revenue.

## Common Mistakes
Comparing draft quotations with issued invoice totals, or treating outstanding AR as collected revenue.

## FAQ
Widget data respects organization isolation and the viewer’s permissions. Conversion rate ignores drafts. Users without the widget permission receive an unauthorized payload, not another tenant’s numbers.
