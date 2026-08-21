<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_email_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('related');
            $table->string('thread_id');
            $table->string('subject');
            $table->unsignedInteger('message_count')->default(0);
            $table->string('last_status', 20)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'thread_id'], 'crm_email_conv_org_thread_unique');
            $table->index(['organization_id', 'customer_id', 'last_message_at'], 'crm_email_conv_org_cust_last_idx');
        });

        Schema::create('crm_email_webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 40);
            $table->string('token', 64)->unique();
            $table->text('signing_secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'provider']);
        });

        Schema::create('crm_email_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained('crm_email_webhook_endpoints')->nullOnDelete();
            $table->foreignId('crm_email_message_id')->nullable()->constrained('crm_email_messages')->nullOnDelete();
            $table->string('provider', 40);
            $table->string('provider_event_id');
            $table->string('event', 40);
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'provider', 'provider_event_id'], 'crm_email_wh_evt_unique');
            $table->index(['crm_email_message_id', 'event'], 'crm_email_wh_evt_msg_idx');
        });

        Schema::table('crm_email_messages', function (Blueprint $table) {
            $table->foreignId('conversation_id')->nullable()->after('organization_id')->constrained('crm_email_conversations')->nullOnDelete();
            $table->string('provider', 40)->nullable()->after('status');
            $table->string('provider_message_id')->nullable()->after('provider');
            $table->string('rfc_message_id')->nullable()->after('provider_message_id');
            $table->string('in_reply_to')->nullable()->after('rfc_message_id');
            $table->text('references_header')->nullable()->after('in_reply_to');
            $table->string('thread_id')->nullable()->after('references_header');
            $table->string('mailable_class')->nullable()->after('thread_id');
            $table->string('direction', 16)->default('outbound')->after('mailable_class');
            $table->string('from_email')->nullable()->after('direction');
            $table->string('from_name')->nullable()->after('from_email');
            $table->json('attachment_paths')->nullable()->after('attachments');
            $table->timestamp('queued_at')->nullable()->after('sent_at');
            $table->timestamp('sending_at')->nullable()->after('queued_at');
            $table->timestamp('delivered_at')->nullable()->after('sending_at');
            $table->timestamp('failed_at')->nullable()->after('delivered_at');
            $table->timestamp('bounced_at')->nullable()->after('failed_at');
            $table->string('bounce_type', 40)->nullable()->after('bounced_at');
            $table->text('bounce_reason')->nullable()->after('bounce_type');
            $table->json('provider_metadata')->nullable()->after('bounce_reason');
            $table->string('idempotency_key', 64)->nullable()->after('provider_metadata');

            $table->index(['organization_id', 'rfc_message_id'], 'crm_email_msg_org_rfc_idx');
            $table->index(['organization_id', 'provider_message_id'], 'crm_email_msg_org_provid_idx');
            $table->index(['organization_id', 'thread_id'], 'crm_email_msg_org_thread_idx');
            $table->unique(['organization_id', 'idempotency_key'], 'crm_email_msg_org_idem_unique');
        });
    }

    public function down(): void
    {
        Schema::table('crm_email_messages', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'idempotency_key']);
            $table->dropConstrainedForeignId('conversation_id');
            $table->dropColumn([
                'provider', 'provider_message_id', 'rfc_message_id', 'in_reply_to',
                'references_header', 'thread_id', 'mailable_class', 'direction',
                'from_email', 'from_name', 'attachment_paths', 'queued_at', 'sending_at',
                'delivered_at', 'failed_at', 'bounced_at', 'bounce_type', 'bounce_reason',
                'provider_metadata', 'idempotency_key',
            ]);
        });

        Schema::dropIfExists('crm_email_webhook_events');
        Schema::dropIfExists('crm_email_webhook_endpoints');
        Schema::dropIfExists('crm_email_conversations');
    }
};
