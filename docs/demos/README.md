# Demo Environment & Scripts

| Document | Purpose |
|----------|---------|
| [organization.md](organization.md) | Demo org profile (Nova Enterprises) |
| [data-guide.md](data-guide.md) | Accounts, workflows, reset |
| [master-demo-script.md](master-demo-script.md) | Standard 45–60 min demo |
| [industry-scenarios.md](industry-scenarios.md) | Vertical demo tracks |

## Seed command

```bash
php artisan demo:seed-presentation
# or
php artisan db:seed --class=PresentationDemoSeeder
```

Login printed by seeder: `demo@novacrm.test` / `password` (local/demo only).