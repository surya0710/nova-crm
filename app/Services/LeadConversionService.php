<?php

namespace App\Services;

use App\Events\CustomerCreated;
use App\Events\LeadConverted;
use App\Events\OpportunityCreated;
use App\Exceptions\DuplicateCustomerException;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadConversionService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected TenantContext $tenantContext,
        protected MetadataEntityFormService $metadataForms,
        protected MarketingAttributionService $attribution,
        protected MarketingConversionService $conversions,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     email?: ?string,
     *     phone?: ?string,
     *     create_opportunity?: bool,
     *     existing_customer_id?: int,
     *     force_create?: bool,
     * }  $data
     * @return array{lead: Lead, customer: Customer, opportunity: ?Opportunity, reused_customer: bool}
     */
    public function convert(Lead $lead, array $data, User $user): array
    {
        $createOpportunity = $data['create_opportunity'] ?? true;

        $this->validatePermissions($lead, $user, $createOpportunity);
        $this->validateBusinessRules($lead);

        return DB::transaction(function () use ($lead, $data, $user, $createOpportunity) {
            [$customer, $reusedCustomer] = $this->resolveCustomer($lead, $data, $user);

            $opportunity = $createOpportunity
                ? $this->createOpportunity($lead, $customer, $user)
                : null;

            $lead = $this->markLeadConverted($lead, $user);

            $this->attribution->propagateToConversion($lead, $customer, $opportunity);

            $this->conversions->recordLeadConverted($lead, $customer, $opportunity);

            if (! $reusedCustomer) {
                $this->conversions->recordCustomerCreated($lead, $customer);
            }

            if ($opportunity) {
                $this->conversions->recordOpportunityCreated($lead, $customer, $opportunity);
            }

            $this->auditLogger->log($lead, 'converted', [
                'customer_id' => $customer->id,
                'opportunity_id' => $opportunity?->id,
                'reused_customer' => $reusedCustomer,
            ], $user);

            $this->notifyAssignee($lead, $customer, $user);

            event(LeadConverted::forModel($lead, [
                'actor_id' => $user->id,
                'customer_id' => $customer->id,
                'opportunity_id' => $opportunity?->id,
            ]));
            if (! $reusedCustomer) {
                event(CustomerCreated::forModel($customer, ['actor_id' => $user->id, 'lead_id' => $lead->id]));
            }
            if ($opportunity) {
                event(OpportunityCreated::forModel($opportunity, ['actor_id' => $user->id, 'lead_id' => $lead->id]));
            }

            return [
                'lead' => $lead->fresh(['assignee', 'customer', 'convertedBy']),
                'customer' => $customer,
                'opportunity' => $opportunity,
                'reused_customer' => $reusedCustomer,
            ];
        });
    }

    /**
     * Find existing customers in the organization by exact email or phone match.
     *
     * @param  array{email?: ?string, phone?: ?string}  $data
     * @return Collection<int, Customer>
     */
    public function findDuplicateCustomers(Lead $lead, array $data): Collection
    {
        $email = trim($data['email'] ?? $lead->email ?? '');
        $phone = trim($data['phone'] ?? $lead->phone ?? '');

        if ($email === '' && $phone === '') {
            return collect();
        }

        return Customer::query()
            ->where('organization_id', $lead->organization_id)
            ->where(function ($query) use ($email, $phone) {
                if ($email !== '') {
                    $query->where('email', $email);
                }

                if ($phone !== '') {
                    if ($email !== '') {
                        $query->orWhere('phone', $phone);
                    } else {
                        $query->where('phone', $phone);
                    }
                }
            })
            ->get();
    }

    protected function validatePermissions(Lead $lead, User $user, bool $createOpportunity): void
    {
        if (! $user->can('update', $lead)) {
            throw new AuthorizationException(__('You do not have permission to convert this lead.'));
        }

        if (! $user->can('create', Customer::class)) {
            throw new AuthorizationException(__('You do not have permission to create customers.'));
        }

        if ($createOpportunity && ! $user->can('create', Opportunity::class)) {
            throw new AuthorizationException(__('You do not have permission to create opportunities.'));
        }
    }

    protected function validateBusinessRules(Lead $lead): void
    {
        if ($lead->isConverted()) {
            throw ValidationException::withMessages([
                'lead' => __('This lead has already been converted.'),
            ]);
        }

        if ($lead->customer()->exists()) {
            throw ValidationException::withMessages([
                'lead' => __('This lead is already linked to a customer.'),
            ]);
        }

        if ($lead->status !== 'qualified') {
            throw ValidationException::withMessages([
                'lead' => __('Only qualified leads can be converted.'),
            ]);
        }
    }

    /**
     * @param  array{
     *     name: string,
     *     email?: ?string,
     *     phone?: ?string,
     *     existing_customer_id?: int,
     *     force_create?: bool,
     * }  $data
     * @return array{0: Customer, 1: bool}
     */
    protected function resolveCustomer(Lead $lead, array $data, User $user): array
    {
        $duplicates = $this->findDuplicateCustomers($lead, $data);

        if ($duplicates->isNotEmpty()) {
            if (! empty($data['existing_customer_id'])) {
                return [$this->useExistingCustomer($lead, $duplicates, (int) $data['existing_customer_id'], $user), true];
            }

            if (empty($data['force_create'])) {
                throw new DuplicateCustomerException($duplicates);
            }
        }

        $customer = Customer::query()->create([
            'organization_id' => $lead->organization_id,
            'name' => $data['name'],
            'company' => $lead->company,
            'email' => $data['email'] ?? $lead->email,
            'phone' => $data['phone'] ?? $lead->phone,
            'industry' => $lead->industry,
            'status' => 'prospect',
            'type' => $lead->company ? 'company' : 'individual',
            'lifecycle_stage' => 'opportunity',
            'source' => array_key_exists((string) $lead->source, config('customers.sources', []))
                ? $lead->source
                : 'other',
            'address_line_1' => $lead->address_line_1,
            'city' => $lead->city,
            'state' => $lead->state,
            'country' => $lead->country,
            'postal_code' => $lead->postal_code,
            'assigned_to' => $lead->assigned_to,
            'lead_id' => $lead->id,
            'tags' => $lead->tags,
            'created_by' => $user->id,
        ]);

        $this->copyMetadataValues($lead, $customer);
        app(ContactService::class)->seedPrimaryFromCustomer($customer, $user);

        return [$customer, false];
    }

    /**
     * @param  Collection<int, Customer>  $duplicates
     */
    protected function useExistingCustomer(Lead $lead, Collection $duplicates, int $customerId, User $user): Customer
    {
        $customer = $duplicates->firstWhere('id', $customerId);

        if (! $customer) {
            throw ValidationException::withMessages([
                'existing_customer_id' => __('The selected customer does not match the duplicate email or phone.'),
            ]);
        }

        if (! $user->can('update', $customer)) {
            throw new AuthorizationException(__('You do not have permission to link this customer.'));
        }

        if ($customer->lead_id !== null && $customer->lead_id !== $lead->id) {
            throw ValidationException::withMessages([
                'existing_customer_id' => __('This customer is already linked to another lead.'),
            ]);
        }

        if ($customer->lead_id === null) {
            $customer->update(['lead_id' => $lead->id]);
        }

        $this->copyMetadataValues($lead, $customer);

        return $customer->fresh();
    }

    protected function createOpportunity(Lead $lead, Customer $customer, User $user): Opportunity
    {
        $organization = $this->tenantContext->get();

        $opportunity = Opportunity::query()->create([
            'organization_id' => $lead->organization_id,
            'title' => $lead->company ?: $lead->name,
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'stage' => 'qualification',
            'amount' => $lead->budget,
            'currency' => $organization?->currency ?? 'USD',
            'assigned_to' => $lead->assigned_to,
            'created_by' => $user->id,
        ]);

        $this->copyMetadataValues($lead, $opportunity);

        return $opportunity;
    }

    protected function copyMetadataValues(Lead $lead, Customer|Opportunity $target): void
    {
        $values = $lead->custom_fields ?? [];

        if ($values === []) {
            return;
        }

        $this->metadataForms->persistValues($target, $values, enforceRequired: false);
    }

    protected function markLeadConverted(Lead $lead, User $user): Lead
    {
        $lead->update([
            'status' => 'converted',
            'converted_at' => now(),
            'converted_by' => $user->id,
        ]);

        return $lead->fresh();
    }

    protected function notifyAssignee(Lead $lead, Customer $customer, User $user): void
    {
        $assignee = $lead->assignee;

        if (! $assignee || $assignee->id === $user->id) {
            return;
        }

        $assignee->notify(new CrmNotification(
            title: __('Lead converted'),
            message: __(':lead was converted to customer :customer.', [
                'lead' => $lead->name,
                'customer' => $customer->display_name,
            ]),
            actionUrl: route('customers.show', $customer),
            organizationId: $lead->organization_id,
        ));
    }
}
