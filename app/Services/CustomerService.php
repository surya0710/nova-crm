<?php

namespace App\Services;

use App\Events\CustomerCreated;
use App\Events\CustomerUpdated;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Write authority for Customer create operations used by import and future callers.
 */
class CustomerService
{
    public function __construct(protected MetadataEntityFormService $metadataForms) {}

    /**
     * @param  Builder<Customer>  $query
     * @return Builder<Customer>
     */
    public function searchQuery(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);
        if ($search === '') {
            return $query;
        }

        $textLike = '%'.strtolower($search).'%';
        $phoneDigits = preg_match('/^[\d\s+().\/-]+$/', $search)
            ? (preg_replace('/\D+/', '', $search) ?? '')
            : '';

        return $query->where(function (Builder $searchQuery) use ($textLike, $phoneDigits) {
            foreach (['customers.name', 'customers.company', 'customers.email', 'customers.state', 'customers.country'] as $column) {
                $searchQuery->orWhereRaw("LOWER({$column}) LIKE ?", [$textLike]);
            }

            if ($phoneDigits !== '') {
                $phoneColumn = 'customers.phone';
                foreach ([' ', '+', '-', '(', ')', '.', '/'] as $character) {
                    $phoneColumn = "REPLACE({$phoneColumn}, '{$character}', '')";
                }

                $searchQuery->orWhereRaw("{$phoneColumn} LIKE ?", ['%'.$phoneDigits.'%']);
                if (strlen($phoneDigits) > 10) {
                    $searchQuery->orWhereRaw("{$phoneColumn} LIKE ?", ['%'.substr($phoneDigits, -10).'%']);
                }
            }
        });
    }

    /**
     * @param  Builder<Customer>  $query
     * @return Builder<Customer>
     */
    public function geographicFilterQuery(Builder $query, mixed $states, mixed $countries): Builder
    {
        $states = $this->normalizeFilterValues($states);
        $countries = $this->normalizeFilterValues($countries);

        if ($states !== []) {
            $query->whereIn('customers.state', $states);
        }

        if ($countries !== []) {
            $query->whereIn('customers.country', $countries);
        }

        return $query;
    }

    /**
     * @return array{states: array<int, string>, countries: array<int, string>}
     */
    public function geographicOptions(): array
    {
        return [
            'states' => Customer::query()
                ->whereNotNull('state')
                ->where('state', '!=', '')
                ->distinct()
                ->orderBy('state')
                ->pluck('state')
                ->all(),
            'countries' => Customer::query()
                ->whereNotNull('country')
                ->where('country', '!=', '')
                ->distinct()
                ->orderBy('country')
                ->pluck('country')
                ->all(),
        ];
    }

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

    /**
     * @return array<int, string>
     */
    protected function normalizeFilterValues(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];

        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $item): string => trim((string) $item), $values),
            static fn (string $item): bool => $item !== '',
        )));
    }
}
