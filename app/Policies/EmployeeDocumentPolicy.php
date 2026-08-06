<?php

namespace App\Policies;

use App\Models\EmployeeDocument;
use App\Models\User;

class EmployeeDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('hrms.view')
            || $user->hasPermission('hrms.documents.manage')
            || $user->hasPermission('ess.access');
    }

    public function view(User $user, EmployeeDocument $employeeDocument): bool
    {
        if ($user->hasPermission('ess.access', $employeeDocument->organization)
            && (int) $employeeDocument->employee?->user_id === (int) $user->id) {
            return true;
        }

        return $user->hasPermission('hrms.view', $employeeDocument->organization)
            || $user->hasPermission('hrms.documents.manage', $employeeDocument->organization);
    }

    public function manage(User $user, ?EmployeeDocument $employeeDocument = null): bool
    {
        $organization = $employeeDocument?->organization;

        return $user->hasPermission('hrms.documents.manage', $organization);
    }
}
