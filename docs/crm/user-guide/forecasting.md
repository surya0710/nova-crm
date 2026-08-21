# CRM User Guide - Sales Forecasting

## Purpose
Read pipeline value, weighted forecast, win/loss, and target achievement from the CRM dashboard and pipeline.

## Who should use this feature
Sales managers and reps with `opportunities.view`.

## Prerequisites
- Opportunities with amount, probability, and expected close date
- Optional organization sales target (`sales_targets`) for the month or year

## Metrics
- Pipeline value (open deals)
- Weighted pipeline (amount × probability)
- Expected revenue (same as weighted open pipeline)
- Won / lost value and win rate
- Average deal size and average sales cycle (won deals)
- Revenue by salesperson and by stage
- Monthly forecast from expected close date vs won amount
- Target vs achievement when a target exists

## Step-by-step instructions
1. Open **CRM → Pipeline** for open value, weighted pipeline, and win/loss counts.
2. Open the dashboard **Sales forecast** and **Pipeline** widgets.
3. Call `GET /api/v1/sales/forecast` for the same payload (optional `year`, `month`).
4. Create an organization target in `sales_targets` (year + optional month, amount, currency) when you need achievement %.

## Expected result
Forecast numbers match opportunity `amount` (not a separate forecast engine). Project/portfolio forecast widgets stay on delivery budgets.

## Best Practices
Keep probability and expected close date current. Closed deals are excluded from weighted pipeline.

## Common Mistakes
Treating collected payment revenue as pipeline forecast. Those remain commercial revenue widgets.

## FAQ
There is no separate quota product. Sales targets are a small CRM table reused by the forecast widget and API.
