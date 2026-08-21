<?php

namespace Tests\Feature;

use App\Models\AdjustmentNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Services\AdjustmentNoteService;
use App\Services\RevenueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdjustmentNoteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization}
     */
    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);
        app(\App\Services\TenantContext::class)->set($organization);

        return [$user, $organization];
    }

    protected function createIssuedInvoice(Organization $organization, User $user, float $total = 1000): Invoice
    {
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        return Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'total' => $total,
            'subtotal' => $total,
            'amount_paid' => 0,
            'status' => 'issued',
            'created_by' => $user->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function makeCreditNote(
        Organization $organization,
        User $user,
        Invoice $invoice,
        float $amount = 100,
        array $overrides = [],
    ): AdjustmentNote {
        return app(AdjustmentNoteService::class)->create($organization, 'credit', array_merge([
            'customer_id' => $invoice->customer_id,
            'invoice_id' => $invoice->id,
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'currency' => 'USD',
            'reason' => 'price_adjustment',
            'items' => [[
                'description' => 'Price correction',
                'quantity' => 1,
                'unit_price' => $amount,
                'tax_rate' => 0,
                'discount_percent' => 0,
            ]],
        ], $overrides), $user);
    }

    public function test_manager_can_view_credit_and_debit_note_indexes(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('credit-notes.index'))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('debit-notes.index'))
            ->assertOk();
    }

    public function test_hr_cannot_access_credit_notes(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('credit-notes.index'))
            ->assertForbidden();
    }

    public function test_applying_credit_note_reduces_outstanding_without_changing_invoice_totals(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $invoice = $this->createIssuedInvoice($organization, $user, 1000);
        $notes = app(AdjustmentNoteService::class);

        $note = $this->makeCreditNote($organization, $user, $invoice, 150);
        $note = $notes->issue($note, $user);
        $note = $notes->apply($note, $user);

        $invoice->refresh();

        $this->assertSame(1000.0, (float) $invoice->total);
        $this->assertSame(0.0, (float) $invoice->amount_paid);
        $this->assertSame(150.0, $invoice->creditedAmount());
        $this->assertSame(850.0, $invoice->effective_balance);

        $statement = app(RevenueService::class)->customerStatement($invoice->customer);
        $this->assertEquals(850.0, $statement['outstanding_balance']);
        $this->assertTrue($statement['ledger']->contains(fn (array $entry) => $entry['type'] === 'credit_note'));
    }

    public function test_other_organization_credit_note_is_not_visible(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $other = Organization::factory()->create();
        $otherUser = User::factory()->create();
        $other->addMember($otherUser, 'manager');
        $invoice = $this->createIssuedInvoice($other, $otherUser, 500);
        $note = $this->makeCreditNote($other, $otherUser, $invoice, 50);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id]);

        $status = $this->get(route('credit-notes.show', $note))->status();
        $this->assertContains($status, [403, 404]);
    }

    public function test_receivables_index_is_available_to_managers(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('receivables.index'))
            ->assertOk();
    }
}
