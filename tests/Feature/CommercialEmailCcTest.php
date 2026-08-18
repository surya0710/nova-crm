<?php

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Mail\QuotationMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\User;
use App\Services\ClientEmailCc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\ConfiguresOrganizationMail;
use Tests\TestCase;

class CommercialEmailCcTest extends TestCase
{
    use ConfiguresOrganizationMail;
    use RefreshDatabase;

    public function test_merge_adds_sender_preserves_manual_cc_and_dedupes(): void
    {
        $sender = User::factory()->make(['email' => 'sales@acme.test']);

        $cc = ClientEmailCc::merge(
            $sender,
            'client@example.com',
            'accounts@example.com, SALES@acme.test, client@example.com',
        );

        $this->assertSame(['sales@acme.test', 'accounts@example.com'], $cc);
    }

    public function test_merge_skips_sender_when_they_are_the_recipient(): void
    {
        $sender = User::factory()->make(['email' => 'client@example.com']);

        $this->assertSame(
            ['accounts@example.com'],
            ClientEmailCc::merge($sender, 'client@example.com', 'accounts@example.com'),
        );
    }

    public function test_invoice_email_ccs_authenticated_sender_and_manual_recipients(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'sender@acme.test']);
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'manager');
        $this->configureOrganizationMail($organization);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'client@example.com',
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
            'total' => 550,
            'subtotal' => 500,
            'tax_total' => 50,
            'created_by' => $user->id,
        ]);

        $invoice->items()->create([
            'description' => 'Monthly service',
            'quantity' => 1,
            'unit_price' => 500,
            'tax_rate' => 10,
            'discount_percent' => 0,
            'line_total' => 550,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.send', $invoice), [
                'email' => 'client@example.com',
                'cc' => 'accounts@example.com, sender@acme.test',
                'message' => 'Please find the invoice attached.',
            ])
            ->assertRedirect(route('invoices.show', $invoice));

        Mail::assertSent(InvoiceMail::class, function (InvoiceMail $mail) {
            return $mail->hasTo('client@example.com')
                && $mail->hasCc('sender@acme.test')
                && $mail->hasCc('accounts@example.com')
                && $mail->personalMessage === 'Please find the invoice attached.';
        });
    }

    public function test_quotation_email_ccs_authenticated_sender(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'rep@acme.test']);
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'manager');
        $this->configureOrganizationMail($organization);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'buyer@example.com',
            'created_by' => $user->id,
        ]);

        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.send', $quotation), [
                'email' => 'buyer@example.com',
            ])
            ->assertRedirect(route('quotations.show', $quotation));

        Mail::assertSent(QuotationMail::class, function (QuotationMail $mail) {
            return $mail->hasTo('buyer@example.com')
                && $mail->hasCc('rep@acme.test');
        });
    }

    public function test_send_rejects_invalid_cc(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'manager');
        $this->configureOrganizationMail($organization);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.send', $invoice), [
                'email' => 'client@example.com',
                'cc' => 'not-an-email',
            ])
            ->assertSessionHasErrors('cc');
    }
}
