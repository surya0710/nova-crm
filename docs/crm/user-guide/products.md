# CRM User Guide - Products

## Purpose
Maintain a product and service catalog that quotations and invoices can reuse.

## Who should use this feature
Sales operations and catalog owners with `products.view` / `products.create`.

## Prerequisites
- Organization currency and tax settings in place
- Optional: GST HSN/SAC codes for Indian tax invoices

## Catalog
1. Create categories under **Products → Categories**.
2. Add products or services with SKU, unit, list price, tax rate, HSN/SAC, default discount, and inclusive/exclusive pricing.
3. Inactive items stay in history but are hidden from document pickers.
4. Free-text category is kept for older records; assigning a category updates that label automatically.

## Expected result
Line items on quotations and invoices inherit SKU, HSN/SAC, unit, tax, cess, and discount defaults.

## Best Practices
Keep HSN/SAC and tax rates current so document snapshots stay accurate.

## Common Mistakes
Deleting a category that still has products assigned. Reassign or inactivate instead.

## FAQ
Existing quotations keep the SKU/tax values stored on each line even if the catalog item later changes.
