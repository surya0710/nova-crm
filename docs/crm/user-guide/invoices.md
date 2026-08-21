# CRM User Guide - Invoices

## Purpose
Issue GST-capable invoices with product lines, payment terms, due dates, and PDF/email delivery.

## Who should use this feature
Sales operations and finance users with `invoices.view` / `invoices.create` / `invoices.update`.

## Prerequisites
- Customer billing details
- Optional accepted quotation for conversion

## Step-by-step instructions
1. Create a **draft** invoice (or convert a sales order).
2. Confirm line items: SKU, HSN/SAC, quantity, UOM, unit price, discount, CGST/SGST/IGST/UTGST/cess.
3. Set due date, payment terms, shipping/other charges, and place of supply.
4. Billing and shipping addresses are snapshotted from the customer so later customer edits do not rewrite issued invoices.
5. Issue the invoice or email it. Emailing a draft also issues it. The authenticated sender is CC’d; extra CC recipients are kept without duplicates.
6. Download PDF for print or archive. Record payments until the invoice is paid.
7. Create credit or debit notes from the invoice when you need an adjustment. Applied notes change **outstanding**, not the stored invoice total.

## Expected result
Invoices stay compliant after issue: tax splits and party addresses remain as stored at save/convert time.

## Best Practices
Validate GSTIN/place of supply before issue. Do not change line items after issue.

## Common Mistakes
Editing financial lines on an issued invoice, cancelling an invoice that already has payments, or assuming live customer address changes flow into old PDFs.

## FAQ
Existing invoices without GST columns still display a simple tax total. Conversion copies quotation snapshots rather than recalculating.
