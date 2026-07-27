# Export Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| Catalog empty | Missing permissions or license | Grant `exports.*` + module license |
| Stuck in `queued` | No queue worker | Start workers (SOP-DEP-004) |
| PDF validation error | Too many rows | Use CSV/Excel |
| Download 404 | Expired / revoked / missing file | Regenerate export |
| Wrong org data | Impossible by design | Confirm `X-Organization-Id` / session org |
| Memory pressure | Huge XLSX | Prefer CSV; raise chunk size carefully |

Audit trail: search Audit Logs for `export_*` events on the export session.
