<?php

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\ConfiguresOrganizationMail;
use Tests\TestCase;

class InvoiceMailTest extends TestCase
{
    use ConfiguresOrganizationMail;
    use RefreshDatabase;

    protected function createInvoiceMailContext(?string $invoiceTitle = null): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create([
            'email' => 'contact@acme.test',
        ]);
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
            'created_by' => $user->id,
            'title' => $invoiceTitle,
        ]);

        $invoice->load(['customer', 'creator', 'items']);

        return [$user, $organization, $invoice];
    }

    public function test_envelope_from_is_address_not_array(): void
    {
        [, $organization, $invoice] = $this->createInvoiceMailContext();

        $envelope = (new InvoiceMail($invoice, $organization))->envelope();

        $this->assertInstanceOf(Address::class, $envelope->from);
        $this->assertSame(
            'billing@'.$organization->slug.'.test',
            $envelope->from->address,
        );
        $this->assertSame($organization->name, $envelope->from->name);
    }

    public function test_envelope_subject_without_title(): void
    {
        [, $organization, $invoice] = $this->createInvoiceMailContext(null);

        $envelope = (new InvoiceMail($invoice, $organization))->envelope();

        $this->assertStringContainsString($invoice->number, $envelope->subject);
        $this->assertStringContainsString($organization->name, $envelope->subject);
    }

    public function test_envelope_subject_with_title(): void
    {
        [, $organization, $invoice] = $this->createInvoiceMailContext('Annual subscription');

        $envelope = (new InvoiceMail($invoice, $organization))->envelope();

        $this->assertStringContainsString($invoice->number, $envelope->subject);
        $this->assertStringContainsString('Annual subscription', $envelope->subject);
    }

    public function test_envelope_reply_to_uses_organization_email(): void
    {
        [, $organization, $invoice] = $this->createInvoiceMailContext();

        $envelope = (new InvoiceMail($invoice, $organization))->envelope();

        $this->assertCount(1, $envelope->replyTo);
        $this->assertInstanceOf(Address::class, $envelope->replyTo[0]);
        $this->assertSame('contact@acme.test', $envelope->replyTo[0]->address);
        $this->assertSame($organization->name, $envelope->replyTo[0]->name);
    }

    public function test_pdf_attachment_has_correct_filename_and_mime(): void
    {
        [, $organization, $invoice] = $this->createInvoiceMailContext();

        $uploadedPdf = UploadedFile::fake()->create('invoice-terms.pdf', 100, 'application/pdf');

        $mail = new InvoiceMail($invoice, $organization, null, [$uploadedPdf]);
        $attachments = $mail->attachments();

        $this->assertCount(1, $attachments);
        $this->assertInstanceOf(Attachment::class, $attachments[0]);
        $this->assertSame('invoice-terms.pdf', $attachments[0]->as);
        $this->assertSame('application/pdf', $attachments[0]->mime);
    }

    public function test_mail_sends_with_log_mailer_without_fake(): void
    {
        [, $organization, $invoice] = $this->createInvoiceMailContext();

        $mailer = app(OrganizationMailer::class);

        $mailer->send($organization, 'client@example.com', new InvoiceMail($invoice, $organization));

        $this->addToAssertionCount(1);
    }

    public function test_mail_sends_with_sync_array_driver(): void
    {
        [, $organization, $invoice] = $this->createInvoiceMailContext();

        Mail::fake();

        $config = app(\App\Services\OrganizationMailConfig::class)->for($organization);
        $mailerName = $config->registerMailer();

        Mail::mailer($mailerName)
            ->to('client@example.com')
            ->send(new InvoiceMail($invoice, $organization));

        Mail::mailer($mailerName)->assertSent(InvoiceMail::class);
    }

    public function test_mail_sends_with_smtp_organization_mailer_configuration(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'manager');
        $this->configureOrganizationMail($organization, 'smtp');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);

        $invoice->load(['customer', 'creator', 'items']);

        app(OrganizationMailer::class)->send(
            $organization,
            'client@example.com',
            new InvoiceMail($invoice, $organization),
        );

        $mailerName = 'organization_'.$organization->id;

        Mail::assertSent(InvoiceMail::class);
        $this->assertSame('smtp', config("mail.mailers.{$mailerName}.transport"));
        $this->assertSame('smtp.example.com', config("mail.mailers.{$mailerName}.host"));
    }

    public function test_mailable_survives_queue_serialization(): void
    {
        [, $organization, $invoice] = $this->createInvoiceMailContext();

        $mail = new InvoiceMail($invoice, $organization, 'Queued message');
        $restored = unserialize(serialize($mail));

        $envelope = $restored->envelope();

        $this->assertInstanceOf(Address::class, $envelope->from);
        $this->assertSame('Queued message', $restored->personalMessage);
    }

    public function test_send_rejects_missing_recipient(): void
    {
        [$user, $organization, $invoice] = $this->createInvoiceMailContext();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.send', $invoice), []);

        $response->assertSessionHasErrors('email');
    }

    public function test_send_rejects_invalid_recipient(): void
    {
        [$user, $organization, $invoice] = $this->createInvoiceMailContext();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.send', $invoice), ['email' => 'not-an-email']);

        $response->assertSessionHasErrors('email');
    }

    public function test_send_blocked_when_organization_mail_not_configured(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'manager');

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

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.send', $invoice), ['email' => 'client@example.com']);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('error');

        Mail::assertNothingSent();
    }

    public function test_envelope_builds_when_from_address_missing_from_config(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'manager');

        $settings = $organization->settings ?? [];
        $settings['mail'] = [
            'enabled' => true,
            'driver' => 'log',
            'from_address' => '',
            'from_name' => '',
        ];
        $organization->update(['settings' => $settings]);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);

        $envelope = (new InvoiceMail($invoice, $organization->fresh()))->envelope();

        $this->assertNull($envelope->from);
    }

    public function test_mail_without_uploaded_attachments_has_empty_attachment_list(): void
    {
        [, $organization, $invoice] = $this->createInvoiceMailContext();

        $attachments = (new InvoiceMail($invoice, $organization))->attachments();

        $this->assertSame([], $attachments);
    }
}
