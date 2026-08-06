<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 80);
            $table->string('display_name');
            $table->string('category', 50);
            $table->string('status', 30)->default('disconnected');
            $table->string('external_account_id')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('configuration')->nullable();
            $table->json('metadata')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_health_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'slug'], 'rec_prov_org_slug_unique');
            $table->index(['organization_id', 'category', 'status'], 'rec_prov_org_cat_status_idx');
        });

        Schema::create('recruitment_provider_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('recruitment_provider_id');
            $table->foreign('recruitment_provider_id', 'rec_prov_cred_provider_fk')
                ->references('id')->on('recruitment_providers')->cascadeOnDelete();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->string('token_type', 50)->nullable();
            $table->json('scopes')->nullable();
            $table->json('configuration')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('recruitment_provider_id', 'rec_prov_cred_provider_unique');
            $table->index(['organization_id', 'expires_at'], 'rec_prov_cred_org_expires_idx');
        });

        Schema::create('recruitment_communication_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('key', 80);
            $table->string('name');
            $table->string('channel', 30)->default('email');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('variables')->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'key', 'channel'], 'rec_comm_tpl_org_key_channel_unique');
            $table->index(['organization_id', 'status'], 'rec_comm_tpl_org_status_idx');
        });

        Schema::create('recruitment_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('recruitment_provider_id');
            $table->foreign('recruitment_provider_id', 'rec_cal_evt_provider_fk')
                ->references('id')->on('recruitment_providers')->cascadeOnDelete();
            $table->foreignId('interview_round_id')->constrained('interview_rounds')->cascadeOnDelete();
            $table->string('external_event_id')->nullable();
            $table->string('meeting_link')->nullable();
            $table->string('meeting_provider', 50)->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('last_error')->nullable();
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['interview_round_id', 'recruitment_provider_id'], 'rec_cal_evt_round_provider_unique');
            $table->index(['organization_id', 'status'], 'rec_cal_evt_org_status_idx');
        });

        Schema::create('recruitment_job_board_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('recruitment_provider_id');
            $table->foreign('recruitment_provider_id', 'rec_job_list_provider_fk')
                ->references('id')->on('recruitment_providers')->cascadeOnDelete();
            $table->foreignId('job_opening_id')->constrained('job_openings')->cascadeOnDelete();
            $table->string('external_job_id')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('last_error')->nullable();
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['job_opening_id', 'recruitment_provider_id'], 'rec_job_list_opening_provider_unique');
            $table->index(['organization_id', 'status'], 'rec_job_list_org_status_idx');
            $table->index(['next_retry_at'], 'rec_job_list_retry_idx');
        });

        Schema::create('recruitment_resume_parse_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('recruitment_provider_id')->nullable();
            $table->foreign('recruitment_provider_id', 'rec_resume_parse_provider_fk')
                ->references('id')->on('recruitment_providers')->nullOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained('candidates')->nullOnDelete();
            $table->unsignedBigInteger('candidate_resume_id')->nullable();
            $table->foreign('candidate_resume_id', 'rec_resume_parse_resume_fk')
                ->references('id')->on('candidate_resumes')->nullOnDelete();
            $table->string('status', 30)->default('pending');
            $table->json('parsed_data')->nullable();
            $table->boolean('applied_to_candidate')->default(false);
            $table->boolean('overwrite_confirmed')->default(false);
            $table->text('last_error')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'status'], 'rec_resume_parse_org_status_idx');
        });

        Schema::create('recruitment_background_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('recruitment_provider_id')->nullable();
            $table->foreign('recruitment_provider_id', 'rec_bgv_provider_fk')
                ->references('id')->on('recruitment_providers')->nullOnDelete();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('hiring_decision_id')->constrained('hiring_decisions')->cascadeOnDelete();
            $table->string('external_verification_id')->nullable();
            $table->string('status', 30)->default('pending');
            $table->json('documents')->nullable();
            $table->json('result')->nullable();
            $table->text('last_error')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status'], 'rec_bgv_org_status_idx');
            $table->unique(['hiring_decision_id', 'recruitment_provider_id'], 'rec_bgv_decision_provider_unique');
        });

        Schema::create('recruitment_webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            $table->string('secret')->nullable();
            $table->json('events');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'is_active'], 'rec_wh_ep_org_active_idx');
        });

        Schema::create('recruitment_webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('recruitment_webhook_endpoint_id');
            $table->foreign('recruitment_webhook_endpoint_id', 'rec_wh_del_endpoint_fk')
                ->references('id')->on('recruitment_webhook_endpoints')->cascadeOnDelete();
            $table->string('event_key', 80);
            $table->string('status', 30)->default('pending');
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('payload');
            $table->text('response_body')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status'], 'rec_wh_del_org_status_idx');
            $table->index(['next_retry_at'], 'rec_wh_del_retry_idx');
            $table->index(['event_key'], 'rec_wh_del_event_idx');
        });

        Schema::table('interview_rounds', function (Blueprint $table) {
            $table->string('meeting_link')->nullable()->after('location');
            $table->string('meeting_provider', 50)->nullable()->after('meeting_link');
        });
    }

    public function down(): void
    {
        Schema::table('interview_rounds', function (Blueprint $table) {
            $table->dropColumn(['meeting_link', 'meeting_provider']);
        });

        Schema::dropIfExists('recruitment_webhook_deliveries');
        Schema::dropIfExists('recruitment_webhook_endpoints');
        Schema::dropIfExists('recruitment_background_verifications');
        Schema::dropIfExists('recruitment_resume_parse_requests');
        Schema::dropIfExists('recruitment_job_board_listings');
        Schema::dropIfExists('recruitment_calendar_events');
        Schema::dropIfExists('recruitment_communication_templates');
        Schema::dropIfExists('recruitment_provider_credentials');
        Schema::dropIfExists('recruitment_providers');
    }
};
