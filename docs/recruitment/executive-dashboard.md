# Executive Recruitment Dashboard

## Purpose
Give HR leaders and executives a single view of hiring health using live KPIs calculated from Phases 11.1–11.4 data.

## Widgets
- Open Positions
- Active Candidates
- Interviews Scheduled
- Offers Pending
- Offers Accepted
- Hiring Rate
- Time to Hire
- Time to Fill
- Offer Acceptance Rate
- Applications This Period
- New Candidates
- Active Recruiters

## Hiring Manager Snapshot
- Open requisitions
- Pending approvals
- Interview completion rate
- Hiring decisions
- Average approval / offer approval time

## Time Analytics
Calculated from timestamps:
- Application `applied_date` → hire `decision_date` (time to hire)
- Opening `publish_date` → `closing_date` (time to fill)
- Interview `duration_minutes`
- Offer approval `created_at` → `approved_at`
- Offer `sent_at` → `accepted_at`
- Average hiring cycle (publish → decision)

## Filters
Today, This Week, This Month, Quarter, Year, and Custom Date Range.

## Access
- Dashboard hub: `recruitment.view` (KPI tiles require `recruitment.analytics.view`)
- Executive Summary page: `recruitment.analytics.view`
- Route: `hrms.recruitment.executive`

## Performance
Aggregations use indexed columns, cached KPI payloads, and avoid N+1 query patterns. Dashboard target remains interactive for large recruitment datasets.
