# CRM User Guide - Sales Orders

## Purpose
Turn an accepted quotation into a sales order, track fulfillment status, and convert the order to an invoice.

## Who should use this feature
Sales teams with `sales_orders.view` / `sales_orders.create` / `sales_orders.update`. Finance users with `invoices.create` convert confirmed orders to invoices.

## Prerequisites
- Customer record
- Optional accepted quotation for conversion

## Step-by-step instructions
1. Create a **draft** sales order, or convert an **accepted** quotation. Conversion copies SKU, HSN/SAC, quantity, UOM, price, discount, tax, billing/shipping snapshots, and terms.
2. Set order date, expected delivery date, and terms & conditions.
3. Confirm the order, then move it through **Processing**, **Partially fulfilled**, and **Fulfilled**. Cancelled orders cannot convert.
4. Download PDF or email the customer. The authenticated sender is CC’d; extra CC recipients are de-duplicated.
5. Convert the sales order to a **draft** invoice. Repeat convert returns the existing non-cancelled invoice and keeps the quotation reference.

## Expected result
Quote → sales order → invoice stays linked, with an audit trail and customer timeline events.

## Best Practices
Confirm line items before converting to invoice. Do not delete an order that already has an active invoice.

## Common Mistakes
Converting a quotation that is not accepted, or converting a cancelled sales order.

## FAQ
Independent invoices (without a sales order) still work. Legacy quotations that were converted directly to invoices remain visible from the quotation.
