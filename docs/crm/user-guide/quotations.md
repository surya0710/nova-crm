# CRM User Guide - Quotations

## Purpose
Create, send, and convert customer quotations with product lines and GST-aware tax.

## Who should use this feature
Sales teams with `quotations.view` / `quotations.create` / `quotations.update`.

## Prerequisites
- Customer record (GST profile optional)
- Product catalog for reusable lines, or ad-hoc service descriptions

## Step-by-step instructions
1. Create a **draft** quotation. New quotations cannot skip draft.
2. Add product or free-text lines: quantity, UOM, unit price, line discount, tax/cess, inclusive flag.
3. Set place of supply, shipping/other charges, validity (valid until), and terms.
4. Download PDF or email the customer. Sending a draft marks it **sent**. Your mailbox is added to CC automatically; extra CC addresses are preserved and de-duplicated.
5. Mark **accepted** or **rejected**. Only accepted quotations can convert to an invoice.
6. Convert copies line items and the tax snapshot onto a new draft invoice. It does not recalculate historical tax. Repeat convert returns the existing non-cancelled invoice.

## Expected result
Quotations are priced consistently, emailed with a sender CC, and convertible without changing past tax.

## Best Practices
Use catalog products for HSN/SAC. Set expiry dates. Confirm place of supply before sending.

## Common Mistakes
Trying to create a non-draft quotation, converting before acceptance, or editing a sent quote.

## FAQ
Service-only (non-product) lines still work. Older quotes that stored a single tax % continue to total correctly as “other tax”.
