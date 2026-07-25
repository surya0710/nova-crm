# Import Center API

Base path: `/api/v1/imports`

Authentication: Sanctum + organization context middleware. RBAC enforced.

## Endpoints

| Method | Path | Permission | Description |
|--------|------|------------|-------------|
| GET | `/catalog` | `imports.view` | Grouped importable entities |
| GET | `/history` | `imports.view` | Paginated import sessions |
| POST | `/{entity}/upload` | `imports.create` + module scope | Upload file (`multipart/form-data`) |
| POST | `/sessions/{id}/validate` | `imports.create` | Re-validate |
| GET | `/sessions/{id}/preview` | `imports.view` | Preview payload |
| POST | `/sessions/{id}/map` | `imports.create` | Apply column mapping |
| POST | `/sessions/{id}/execute` | `imports.create` | Start import (`confirm=1`) |
| GET | `/sessions/{id}` | `imports.view` | Status / counts |
| GET | `/sessions/{id}/errors` | `imports.view` | Download error CSV |

### Upload body

- `file` (required) — csv/xlsx
- `duplicate_strategy` — `skip` \| `update` \| `create`
- `validate` — boolean, default true

### Execute body

- `confirm` — must be accepted
- `duplicate_strategy` — optional override

### Entity keys

`lead`, `customer`, `opportunity`, `employee`, `department`, `designation`, `branch`, `shift`, `leave_type`, `holiday`, `project`, `milestone`, `task`, `user`
