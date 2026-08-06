# NovaCRM Enterprise Presentation

## Deliverables

| File | Description |
|------|-------------|
| `NovaCRM_Presentation.pptx` | Editable 23-slide 16:9 enterprise deck (project root + `presentation/`) |
| `NovaCRM_Presentation.pdf` | PDF export of the full deck |
| `assets/screenshots/` | Real application screenshots captured from local demo data |

## Demo login (for recapture)

- URL: `http://127.0.0.1:8010`
- Email: `demo@novacrm.test`
- Password: `password`
- Organization: Nova Enterprises

## Rebuild

```bash
php artisan serve --host=127.0.0.1 --port=8010
php artisan demo:seed-presentation   # idempotent demo data
node presentation/capture-screenshots.mjs
py -3 presentation/build_presentation.py
```

Then export PDF via PowerPoint (script attempts COM automatically when `pywin32` is installed).
