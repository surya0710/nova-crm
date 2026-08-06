# Import Guides

## Recommended import order (new organization)

1. Administration — Users (optional invites)
2. HRMS masters — Branches, Departments, Designations, Shifts, Leave Types, Holidays
3. HRMS — Employees (optionally with Create Login)
4. CRM — Customers, then Leads / Opportunities
5. Projects — Projects, then Milestones, then Tasks

## Duplicate handling

Choose a strategy before confirming import:

- **Skip** — leave existing records unchanged; report duplicates as validation issues when strategy is skip
- **Update** — update matched existing records
- **Create** — always insert new rows (may create duplicates)

## Employee + Identity

When `Create Login` is Yes:

1. Employee record is created
2. User account is provisioned via Identity Platform
3. Invitation email is sent when `Send Invitation` is Yes
4. Portal / ESS access follows `Portal Access`

Admins never assign passwords during import.

## Validation rules (common)

- Required fields must be present
- Email / phone formats
- Dates parseable
- Enumerations must match configured keys or labels
- Foreign keys (department/branch/designation/project codes) must resolve in the organization

Invalid rows are not persisted. Download the error report, fix only failed rows, and re-import.

## Performance

- Imports above `IMPORT_QUEUE_THRESHOLD_ROWS` run on the queue
- Chunk size: `IMPORT_CHUNK_SIZE` (default 100)
- Ensure a queue worker is running for large migrations
