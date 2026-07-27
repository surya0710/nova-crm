# Bulk Operations — Lookup Integration

Release **1.1.S.1** replaces numeric ID inputs with Entity Picker fields.

## Field types

| Type | Lookup entity | Example action |
|------|---------------|----------------|
| `user` | `users` | `lead.assign_owner` |
| `employee` | `employees` | Future HRMS actions |
| `department` | `departments` | `employee.assign_department` |
| `designation` | `designations` | `employee.assign_designation` |
| `branch` | `branches` | `employee.assign_branch` |
| `shift` | `shifts` | Future shift assignment |
| `lookup` | Custom (`lookup` key) | Extensibility |

## Defining lookup fields in actions

Use the `DefinesLookupField` trait:

```php
use App\Services\Bulk\Concerns\DefinesLookupField;

class MyBulkAction implements BulkActionProviderInterface
{
    use DefinesLookupField;

    public function inputFields(): array
    {
        return [
            $this->userField('owner_id', 'Assign Owner'),
            $this->departmentField(),
        ];
    }
}
```

## CRM — Assign Owner

- Field type: `user`
- Execution: `AssignmentService::assignOwner()` (preserves assignment history and audit logs)
- Permission: `leads.update`

## HRMS — Org unit assignment

Department, designation, and branch bulk actions use typed lookup fields. Execution validates the selected entity belongs to the organization.

## Best practices

1. Never use `type: 'integer'` for relationship fields
2. Reuse lookup types from `config/lookups.php`
3. Validate selected IDs in `executeOne()` (org scope)
4. Use `AssignmentService` for owner assignment to preserve audit trail

## Validation checklist

- ✓ No numeric ID fields in bulk action forms
- ✓ Entity Picker renders for all lookup field types
- ✓ Organization isolation on lookup APIs
- ✓ RBAC enforced per entity
- ✓ Assignment audit logs preserved for owner assignment
