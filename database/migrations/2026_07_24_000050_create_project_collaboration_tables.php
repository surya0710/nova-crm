<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 20)->default('#64748b');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'name']);
            $table->index(['organization_id', 'is_system'], 'project_labels_org_system_idx');
        });

        Schema::create('task_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('label_id')->constrained('project_labels')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'label_id']);
            $table->index('label_id');
        });

        Schema::create('project_watchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
            $table->index(['organization_id', 'user_id'], 'project_watchers_org_user_idx');
        });

        Schema::create('task_watchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
            $table->index(['organization_id', 'user_id'], 'task_watchers_org_user_idx');
        });

        Schema::create('task_recurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('recurrence_type', 30);
            $table->unsignedInteger('interval')->default(1);
            $table->json('days_of_week')->nullable();
            $table->string('end_type', 30)->default('never');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('occurrences')->nullable();
            $table->unsignedInteger('generated_count')->default(0);
            $table->boolean('skip_holidays')->default(false);
            $table->boolean('copy_attachments')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'next_run_at', 'is_active'], 'task_recurrences_org_next_active_idx');
            $table->index(['task_id'], 'task_recurrences_task_idx');
        });

        Schema::create('project_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('category', 50)->nullable();
            $table->string('industry', 50)->nullable();
            $table->foreignId('department_id')->nullable()->constrained('hrms_departments')->nullOnDelete();
            $table->foreignId('source_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_favorite')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->unsignedInteger('usage_count')->default(0);
            $table->json('defaults')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
            $table->index(['organization_id', 'is_system'], 'project_templates_org_system_idx');
            $table->index(['organization_id', 'category'], 'project_templates_org_category_idx');
        });

        Schema::create('template_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_template_id')->constrained('project_templates')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->unsignedInteger('offset_days')->default(0);
            $table->unsignedInteger('duration_days')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['project_template_id', 'sequence'], 'template_milestones_template_seq_idx');
        });

        Schema::create('template_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_template_id')->constrained('project_templates')->cascadeOnDelete();
            $table->foreignId('template_milestone_id')->nullable()->constrained('template_milestones')->nullOnDelete();
            $table->foreignId('parent_template_task_id')->nullable()->constrained('template_tasks')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority', 20)->default('medium');
            $table->unsignedInteger('offset_days')->default(0);
            $table->unsignedInteger('duration_days')->nullable();
            $table->unsignedInteger('estimated_hours')->nullable();
            $table->string('assignee_role', 50)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['project_template_id', 'sort_order'], 'template_tasks_template_sort_idx');
        });

        Schema::create('template_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_task_id')->constrained('template_tasks')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['template_task_id', 'sort_order'], 'template_checklists_task_sort_idx');
        });

        Schema::create('template_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_template_id')->constrained('project_templates')->cascadeOnDelete();
            $table->foreignId('template_task_id')->nullable()->constrained('template_tasks')->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 20)->default('#64748b');
            $table->timestamps();

            $table->index(['project_template_id'], 'template_labels_template_idx');
        });

        Schema::create('project_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('mentioned_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentioned_by')->constrained('users')->cascadeOnDelete();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->text('excerpt')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'mentioned_user_id'], 'project_mentions_org_user_idx');
            $table->index(['source_type', 'source_id'], 'project_mentions_source_idx');
            $table->index(['project_id'], 'project_mentions_project_idx');
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('digest_enabled')->default(false);
            $table->string('digest_frequency', 20)->default('daily');
            $table->json('muted_projects')->nullable();
            $table->json('muted_tasks')->nullable();
            $table->json('event_preferences')->nullable();
            $table->json('channels')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
        });

        Schema::create('project_calendar_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained('project_milestones')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('provider', 30)->default('internal');
            $table->string('external_event_id')->nullable();
            $table->string('event_type', 50);
            $table->string('title');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->date('due_date')->nullable();
            $table->string('sync_status', 30)->default('synced');
            $table->timestamp('last_synced_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'provider'], 'project_calendar_links_org_provider_idx');
            $table->index(['organization_id', 'due_date'], 'project_calendar_links_org_due_idx');
            $table->index(['project_id'], 'project_calendar_links_project_idx');
        });

        Schema::create('project_collaboration_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('pinned_by')->constrained('users')->cascadeOnDelete();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'source_type', 'source_id'], 'project_collab_pins_unique');
            $table->index(['organization_id', 'project_id'], 'project_collab_pins_org_project_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_collaboration_pins');
        Schema::dropIfExists('project_calendar_links');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('project_mentions');
        Schema::dropIfExists('template_labels');
        Schema::dropIfExists('template_checklists');
        Schema::dropIfExists('template_tasks');
        Schema::dropIfExists('template_milestones');
        Schema::dropIfExists('project_templates');
        Schema::dropIfExists('task_recurrences');
        Schema::dropIfExists('task_watchers');
        Schema::dropIfExists('project_watchers');
        Schema::dropIfExists('task_labels');
        Schema::dropIfExists('project_labels');
    }
};
