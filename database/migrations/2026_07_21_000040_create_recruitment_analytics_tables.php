<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recruitment_saved_reports')) {
            Schema::create('recruitment_saved_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('report_name');
                $table->string('report_type', 60);
                $table->json('filters_json')->nullable();
                $table->boolean('is_shared')->default(false);
                $table->timestamps();

                $table->unique(['organization_id', 'id'], 'recruitment_saved_reports_org_id_unique');
                $table->index(['organization_id', 'user_id'], 'recruitment_saved_reports_org_user_idx');
                $table->index(['organization_id', 'report_type'], 'recruitment_saved_reports_org_type_idx');
            });
        }

        $this->addIndexIfMissing('job_applications', 'job_applications_org_applied_date_idx', ['organization_id', 'applied_date']);
        $this->addIndexIfMissing('job_applications', 'job_applications_org_recruiter_idx', ['organization_id', 'assigned_recruiter_id']);
        $this->addIndexIfMissing('job_applications', 'job_applications_org_source_idx', ['organization_id', 'source']);

        $this->addIndexIfMissing('offer_letters', 'offer_letters_org_sent_at_idx', ['organization_id', 'sent_at']);
        $this->addIndexIfMissing('offer_letters', 'offer_letters_org_accepted_at_idx', ['organization_id', 'accepted_at']);

        $this->addIndexIfMissing('interview_rounds', 'interview_rounds_org_scheduled_at_idx', ['organization_id', 'scheduled_at']);
        // status index already exists from interview foundation migration

        $this->addIndexIfMissing('hiring_decisions', 'hiring_decisions_org_decision_date_idx', ['organization_id', 'decision_date']);
        $this->addIndexIfMissing('candidates', 'candidates_org_created_at_idx', ['organization_id', 'created_at']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('candidates', 'candidates_org_created_at_idx');
        $this->dropIndexIfExists('hiring_decisions', 'hiring_decisions_org_decision_date_idx');
        $this->dropIndexIfExists('interview_rounds', 'interview_rounds_org_scheduled_at_idx');
        $this->dropIndexIfExists('offer_letters', 'offer_letters_org_sent_at_idx');
        $this->dropIndexIfExists('offer_letters', 'offer_letters_org_accepted_at_idx');
        $this->dropIndexIfExists('job_applications', 'job_applications_org_applied_date_idx');
        $this->dropIndexIfExists('job_applications', 'job_applications_org_recruiter_idx');
        $this->dropIndexIfExists('job_applications', 'job_applications_org_source_idx');

        Schema::dropIfExists('recruitment_saved_reports');
    }

    /**
     * @param  list<string>  $columns
     */
    protected function addIndexIfMissing(string $table, string $index, array $columns): void
    {
        if (Schema::hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index) {
            $blueprint->index($columns, $index);
        });
    }

    protected function dropIndexIfExists(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index) {
            $blueprint->dropIndex($index);
        });
    }
};
