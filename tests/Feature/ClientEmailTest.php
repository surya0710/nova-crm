<?php

namespace Tests\Feature;

use App\Mail\CustomerMail;
use App\Mail\InvoiceMail;
use App\Mail\PaymentMail;
use App\Mail\QuotationMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\ConfiguresOrganizationMail;
use Tests\TestCase;

class ClientEmailTest extends TestCase
{
    use ConfiguresOrganizationMail;
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_quotation_email_can_include_attachments(): void
    {
        Mail::fake();

        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->configureOrganizationMail($organization);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'client@example.com',
            'created_by' => $user->id,
        ]);

        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.send', $quotation), [
                'email' => 'client@example.com',
                'message' => 'Please see attached brochure.',
                'attachments' => [
                    UploadedFile::fake()->create('brochure.pdf', 100, 'application/pdf'),
                ],
            ]);

        $response->assertRedirect(route('quotations.show', $quotation));
        $response->assertSessionHas('status', 'quotation-email-sent');

        Mail::assertSent(QuotationMail::class, function (QuotationMail $mail) {
            return count($mail->uploadedAttachments) === 1
                && $mail->uploadedAttachments[0]->getClientOriginalName() === 'brochure.pdf';
        });
    }

    public function test_invoice_email_can_include_attachments(): void
    {
        Mail::fake();

        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->configureOrganizationMail($organization);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'client@example.com',
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
                'attachments' => [
                    UploadedFile::fake()->create('terms.pdf', 50, 'application/pdf'),
                ],
            ])
            ->assertRedirect();

        Mail::assertSent(InvoiceMail::class, fn (InvoiceMail $mail) => count($mail->uploadedAttachments) === 1);
    }

    public function test_manager_can_email_payment_receipt_to_client(): void
    {
        Mail::fake();

        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->configureOrganizationMail($organization);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'client@example.com',
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'total' => 500,
            'amount_paid' => 500,
            'status' => 'paid',
            'created_by' => $user->id,
        ]);

        $payment = Payment::factory()->create([
            'organization_id' => $organization->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'amount' => 500,
            'recorded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('payments.send', $payment), [
                'email' => 'client@example.com',
                'message' => 'Thank you for your payment.',
            ]);

        $response->assertRedirect(route('payments.show', $payment));
        $response->assertSessionHas('status', 'payment-email-sent');

        Mail::assertSent(PaymentMail::class);
    }

    public function test_manager_can_email_customer_with_attachments(): void
    {
        Mail::fake();

        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->configureOrganizationMail($organization);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'client@example.com',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.send', $customer), [
                'email' => 'client@example.com',
                'subject' => 'Your documents',
                'message' => 'Please find the attached files.',
                'attachments' => [
                    UploadedFile::fake()->create('contract.pdf', 80, 'application/pdf'),
                    UploadedFile::fake()->create('id-scan.pdf', 60, 'application/pdf'),
                ],
            ]);

        $response->assertRedirect(route('customers.show', $customer));
        $response->assertSessionHas('status', 'customer-email-sent');

        Mail::assertSent(CustomerMail::class, function (CustomerMail $mail) {
            return $mail->mailSubject === 'Your documents'
                && count($mail->uploadedAttachments) === 2;
        });
    }

    public function test_hr_user_cannot_email_customer(): void
    {
        Mail::fake();

        [$user, $organization] = $this->setupUserWithOrg('hr');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'client@example.com',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.send', $customer), [
                'email' => 'client@example.com',
                'subject' => 'Hello',
            ])
            ->assertForbidden();

        Mail::assertNothingSent();
    }

    public function test_invalid_attachment_type_is_rejected(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->configureOrganizationMail($organization);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'client@example.com',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.send', $customer), [
                'email' => 'client@example.com',
                'subject' => 'Files',
                'attachments' => [
                    UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload'),
                ],
            ]);

        $response->assertSessionHasErrors('attachments.0');
    }
}
