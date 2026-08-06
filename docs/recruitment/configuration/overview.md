# Recruitment Configuration

## Purpose
List configuration keys and environment variables for recruitment.

## Config Catalogs (`config/hrms.php` → `recruitment`)
- `requisition_statuses`
- `opening_statuses`
- `application_stages` / `application_statuses`
- `candidate_sources`
- `document_categories`
- `documents.disk`, `documents.max_size_kb`, `documents.allowed_mimes`
- `analytics.cache_ttl`, `analytics.periods`, `analytics.leaderboard_periods`, `analytics.export_formats`, `analytics.funnel_stages`
- `report_types`

## Environment Variables
- `HRMS_DOCUMENT_DISK` — storage disk for candidate documents (default: `local`)
- `HRMS_DOCUMENT_MAX_SIZE_KB` — max upload size
- `RECRUITMENT_ANALYTICS_CACHE_TTL` — analytics cache TTL in seconds (default: `120`)
- `CANDIDATE_RESUME_MAX_KB` — portal resume max size

## RBAC
Permissions defined in `config/rbac.php` under the `recruitment` module, including analytics and reporting permissions.

## Workflow Triggers
Documented in `config/hrms.php` `workflow_triggers` with `recruitment.*` keys.
