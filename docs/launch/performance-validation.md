# Deliverable 11 — Performance Validation

**Environment note:** Benchmarks below are **local XAMPP** reference points for regression comparison only. Production SLOs must be measured on staging/production hardware with realistic concurrency.

## Method

1. Warm caches (`config:cache`, `route:cache`, `view:cache`) for “cached” column.
2. Use browser DevTools Network or Laravel Debugbar if enabled locally.
3. Record p50 feel for interactive pages; use `php artisan` timings for CLI jobs.

## Benchmark template

| Scenario | Target (local guidance) | Cold (ms) | Cached (ms) | Notes |
|----------|-------------------------|-----------|-------------|-------|
| Login POST + redirect | < 1500 | | | |
| Dashboard / home | < 2000 | | | |
| Lead index (CRM orgs) | < 1500 | | | |
| Global search | < 1000 | | | |
| Simple report / analytics entry | < 3000 | | | |
| Large list (seeded + imported) | < 3000 | | | |
| Queue job (`queue:work --once`) | completes | | | |
| `organization:upgrade --all` | completes < 60s local | | | |

## Large dataset notes

- Presentation demo (`demo:seed-presentation`) provides denser CRM/HR/project data for stress browsing.
- Pilot seeder is intentionally modest (operational realism, not load test).
- For GA load testing, use dedicated staging with synthetic traffic — out of scope for XAMPP evidence.

## Result summary

| Status | Detail |
|--------|--------|
| Local harness ready | Templates + pilot data available |
| Production SLO sign-off | **Pending** staging run |
