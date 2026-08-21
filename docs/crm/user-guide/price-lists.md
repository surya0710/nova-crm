# CRM User Guide - Price Lists

## Purpose
Maintain multiple price lists, customer-specific and quantity-based prices, discount rules, effective dates, and price history.

## Who should use this feature
Catalog and sales operations users with `price_lists.view` / `create` / `update`.

## Prerequisites
- Product catalog
- Optional customer records for assigned lists

## Step-by-step instructions
1. Create a price list with currency, status, start/end dates, and optional **default** flag.
2. Add product rows with unit price, min/max quantity, tax-inclusive flag, and effective dates.
3. Assign customers with a priority (higher wins).
4. Optional discount rules (percent or fixed) can target a product and/or customer.
5. On quotations and sales orders, choosing a product resolves: customer lists by priority → default list → catalog `unit_price`, then quantity breaks, then the best discount rule.

## Expected result
Line items pick the applicable price automatically. Catalog and list price changes write **price history**.

## Best Practices
Use quantity breaks for volume pricing instead of one-off quote edits. Keep one default list per organization.

## Common Mistakes
Expecting an inactive or expired list to apply. Quantity below `min_quantity` falls through to the next list or catalog.

## FAQ
Tax rate still comes from the product; lists can override tax-inclusive and unit price only.
