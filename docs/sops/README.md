# Standard Operating Procedures (SOPs)

Version-controlled operational procedures for selling, deploying, supporting, and succeeding with NovaCRM customers.

## SOP index

| Family | Document |
|--------|----------|
| Sales Operations | [sales-operations.md](sales-operations.md) |
| Customer Onboarding | [customer-onboarding.md](customer-onboarding.md) |
| Technical Operations | [technical-operations.md](technical-operations.md) |
| Support | [support.md](support.md) |
| Customer Success | [customer-success.md](customer-success.md) |
| Internal Operations | [internal-operations.md](internal-operations.md) |

## Conventions

1. Every SOP includes **Purpose**, **Roles**, **Prerequisites**, **Procedure**, **Exit criteria**, and **Document control**.
2. Checklists use `- [ ]` so they can be copied into tickets or runbooks.
3. Link to product screens by route name where helpful (e.g. `platform.organizations.*`).
4. Never rely on undocumented tribal knowledge — if a step requires a secret or credential, name the vault / owner field.

## Change process

1. Propose change via PR to `docs/sops/`.
2. Owner reviews within 5 business days.
3. Bump **Version** and **Last reviewed** in the document control block.
4. Announce material changes in release communication if customer-facing SLAs change.
