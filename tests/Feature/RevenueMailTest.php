<?php

namespace Tests\Feature;

use App\Mail\CustomerMail;
use App\Mail\PaymentMail;
use App\Mail\QuotationMail;
use App\Mail\TestOrganizationMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\User;
use App\Services\OrganizationMailConfig;
use App\Services\OrganizationMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\ConfiguresOrganizationMail;
use Tests\TestCase;

class RevenueMailTest extends TestCase
{
    use ConfiguresOrganizationMail;
    use RefreshDatabase;

    protected function setupOrganizationWithMail(string $driver = 'log'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create([
            'email' => 'contact@acme.test',
        ]);
        $organization->addMember($user, 'manager');
        $this->configureOrganizationMail($organization, $driver);

        return [$user, $organization];
    }

    public function test_quotation_envelope_from_is_address_not_array(): void
    {
        [, $organization] = $this->setupOrganizationWithMail();

        $customer = Customer::factory()->create(['organization_id' => $organization->id]);
        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
        ]);
        $quotation->load(['customer', 'creator', 'items']);

        $envelope = (new QuotationMail($quotation, $organization))->envelope();

        $this->assertInstanceOf(Address::class, $envelope->from);
        $this->assertSame('billing@'.$organization->slug.'.test', $envelope->from->address);
        $this->assertStringContainsString($quotation->number, $envelope->subject);
    }

    public function test_quotation_pdf_attachment_has_correct_filename_and_mime(): void
    {
        [, $organization] = $this->setupOrganizationWithMail();

        $customer = Customer::factory()->create(['organization_id' => $organization->id]);
        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
        ]);
        $quotation->load(['customer', 'creator', 'items']);

        $uploadedPdf = UploadedFile::fake()->create('quote.pdf', 100, 'application/pdf');
        $attachments = (new QuotationMail($quotation, $organization, null, [$uploadedPdf]))->attachments();

        $this->assertCount(1, $attachments);
        $this->assertInstanceOf(Attachment::class, $attachments[0]);
        $this->assertSame('quote.pdf', $attachments[0]->as);
        $this->assertSame('application/pdf', $attachments[0]->mime);
    }

    public function test_quotation_mail_sends_with_log_mailer(): void
    {
        [, $organization] = $this->setupOrganizationWithMail();

        $customer = Customer::factory()->create(['organization_id' => $organization->id]);
        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
        ]);
        $quotation->load(['customer', 'creator', 'items']);

        app(OrganizationMailer::class)->send(
            $organization,
            'client@example.com',
            new QuotationMail($quotation, $organization),
        );

        $this->addToAssertionCount(1);
    }

    public function test_quotation_mail_sends_with_smtp_configuration(): void
    {
        Mail::fake();

        [, $organization] = $this->setupOrganizationWithMail('smtp');

        $customer = Customer::factory()->create(['organization_id' => $organization->id]);
        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
        ]);
        $quotation->load(['customer', 'creator', 'items']);

        app(OrganizationMailer::class)->send(
            $organization,
            'client@example.com',
            new QuotationMail($quotation, $organization),
        );

        $mailerName = 'organization_'.$organization->id;

        Mail::assertSent(QuotationMail::class);
        $this->assertSame('smtp', config("mail.mailers.{$mailerName}.transport"));
    }

    public function test_quotation_mailable_survives_queue_serialization(): void
    {
        [, $organization] = $this->setupOrganizationWithMail();

        $customer = Customer::factory()->create(['organization_id' => $organization->id]);
        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
        ]);
        $quotation->load(['customer', 'creator', 'items']);

        $mail = new QuotationMail($quotation, $organization, 'Queued quote');
        $restored = unserialize(serialize($mail));

        $this->assertInstanceOf(Address::class, $restored->envelope()->from);
        $this->assertSame('Queued quote', $restored->personalMessage);
    }

    public function test_payment_envelope_from_is_address_not_array(): void
    {
        [, $organization] = $this->setupOrganizationWithMail();

        $customer = Customer::factory()->create(['organization_id' => $organization->id]);
        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'total' => 500,
            'amount_paid' => 500,
        ]);
        $payment = Payment::factory()->create([
            'organization_id' => $organization->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'amount' => 500,
        ]);
        $payment->load(['invoice', 'customer', 'recorder']);

        $envelope = (new PaymentMail($payment, $organization))->envelope();

        $this->assertInstanceOf(Address::class, $envelope->from);
        $this->assertSame('billing@'.$organization->slug.'.test', $envelope->from->address);
        $this->assertStringContainsString($payment->number, $envelope->subject);
    }

    public function test_payment_mailable_survives_queue_serialization(): void
    {
        [, $organization] = $this->setupOrganizationWithMail();

        $customer = Customer::factory()->create(['organization_id' => $organization->id]);
        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
        ]);
        $payment = Payment::factory()->create([
            'organization_id' => $organization->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
        ]);
        $payment->load(['invoice', 'customer', 'recorder']);

        $mail = new PaymentMail($payment, $organization, 'Thank you');
        $restored = unserialize(serialize($mail));

        $this->assertInstanceOf(Address::class, $restored->envelope()->from);
        $this->assertSame('Thank you', $restored->personalMessage);
    }

    public function test_customer_envelope_from_is_address_not_array(): void
    {
        [, $organization] = $this->setupOrganizationWithMail();

        $customer = Customer::factory()->create(['organization_id' => $organization->id]);

        $envelope = (new CustomerMail(
            $customer,
            $organization,
            'Your documents',
            'Please review the attached files.',
        ))->envelope();

        $this->assertInstanceOf(Address::class, $envelope->from);
        $this->assertSame('billing@'.$organization->slug.'.test', $envelope->from->address);
        $this->assertSame('Your documents', $envelope->subject);
        $this->assertCount(1, $envelope->replyTo);
        $this->assertSame('contact@acme.test', $envelope->replyTo[0]->address);
    }

    public function test_test_organization_mail_envelope_from_is_address_not_array(): void
    {
        [, $organization] = $this->setupOrganizationWithMail();

        $envelope = (new TestOrganizationMail($organization))->envelope();

        $this->assertInstanceOf(Address::class, $envelope->from);
        $this->assertSame('billing@'.$organization->slug.'.test', $envelope->from->address);
        $this->assertStringContainsString($organization->name, $envelope->subject);
    }

    public function test_test_organization_mail_sends_with_smtp_configuration(): void
    {
        Mail::fake();

        [, $organization] = $this->setupOrganizationWithMail('smtp');

        app(OrganizationMailer::class)->send(
            $organization,
            'owner@example.com',
            new TestOrganizationMail($organization),
        );

        $mailerName = 'organization_'.$organization->id;

        Mail::assertSent(TestOrganizationMail::class);
        $this->assertSame('smtp', config("mail.mailers.{$mailerName}.transport"));
        $this->assertSame('smtp.example.com', config("mail.mailers.{$mailerName}.host"));
    }

    public function test_organization_mail_config_is_single_source_of_truth_for_from_address(): void
    {
        [, $organization] = $this->setupOrganizationWithMail();

        $configFrom = app(OrganizationMailConfig::class)->for($organization)->fromAddress();
        $mailableFrom = (new TestOrganizationMail($organization))->envelope()->from;

        $this->assertInstanceOf(Address::class, $configFrom);
        $this->assertSame($configFrom->address, $mailableFrom->address);
        $this->assertSame($configFrom->name, $mailableFrom->name);
    }

    public function test_envelope_from_is_null_when_organization_from_address_missing(): void
    {
        $organization = Organization::factory()->create();
        $settings = $organization->settings ?? [];
        $settings['mail'] = [
            'enabled' => true,
            'driver' => 'log',
            'from_address' => '',
            'from_name' => '',
        ];
        $organization->update(['settings' => $settings]);

        $envelope = (new TestOrganizationMail($organization->fresh()))->envelope();

        $this->assertNull($envelope->from);
    }

    public function test_organization_mailer_rejects_unconfigured_organization(): void
    {
        $organization = Organization::factory()->create();

        $this->expectException(\RuntimeException::class);

        app(OrganizationMailer::class)->send(
            $organization,
            'client@example.com',
            new TestOrganizationMail($organization),
        );
    }

    public function test_organization_mailer_rejects_invalid_smtp_configuration(): void
    {
        $organization = Organization::factory()->create();
        $settings = $organization->settings ?? [];
        $settings['mail'] = [
            'enabled' => true,
            'driver' => 'smtp',
            'host' => '',
            'port' => 587,
            'from_address' => 'billing@example.com',
            'from_name' => 'Billing',
        ];
        $organization->update(['settings' => $settings]);

        $this->assertFalse(app(OrganizationMailConfig::class)->for($organization->fresh())->isConfigured());
    }

    public function test_password_reset_notification_is_unaffected_by_organization_mail_trait(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors();

        $this->addToAssertionCount(1);
    }
}
