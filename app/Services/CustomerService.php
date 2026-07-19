<?php

namespace App\Services;

use App\Events\CustomerCreated;
use App\Events\CustomerUpdated;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;

/**
 * Write authority for Customer create operations used by import and future callers.
 */
class CustomerService
{
    public function __construct(protected MetadataEntityFormService $metadataForms) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user, array $metadataValues = []): Customer
    {
        $customer = Customer::query()->create([
            ...$data,
            'created_by' => $user->id,
        ]);

        $this->metadataForms->persistValidatedValues($customer, $metadataValues);
        $customer = $customer->fresh();
        event(CustomerCreated::forModel($customer, ['actor_id' => $user->id]));

        return $customer;
    }

    public function update(Customer $customer, array $data, User $user, array $metadataValues = []): Customer
    {
        $customer->update($data);
        $changes = array_values(array_filter(
            array_keys($data),
            fn (string $attribute) => $customer->wasChanged($attribute),
        ));
        $metadataResult = $this->metadataForms->persistValidatedValues($customer, $metadataValues);
        if ($metadataResult['changed'] ?? false) {
            $changes[] = 'custom_fields';
        }
        $customer = $customer->fresh();
        if ($changes !== []) {
            $runtime = app(WorkflowRuntimeContext::class);
            event(CustomerUpdated::forModel(
                $customer,
                ['actor_id' => $user->id, 'changes' => $changes],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));
        }

        return $customer;
    }

    public function findDuplicate(Organization $organization, ?string $email, ?string $phone): ?Customer
    {
        $email = trim((string) ($email ?? ''));
        $phone = $this->normalizePhone($phone) ?? '';

        if ($email === '' && $phone === '') {
            return null;
        }

        return Customer::query()
            ->where('organization_id', $organization->id)
            ->where(function ($query) use ($email, $phone) {
                if ($email !== '') {
                    $query->orWhere('email', $email);
                }

                if ($phone !== '') {
                    $query->orWhere('phone', $phone);
                }
            })
            ->first();
    }

    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $phone = trim($phone);
        $hasPlus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return null;
        }

        return ($hasPlus ? '+' : '').$digits;
    }
}
