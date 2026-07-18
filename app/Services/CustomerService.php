<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;

/**
 * Write authority for Customer create operations used by import and future callers.
 */
class CustomerService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): Customer
    {
        return Customer::query()->create([
            ...$data,
            'created_by' => $user->id,
        ])->fresh();
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
