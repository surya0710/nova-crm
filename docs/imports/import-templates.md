# Import Templates

Every Import Center entity provides downloadable CSV and Excel templates that match the current field definitions.

## Contents

Templates include:

- Required columns
- Optional columns
- One sample data row
- Instruction sheet (Excel) with validation notes
- Lookup sheet for boolean/enum hints where applicable

## Download

- UI: **Administration → Import Center → [Entity] → Download Excel / CSV**
- Routes: `administration.imports.template` (`csv` | `xlsx`)

CRM lead/customer shortcuts remain available under their module import screens and share the same engine.

## Best practices

1. Keep the header row unchanged.
2. Delete or replace the sample row before production imports.
3. Prefer codes (department code, branch code, project code) over free-text names for relationships.
4. Use ISO dates (`YYYY-MM-DD`) when possible.
5. For employees requiring login, include email and set Create Login = Yes.
