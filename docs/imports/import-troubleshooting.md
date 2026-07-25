# Import Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| Entity missing from Import Center | Module not licensed / not enabled, or missing module permission | Check plan modules and `imports.*` permissions |
| Template columns look wrong | Adapter field definitions changed | Re-download template; do not reuse old headers blindly |
| All rows invalid | Header mismatch | Use Apply mapping or rename columns to match template labels |
| Duplicate errors | Strategy is Skip and records already exist | Switch to Update, or remove duplicates from file |
| Import stuck on Ready after Start | Queued job not processed | Run `php artisan queue:work` and refresh status |
| Employee login not created | Create Login blank/No or email missing | Set Create Login=Yes and provide email |
| Cross-org data leak concerns | — | Sessions are organization-scoped; APIs enforce tenant context |
| 403 on API upload | Missing `imports.create` or module scope | Grant RBAC permissions |

## Error report columns

`row_number`, `column`, `field`, `error`, `original_value`

Use the report to correct and re-upload only failed rows.
