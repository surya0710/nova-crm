<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_health_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('health_status', 50);
            $table->unsignedTinyInteger('completion_percentage')->default(0);
            $table->decimal('schedule_variance', 8, 2)->nullable();
            $table->decimal('budget_variance', 12, 2)->nullable();
            $table->date('estimated_completion_date')->nullable();
            $table->timestamp('calculated_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(
                ['organization_id', 'project_id', 'calculated_at'],
                'project_health_snapshots_org_project_calc_idx'
            );
            $table->index(
                ['organization_id', 'health_status'],
                'project_health_snapshots_org_status_idx'
            );
        });

        Schema::create('progress_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained('project_milestones')->nullOnDelete();
            $table->foreignId('updated_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('progress_percentage');
            $table->text('summary');
            $table->text('blockers')->nullable();
            $table->text('next_steps')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'project_id'], 'progress_updates_org_project_idx');
            $table->index(['organization_id', 'created_at'], 'progress_updates_org_created_idx');
        });

        Schema::create('project_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('report_type', 50);
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->json('filters')->nullable();
            $table->string('storage_path')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index(['organization_id', 'project_id'], 'project_reports_org_project_idx');
            $table->index(['organization_id', 'report_type'], 'project_reports_org_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_reports');
        Schema::dropIfExists('progress_updates');
        Schema::dropIfExists('project_health_snapshots');
    }
};
