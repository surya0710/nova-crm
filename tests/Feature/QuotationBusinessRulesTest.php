<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\User;
use App\Services\QuotationCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function sampleItems(array $overrides = []): array
    {
        return [array_merge([
            'description' => 'Consulting hours',
            'quantity' => 10,
            'unit_price' => 100,
            'tax_rate' => 10,
            'discount_percent' => 0,
        ], $overrides)];
    }

    protected function createQuotationWithItems(
        Organization $organization,
        User $user,
        Customer $customer,
        string $status = 'draft',
        array $itemOverrides = [],
        array $quotationOverrides = [],
    ): Quotation {
        $quotation = Quotation::factory()->create(array_merge([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => $status,
            'subtotal' => 1000,
            'discount_amount' => 0,
            'tax_total' => 100,
            'total' => 1100,
            'created_by' => $user->id,
        ], $quotationOverrides));

        $quotation->items()->create([
            'description' => $itemOverrides['description'] ?? 'Consulting hours',
            'quantity' => $itemOverrides['quantity'] ?? 10,
            'unit_price' => $itemOverrides['unit_price'] ?? 100,
            'tax_rate' => $itemOverrides['tax_rate'] ?? 10,
            'discount_percent' => $itemOverrides['discount_percent'] ?? 0,
            'line_total' => $itemOverrides['line_total'] ?? 1100,
            'sort_order' => 0,
        ]);

        return $quotation->fresh(['items']);
    }

    public function test_draft_quotation_is_editable(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createQuotationWithItems($organization, $user, $customer, 'draft');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('quotations.update', $quotation), [
                'customer_id' => $customer->id,
                'title' => 'Updated title',
                'issue_date' => now()->toDateString(),
                'currency' => 'USD',
                'items' => $this->sampleItems(['description' => 'Updated line']),
            ]);

        $response->assertRedirect(route('quotations.show', $quotation));
        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'title' => 'Updated title',
        ]);
    }

    public function test_sent_quotation_is_editable(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createQuotationWithItems($organization, $user, $customer, 'sent');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('quotations.update', $quotation), [
                'customer_id' => $customer->id,
                'issue_date' => now()->toDateString(),
                'currency' => 'USD',
                'items' => $this->sampleItems(['description' => 'Sent quote update']),
            ]);

        $response->assertRedirect(route('quotations.show', $quotation));
    }

    public function test_accepted_quotation_is_not_editable(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createQuotationWithItems($organization, $user, $customer, 'accepted');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('quotations.update', $quotation), [
                'customer_id' => $customer->id,
                'issue_date' => now()->toDateString(),
                'currency' => 'USD',
                'items' => $this->sampleItems(),
            ]);

        $response->assertForbidden();
    }

    public function test_converted_quotation_is_not_editable(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createQuotationWithItems($organization, $user, $customer, 'converted');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('quotations.edit', $quotation));

        $response->assertForbidden();
    }

    public function test_accepted_quotation_cannot_be_deleted(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createQuotationWithItems($organization, $user, $customer, 'accepted');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('quotations.destroy', $quotation));

        $response->assertForbidden();
    }

    public function test_invalid_status_transition_is_blocked(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createQuotationWithItems($organization, $user, $customer, 'rejected');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('quotations.status.update', $quotation), [
                'status' => 'accepted',
            ]);

        $response->assertRedirect(route('quotations.show', $quotation));
        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'rejected',
        ]);
    }

    public function test_valid_status_transition_draft_to_sent(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createQuotationWithItems($organization, $user, $customer, 'draft');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('quotations.status.update', $quotation), [
                'status' => 'sent',
            ]);

        $response->assertRedirect(route('quotations.show', $quotation));
        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'sent',
        ]);
    }

    public function test_financial_totals_are_calculated_correctly(): void
    {
        $service = app(QuotationCalculationService::class);

        $totals = $service->calculateTotals([
            [
                'description' => 'Widget',
                'quantity' => 2,
                'unit_price' => 100,
                'tax_rate' => 10,
                'discount_percent' => 10,
            ],
        ]);

        $this->assertSame(200.0, $totals['subtotal']);
        $this->assertSame(20.0, $totals['discount_amount']);
        $this->assertSame(18.0, $totals['tax_total']);
        $this->assertSame(198.0, $totals['total']);
        $this->assertSame(198.0, $totals['items'][0]['line_total']);
    }

    public function test_cannot_accept_quotation_without_line_items(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
            'total' => 1100,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('quotations.status.update', $quotation), [
                'status' => 'accepted',
            ]);

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'sent',
        ]);
    }

    public function test_cannot_accept_zero_value_quotation(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createQuotationWithItems(
            $organization,
            $user,
            $customer,
            'sent',
            [
                'quantity' => 1,
                'unit_price' => 0,
                'tax_rate' => 0,
                'discount_percent' => 0,
                'line_total' => 0,
            ],
            [
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
            ],
        );

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('quotations.status.update', $quotation), [
                'status' => 'accepted',
            ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_discount_validation_rejects_invalid_percent(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.store'), [
                'customer_id' => $customer->id,
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'currency' => 'USD',
                'items' => [
                    [
                        'description' => 'Discounted item',
                        'quantity' => 1,
                        'unit_price' => 100,
                        'tax_rate' => 0,
                        'discount_percent' => 150,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('items.0.discount_percent');
    }

    public function test_tax_calculation_applied_on_store(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.store'), [
                'customer_id' => $customer->id,
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'currency' => 'USD',
                'items' => [
                    [
                        'description' => 'Taxable service',
                        'quantity' => 1,
                        'unit_price' => 100,
                        'tax_rate' => 20,
                        'discount_percent' => 0,
                    ],
                ],
            ]);

        $response->assertRedirect();

        $quotation = Quotation::query()->first();
        $this->assertSame(100.0, (float) $quotation->subtotal);
        $this->assertSame(20.0, (float) $quotation->tax_total);
        $this->assertSame(120.0, (float) $quotation->total);
    }

    public function test_acceptance_writes_audit_log(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createQuotationWithItems($organization, $user, $customer, 'sent');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('quotations.status.update', $quotation), [
                'status' => 'accepted',
            ]);

        $response->assertRedirect(route('quotations.show', $quotation));

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'auditable_type' => $quotation->getMorphClass(),
            'auditable_id' => $quotation->id,
            'event' => 'accepted',
        ]);
    }

    public function test_hr_user_cannot_change_quotation_status(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createQuotationWithItems($organization, $user, $customer, 'draft');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('quotations.status.update', $quotation), [
                'status' => 'sent',
            ]);

        $response->assertForbidden();
    }

    public function test_new_quotations_must_be_created_as_draft(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.store'), [
                'customer_id' => $customer->id,
                'status' => 'sent',
                'issue_date' => now()->toDateString(),
                'currency' => 'USD',
                'items' => $this->sampleItems(),
            ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_accepted_can_transition_to_converted(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createQuotationWithItems($organization, $user, $customer, 'accepted');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('quotations.status.update', $quotation), [
                'status' => 'converted',
            ]);

        $response->assertRedirect(route('quotations.show', $quotation));
        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'converted',
        ]);
    }

    public function test_converted_quotation_has_no_further_transitions(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createQuotationWithItems($organization, $user, $customer, 'converted');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('quotations.status.update', $quotation), [
                'status' => 'sent',
            ]);

        $response->assertSessionHasErrors('status');
    }
}
