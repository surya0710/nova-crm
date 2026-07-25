# Frequently Asked Questions (Launch Pack)

## General
**Q: Where is documentation?**  
A: In-app Knowledge Center (`/knowledge`) and the `docs/` repository tree.

**Q: How do I invite users?**  
A: Administration → Users (or equivalent) → Invite; assign the least-privilege role.

## CRM
**Q: Can I customize pipeline stages?**  
A: Pipeline configuration is org-scoped; see CRM admin/configuration docs.

## Projects
**Q: Difference between portfolio, program, and project?**  
A: See [program-management.md](../projects/program-management.md).

## HRMS
**Q: Who approves leave?**  
A: Reporting manager / configured approver workflow — see leave user guide.

## Technical
**Q: We see a blank page after deploy.**  
A: Confirm `npm run build`, `APP_URL`, and `storage:link`; see [troubleshooting](../troubleshooting/overview.md).

**Q: Can we run migrate:fresh?**  
A: **No** on shared or production databases. Forward-only `php artisan migrate`.

More: [faq/overview.md](../faq/overview.md)