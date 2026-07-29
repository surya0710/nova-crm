<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('name');
            $table->text('goal')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 32)->default('planned');
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'project_id', 'status']);
            $table->index(['organization_id', 'start_date', 'end_date']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('sprint_id')->nullable()->after('milestone_id')->constrained('sprints')->nullOnDelete();
            $table->index(['organization_id', 'sprint_id']);
        });

        Schema::table('task_statuses', function (Blueprint $table) {
            $table->unsignedInteger('wip_limit')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sprint_id');
        });

        Schema::table('task_statuses', function (Blueprint $table) {
            $table->dropColumn('wip_limit');
        });

        Schema::dropIfExists('sprints');
    }
};
