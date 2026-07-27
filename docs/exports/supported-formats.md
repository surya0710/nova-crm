# Supported Export Formats

| Key | Extension | MIME | Notes |
|-----|-----------|------|-------|
| `xlsx` | `.xlsx` | Office Open XML | Default for interactive use |
| `csv` | `.csv` | `text/csv; charset=UTF-8` | UTF-8 BOM, streamed writes |
| `pdf` | `.pdf` | `application/pdf` | Branded DomPDF; limited rows |

## Future-ready

The writer interface supports adding JSON, XML, and ZIP without changing entity adapters. Register a new writer in `ExportPlatformService::makeWriter()` and add metadata under `config/export.php` → `formats`.
