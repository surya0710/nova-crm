<?php

namespace App\Services;

use App\Events\CustomerCreated;
use App\Events\CustomerUpdated;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Write authority for Customer create operations used by import and future callers.
 */
class CustomerService
{
    public const SORTABLE = [
        'name',
        'company',
        'created_at',
        'last_activity_at',
        'customer_value',
    ];

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
            foreach (['customers.name', 'customers.company', 'customers.email', 'customers.state', 'customers.country', 'customers.gstin', 'customers.pan'] as $column) {
                $searchQuery->orWhereRaw("LOWER({$column}) LIKE ?", [$textLike]);
            }

            $searchQuery->orWhereHas('contacts', function (Builder $contacts) use ($textLike) {
                $contacts->whereRaw('LOWER(contacts.name) LIKE ?', [$textLike])
                    ->orWhereRaw('LOWER(contacts.email) LIKE ?', [$textLike])
                    ->orWhereRaw('LOWER(contacts.phone) LIKE ?', [$textLike]);
            });

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
     * @param  Builder<Customer>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Customer>
     */
    public function applyIndexFilters(Builder $query, array $filters): Builder
    {
        $this->searchQuery($query, $filters['search'] ?? null);
        $this->geographicFilterQuery(
            $query,
            $filters['state'] ?? null,
            $filters['country'] ?? null,
        );

        if ($status = ($filters['status'] ?? '')) {
            $query->where('customers.status', $status);
        }

        if ($industry = trim((string) ($filters['industry'] ?? ''))) {
            $query->where('customers.industry', 'like', "%{$industry}%");
        }

        if ($assignedTo = (int) ($filters['assigned_to'] ?? 0)) {
            $query->where('customers.assigned_to', $assignedTo);
        }

        if ($type = ($filters['type'] ?? '')) {
            $query->where('customers.type', $type);
        }

        if ($lifecycle = ($filters['lifecycle_stage'] ?? '')) {
            $query->where('customers.lifecycle_stage', $lifecycle);
        }

        if ($segment = ($filters['segment'] ?? '')) {
            $query->where('customers.segment', $segment);
        }

        if ($source = ($filters['source'] ?? '')) {
            $query->where('customers.source', $source);
        }

        $this->tagFilterQuery($query, $filters['tags'] ?? null);
        $this->dateRangeFilterQuery($query, 'customers.created_at', $filters['created_from'] ?? null, $filters['created_to'] ?? null);
        $this->dateRangeFilterQuery($query, 'customers.last_activity_at', $filters['last_activity_from'] ?? null, $filters['last_activity_to'] ?? null);
        $this->valueFilterQuery($query, $filters['value_min'] ?? null, $filters['value_max'] ?? null);

        return $query;
    }

    /**
     * @param  Builder<Customer>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Customer>
     */
    public function applyIndexSort(Builder $query, array $filters, bool $metadataSorted = false): Builder
    {
        if ($metadataSorted) {
            return $query;
        }

        $sort = $this->normalizeSortKey($filters['sort'] ?? null);
        $direction = strtolower((string) ($filters['sort_direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort === 'customer_value') {
            $this->withCustomerValue($query);
            $query->orderBy('customer_value', $direction);

            return $query;
        }

        if ($sort === 'name') {
            return $query->orderByRaw("COALESCE(NULLIF(customers.company, ''), customers.name) {$direction}");
        }

        if ($sort) {
            return $query->orderBy('customers.'.$sort, $direction);
        }

        return $query->latest('customers.created_at');
    }

    public function touchLastActivity(?Customer $customer, mixed $at = null): void
    {
        if (! $customer) {
            return;
        }

        $timestamp = $at ? Carbon::parse($at) : now();

        if ($customer->last_activity_at && $timestamp->lte($customer->last_activity_at)) {
            return;
        }

        $customer->forceFill(['last_activity_at' => $timestamp])->saveQuietly();
    }

    /**
     * @param  Builder<Customer>  $query
     */
    protected function tagFilterQuery(Builder $query, mixed $tags): Builder
    {
        $tags = $this->normalizeFilterValues(is_string($tags) ? preg_split('/\s*,\s*/', $tags) : $tags);

        if ($tags === []) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($tags) {
            foreach ($tags as $tag) {
                $inner->orWhereJsonContains('customers.tags', $tag);
            }
        });
    }

    /**
     * @param  Builder<Customer>  $query
     */
    protected function dateRangeFilterQuery(Builder $query, string $column, mixed $from, mixed $to): Builder
    {
        if (filled($from)) {
            $query->whereDate($column, '>=', $from);
        }

        if (filled($to)) {
            $query->whereDate($column, '<=', $to);
        }

        return $query;
    }

    /**
     * @param  Builder<Customer>  $query
     */
    protected function valueFilterQuery(Builder $query, mixed $min, mixed $max): Builder
    {
        $min = $this->nullableNumber($min);
        $max = $this->nullableNumber($max);

        if ($min === null && $max === null) {
            return $query;
        }

        $sql = $this->customerValueSql();

        if ($min !== null) {
            $query->whereRaw("{$sql} >= ?", [$min]);
        }

        if ($max !== null) {
            $query->whereRaw("{$sql} <= ?", [$max]);
        }

        return $query;
    }

    protected function customerValueSql(): string
    {
        return '(select coalesce(sum(invoices.total), 0) from invoices where invoices.customer_id = customers.id and invoices.status != \'cancelled\')';
    }

    /**
     * @param  Builder<Customer>  $query
     */
    protected function withCustomerValue(Builder $query): Builder
    {
        if (collect($query->getQuery()->columns ?? [])->contains(fn ($column) => is_string($column) && str_contains($column, 'customer_value'))) {
            return $query;
        }

        if ($query->getQuery()->columns === null) {
            $query->select('customers.*');
        }

        return $query->selectSub(
            Invoice::query()
                ->selectRaw('COALESCE(SUM(invoices.total), 0)')
                ->whereColumn('invoices.customer_id', 'customers.id')
                ->where('invoices.status', '!=', 'cancelled'),
            'customer_value',
        );
    }

    protected function normalizeSortKey(mixed $sort): ?string
    {
        $sort = trim((string) $sort);

        return in_array($sort, self::SORTABLE, true) ? $sort : null;
    }

    protected function nullableNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
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
        if (empty($data['type'])) {
            $data['type'] = ! empty($data['company']) ? 'company' : 'individual';
        }
        if (empty($data['lifecycle_stage'])) {
            $data['lifecycle_stage'] = ($data['status'] ?? 'active') === 'prospect' ? 'lead' : 'customer';
        }

        $customer = Customer::query()->create([
            ...$data,
            'created_by' => $user->id,
            'last_activity_at' => $data['last_activity_at'] ?? now(),
        ]);

        $this->metadataForms->persistValidatedValues($customer, $metadataValues);
        app(ContactService::class)->seedPrimaryFromCustomer($customer, $user);
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
            $this->touchLastActivity($customer);
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
