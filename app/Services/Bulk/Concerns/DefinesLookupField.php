<?php

namespace App\Services\Bulk\Concerns;

trait DefinesLookupField
{
    /**
     * @return array{key: string, label: string, type: string, required?: bool, lookup?: string}
     */
    protected function lookupField(string $key, string $label, string $type, bool $required = true, ?string $lookupEntity = null): array
    {
        $field = [
            'key' => $key,
            'label' => $label,
            'type' => $type,
            'required' => $required,
        ];

        if ($type === 'lookup' && $lookupEntity) {
            $field['lookup'] = $lookupEntity;
        }

        return $field;
    }

    protected function userField(string $key, string $label, bool $required = true): array
    {
        return $this->lookupField($key, $label, 'user', $required);
    }

    protected function employeeField(string $key, string $label, bool $required = true): array
    {
        return $this->lookupField($key, $label, 'employee', $required);
    }

    protected function departmentField(string $key = 'department_id', string $label = 'Department'): array
    {
        return $this->lookupField($key, $label, 'department');
    }

    protected function designationField(string $key = 'designation_id', string $label = 'Designation'): array
    {
        return $this->lookupField($key, $label, 'designation');
    }

    protected function branchField(string $key = 'branch_id', string $label = 'Branch'): array
    {
        return $this->lookupField($key, $label, 'branch');
    }

    protected function shiftField(string $key = 'shift_id', string $label = 'Shift'): array
    {
        return $this->lookupField($key, $label, 'shift');
    }
}
