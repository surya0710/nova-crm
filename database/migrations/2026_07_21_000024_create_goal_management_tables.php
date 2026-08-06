<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'goal_categories_org_id_unique');
            $table->unique(['organization_id', 'code'], 'goal_categories_org_code_unique');
            $table->index(['organization_id', 'is_active'], 'goal_categories_org_active_idx');
        });

        Schema::create('goal_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('goal_category_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('goal_type', 30)->default('individual');
            $table->decimal('default_weight', 8, 2)->default(0);
            $table->string('measurement_type', 30)->default('percentage');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'goal_templates_org_id_unique');
            $table->foreign('goal_category_id', 'goal_templates_category_fk')
                ->references('id')
                ->on('goal_categories')
                ->nullOnDelete();
            $table->index(['organization_id', 'is_active'], 'goal_templates_org_active_idx');
            $table->index(['organization_id', 'goal_type'], 'goal_templates_org_type_idx');
            $table->index(['organization_id', 'goal_category_id'], 'goal_templates_org_category_idx');
        });

        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->string('unit', 50)->nullable();
            $table->string('measurement_type', 30)->default('numeric');
            $table->decimal('default_target', 15, 4)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'kpis_org_id_unique');
            $table->unique(['organization_id', 'code'], 'kpis_org_code_unique');
            $table->index(['organization_id', 'is_active'], 'kpis_org_active_idx');
        });

        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('performance_cycle_id');
            $table->unsignedBigInteger('goal_template_id')->nullable();
            $table->unsignedBigInteger('kpi_id')->nullable();
            $table->unsignedBigInteger('goal_category_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('goal_type', 30)->default('individual');
            $table->string('assignee_type', 30)->default('employee');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('measurement_type', 30)->default('percentage');
            $table->decimal('target_value', 15, 4)->nullable();
            $table->decimal('current_value', 15, 4)->default(0);
            $table->decimal('weight', 8, 2)->default(0);
            $table->decimal('achievement_percentage', 8, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'goals_org_id_unique');
            $table->foreign(['organization_id', 'performance_cycle_id'], 'goals_org_cycle_fk')
                ->references(['organization_id', 'id'])
                ->on('performance_cycles')
                ->restrictOnDelete();
            $table->foreign('goal_template_id', 'goals_template_fk')
                ->references('id')
                ->on('goal_templates')
                ->nullOnDelete();
            $table->foreign('kpi_id', 'goals_kpi_fk')
                ->references('id')
                ->on('kpis')
                ->nullOnDelete();
            $table->foreign('goal_category_id', 'goals_category_fk')
                ->references('id')
                ->on('goal_categories')
                ->nullOnDelete();
            $table->foreign('employee_id', 'goals_employee_fk')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
            $table->foreign('team_id', 'goals_team_fk')
                ->references('id')
                ->on('hrms_teams')
                ->nullOnDelete();
            $table->foreign('department_id', 'goals_department_fk')
                ->references('id')
                ->on('hrms_departments')
                ->nullOnDelete();
            $table->index(['organization_id', 'status'], 'goals_org_status_idx');
            $table->index(['organization_id', 'performance_cycle_id', 'employee_id'], 'goals_org_cycle_employee_idx');
            $table->index(['organization_id', 'assignee_type'], 'goals_org_assignee_type_idx');
        });

        Schema::create('goal_progress_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('goal_id');
            $table->decimal('progress_value', 15, 4);
            $table->decimal('achievement_percentage', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'goal_progress_updates_org_id_unique');
            $table->foreign(['organization_id', 'goal_id'], 'goal_progress_updates_org_goal_fk')
                ->references(['organization_id', 'id'])
                ->on('goals')
                ->cascadeOnDelete();
            $table->index(['organization_id', 'goal_id'], 'goal_progress_updates_org_goal_idx');
        });

        Schema::create('goal_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('goal_id');
            $table->text('summary');
            $table->text('progress')->nullable();
            $table->text('risks')->nullable();
            $table->text('next_steps')->nullable();
            $table->foreignId('checked_in_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'goal_checkins_org_id_unique');
            $table->foreign(['organization_id', 'goal_id'], 'goal_checkins_org_goal_fk')
                ->references(['organization_id', 'id'])
                ->on('goals')
                ->cascadeOnDelete();
            $table->index(['organization_id', 'goal_id'], 'goal_checkins_org_goal_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_checkins');
        Schema::dropIfExists('goal_progress_updates');
        Schema::dropIfExists('goals');
        Schema::dropIfExists('kpis');
        Schema::dropIfExists('goal_templates');
        Schema::dropIfExists('goal_categories');
    }
};
