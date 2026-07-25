<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('active');
            $table->string('color', 20)->default('#4f46e5');
            $table->date('start_date')->nullable();
            $table->date('target_end_date')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->json('metadata')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'status'], 'portfolios_org_status_idx');
            $table->index(['organization_id', 'owner_id'], 'portfolios_org_owner_idx');
        });

        Schema::create('portfolio_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained('portfolios')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['portfolio_id', 'project_id']);
            $table->index('project_id');
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('portfolio_id')->nullable()->constrained('portfolios')->nullOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('active');
            $table->string('color', 20)->nullable();
            $table->date('start_date')->nullable();
            $table->date('target_end_date')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'portfolio_id'], 'programs_org_portfolio_idx');
            $table->index(['organization_id', 'status'], 'programs_org_status_idx');
        });

        Schema::create('program_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['program_id', 'project_id']);
            $table->index('project_id');
        });

        Schema::create('project_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('predecessor_project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('successor_project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('dependency_type', 30)->default('finish_to_start');
            $table->integer('lag_days')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['predecessor_project_id', 'successor_project_id', 'dependency_type'],
                'project_dependencies_unique'
            );
            $table->index(['organization_id', 'predecessor_project_id'], 'project_deps_org_pred_idx');
            $table->index(['organization_id', 'successor_project_id'], 'project_deps_org_succ_idx');
        });

        Schema::create('project_risks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->foreignId('portfolio_id')->nullable()->constrained('portfolios')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 50)->nullable();
            $table->unsignedTinyInteger('probability')->default(3);
            $table->unsignedTinyInteger('impact')->default(3);
            $table->unsignedTinyInteger('severity')->default(9);
            $table->text('mitigation_plan')->nullable();
            $table->text('contingency_plan')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status', 40)->default('open');
            $table->timestamp('escalated_at')->nullable();
            $table->json('history')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status'], 'project_risks_org_status_idx');
            $table->index(['organization_id', 'severity'], 'project_risks_org_severity_idx');
            $table->index(['project_id'], 'project_risks_project_idx');
        });

        Schema::create('project_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->foreignId('portfolio_id')->nullable()->constrained('portfolios')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority', 20)->default('medium');
            $table->string('severity', 20)->default('medium');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('open');
            $table->text('resolution')->nullable();
            $table->text('root_cause')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->date('due_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status'], 'project_issues_org_status_idx');
            $table->index(['organization_id', 'priority'], 'project_issues_org_priority_idx');
            $table->index(['project_id'], 'project_issues_project_idx');
        });

        Schema::create('project_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('name')->nullable();
            $table->json('scope_snapshot')->nullable();
            $table->json('schedule_snapshot')->nullable();
            $table->json('budget_snapshot')->nullable();
            $table->json('progress_snapshot')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'version']);
            $table->index(['organization_id', 'project_id'], 'project_baselines_org_project_idx');
        });

        Schema::create('budget_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 80);
            $table->string('color', 20)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('project_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name')->default('Primary Budget');
            $table->string('currency', 10)->default('USD');
            $table->decimal('planned_total', 14, 2)->default(0);
            $table->decimal('actual_total', 14, 2)->default(0);
            $table->decimal('forecast_total', 14, 2)->default(0);
            $table->decimal('variance_total', 14, 2)->default(0);
            $table->string('status', 40)->default('draft');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'project_id'], 'project_budgets_org_project_idx');
        });

        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_budget_id')->constrained('project_budgets')->cascadeOnDelete();
            $table->foreignId('budget_category_id')->nullable()->constrained('budget_categories')->nullOnDelete();
            $table->string('name');
            $table->decimal('planned', 14, 2)->default(0);
            $table->decimal('actual', 14, 2)->default(0);
            $table->decimal('forecast', 14, 2)->default(0);
            $table->decimal('variance', 14, 2)->default(0);
            $table->string('currency', 10)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_budget_id', 'sort_order'], 'budget_items_budget_sort_idx');
        });

        Schema::create('portfolio_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('portfolio_id')->nullable()->constrained('portfolios')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->string('report_type', 50);
            $table->string('format', 20)->default('pdf');
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->json('filters')->nullable();
            $table->string('storage_path')->nullable();
            $table->timestamp('generated_at');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'report_type'], 'portfolio_reports_org_type_idx');
            $table->index(['organization_id', 'portfolio_id'], 'portfolio_reports_org_portfolio_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_reports');
        Schema::dropIfExists('budget_items');
        Schema::dropIfExists('project_budgets');
        Schema::dropIfExists('budget_categories');
        Schema::dropIfExists('project_baselines');
        Schema::dropIfExists('project_issues');
        Schema::dropIfExists('project_risks');
        Schema::dropIfExists('project_dependencies');
        Schema::dropIfExists('program_projects');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('portfolio_projects');
        Schema::dropIfExists('portfolios');
    }
};
