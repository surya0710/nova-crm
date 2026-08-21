<?php

namespace Tests\Feature;

use App\Mail\CrmMessageMail;
use App\Mail\CustomerMail;
use App\Mail\InvoiceMail;
use App\Models\Contact;
use App\Models\CrmActivity;
use App\Models\CrmEmailMessage;
use App\Models\CrmEmailTemplate;
use App\Models\Customer;
use App\Models\CustomerTicket;
use App\Models\Invoice;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\User;
use App\Services\ClientEmailCc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\ConfiguresOrganizationMail;
use Tests\TestCase;

class CrmEmailComposerTest extends TestCase
{
    use ConfiguresOrganizationMail;
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization}
     */
    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create(['email' => 'sender@acme.test']);
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_resolve_dedupes_to_cc_bcc_and_defaults(): void
    {
        $sender = User::factory()->make(['email' => 'sales@acme.test']);

        $recipients = ClientEmailCc::resolve(
            $sender,
            'client@example.com, accounts@example.com',
            'accounts@example.com, extra@example.com',
            'client@example.com, blind@example.com, extra@example.com',
            ['archive@acme.test', 'sales@acme.test'],
            ['blind@example.com'],
            ccSender: true,
        );

        $this->assertSame(['client@example.com', 'accounts@example.com'], $recipients['to']);
        $this->assertSame(['sales@acme.test', 'extra@example.com', 'archive@acme.test'], $recipients['cc']);
        $this->assertSame(['blind@example.com'], $recipients['bcc']);
    }

    public function test_contact_email_is_logged_against_customer_and_contact(): void
    {
        Mail::fake();

        [$user, $organization] = $this->setupUserWithOrg();
        $this->configureOrganizationMail($organization);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'billing@example.com',
            'created_by' => $user->id,
        ]);

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'email' => 'person@example.com',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('contacts.send', $contact), [
                'email' => 'person@example.com, billing@example.com',
                'subject' => 'Follow-up',
                'message' => 'Hello {{contact.name}} from {{company.name}}',
            ])
            ->assertRedirect(route('contacts.show', $contact))
            ->assertSessionHas('status', 'contact-email-sent');

        Mail::assertSent(CrmMessageMail::class, function (CrmMessageMail $mail) use ($contact, $organization) {
            return $mail->hasTo('person@example.com')
                && $mail->hasTo('billing@example.com')
                && $mail->mailSubject === 'Follow-up'
                && str_contains($mail->body, $contact->name)
                && str_contains($mail->body, $organization->name);
        });

        $this->assertDatabaseHas('crm_email_messages', [
            'customer_id' => $customer->id,
            'contact_id' => $contact->id,
            'status' => 'sent',
        ]);

        $this->assertTrue(
            CrmActivity::query()
                ->where('customer_id', $customer->id)
                ->where('contact_id', $contact->id)
                ->where('type', 'email')
                ->exists()
        );
    }

    public function test_invoice_email_applies_default_bcc_and_signature(): void
    {
        Mail::fake();

        [$user, $organization] = $this->setupUserWithOrg();
        $this->configureOrganizationMail($organization);
        $organization->refresh();
        $settings = $organization->settings ?? [];
        $settings['mail']['default_bcc'] = 'archive@acme.test';
        $settings['mail']['signature'] = 'Acme Billing Team';
        $organization->update(['settings' => $settings]);

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
                'include_signature' => '1',
            ])
            ->assertRedirect();

        Mail::assertSent(InvoiceMail::class, function (InvoiceMail $mail) {
            return $mail->hasTo('client@example.com')
                && $mail->hasCc('sender@acme.test')
                && $mail->hasBcc('archive@acme.test')
                && $mail->emailSignature === 'Acme Billing Team';
        });
    }

    public function test_template_fills_customer_email_and_interpolates_variables(): void
    {
        Mail::fake();

        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $this->configureOrganizationMail($organization);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Pat Lee',
            'company' => 'Lee Co',
            'email' => 'pat@example.com',
            'created_by' => $user->id,
        ]);

        $template = CrmEmailTemplate::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Intro',
            'subject' => 'Hello {{customer.name}}',
            'body' => 'From {{company.name}}',
            'category' => 'general',
            'is_active' => true,
            'available_modules' => ['customers'],
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.send', $customer), [
                'email' => 'pat@example.com',
                'template_id' => $template->id,
                'subject' => 'Hello {{customer.name}}',
                'message' => 'From {{company.name}}',
            ])
            ->assertRedirect(route('customers.show', $customer));

        Mail::assertSent(CustomerMail::class, function (CustomerMail $mail) use ($organization) {
            return $mail->mailSubject === 'Hello Pat Lee'
                && $mail->personalMessage === 'From '.$organization->name;
        });
    }

    public function test_opportunity_and_ticket_can_send_composed_email(): void
    {
        Mail::fake();

        [$user, $organization] = $this->setupUserWithOrg();
        $this->configureOrganizationMail($organization);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'buyer@example.com',
            'created_by' => $user->id,
        ]);

        $opportunity = Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'title' => 'Expansion deal',
            'created_by' => $user->id,
        ]);

        $ticket = CustomerTicket::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('pipeline.send', $opportunity), [
                'email' => 'buyer@example.com',
                'subject' => 'About {{opportunity.name}}',
                'message' => 'Next steps',
            ])
            ->assertRedirect(route('pipeline.show', $opportunity));

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tickets.send', $ticket), [
                'email' => 'buyer@example.com',
                'subject' => 'Ticket {{ticket.number}}',
                'message' => 'We are looking into this.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        Mail::assertSent(CrmMessageMail::class, 2);
        $this->assertSame(2, CrmEmailMessage::query()->count());
    }

    public function test_owner_can_create_email_template(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('organization.settings.email-templates.store'), [
                'name' => 'Quote follow-up',
                'subject' => 'Regarding {{quotation.number}}',
                'body' => 'Please review.',
                'category' => 'quotation',
                'is_active' => '1',
                'available_modules' => ['quotations'],
            ])
            ->assertRedirect(route('organization.settings.email-templates.index'));

        $this->assertDatabaseHas('crm_email_templates', [
            'organization_id' => $organization->id,
            'name' => 'Quote follow-up',
            'category' => 'quotation',
        ]);
    }

    public function test_owner_can_save_reply_to_signature_and_default_cc(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('organization.update'), [
                'name' => $organization->name,
                'timezone' => $organization->timezone,
                'currency' => $organization->currency,
                'mail_enabled' => '1',
                'mail_provider' => 'log',
                'mail_from_address' => 'noreply@acme.test',
                'mail_from_name' => 'Acme Billing',
                'mail_reply_to' => 'support@acme.test',
                'mail_default_cc' => 'sales@acme.test',
                'mail_signature' => 'Thanks, Acme',
            ])
            ->assertRedirect(route('organization.edit'));

        $organization->refresh();
        $this->assertSame('support@acme.test', $organization->settings['mail']['reply_to']);
        $this->assertSame('sales@acme.test', $organization->settings['mail']['default_cc']);
        $this->assertSame('Thanks, Acme', $organization->settings['mail']['signature']);
        $this->assertSame('log', $organization->settings['mail']['driver']);
    }
}
