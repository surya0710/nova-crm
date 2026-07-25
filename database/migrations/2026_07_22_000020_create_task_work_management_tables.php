<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('color', 32)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
            $table->index(['organization_id', 'sort_order']);
        });

        Schema::create('task_priorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('color', 32)->nullable();
            $table->unsignedTinyInteger('level')->default(1);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
            $table->index(['organization_id', 'level']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('organization_id')->constrained('projects')->nullOnDelete();
            $table->foreignId('parent_task_id')->nullable()->after('project_id')->constrained('tasks')->nullOnDelete();
            $table->foreignId('milestone_id')->nullable()->after('parent_task_id')->constrained('project_milestones')->nullOnDelete();
            $table->foreignId('status_id')->nullable()->after('milestone_id')->constrained('task_statuses')->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->after('status_id')->constrained('task_priorities')->nullOnDelete();
            $table->string('task_number')->nullable()->after('priority_id');
            $table->string('slug')->nullable()->after('task_number');
            $table->foreignId('assigned_by')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            $table->decimal('estimated_hours', 8, 2)->nullable()->after('completed_at');
            $table->decimal('actual_hours', 8, 2)->nullable()->after('estimated_hours');
            $table->date('start_date')->nullable()->after('actual_hours');
            $table->date('due_date')->nullable()->after('start_date');
            $table->unsignedTinyInteger('completion_percentage')->default(0)->after('due_date');
            $table->json('metadata')->nullable()->after('completion_percentage');
            $table->json('settings')->nullable()->after('metadata');
            $table->unsignedInteger('sort_order')->default(0)->after('settings');
            $table->boolean('is_archived')->default(false)->after('sort_order');

            $table->index(['organization_id', 'project_id']);
            $table->index(['organization_id', 'status_id']);
            $table->index(['organization_id', 'due_date']);
            $table->index(['organization_id', 'is_archived']);
            $table->unique(['organization_id', 'task_number']);
            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('predecessor_task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('successor_task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('dependency_type', 32)->default('finish_to_start');
            $table->timestamps();

            $table->unique(['predecessor_task_id', 'successor_task_id']);
            $table->index(['organization_id', 'predecessor_task_id']);
            $table->index(['organization_id', 'successor_task_id']);
        });

        Schema::create('task_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('sequence')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'task_id', 'sequence']);
        });

        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment');
            $table->foreignId('parent_comment_id')->nullable()->constrained('task_comments')->nullOnDelete();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'task_id']);
            $table->index(['organization_id', 'parent_comment_id']);
        });

        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'task_id']);
        });

        Schema::create('task_time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->text('description')->nullable();
            $table->string('source', 20)->default('manual');
            $table->timestamps();

            $table->index(['organization_id', 'task_id']);
            $table->index(['organization_id', 'user_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_time_logs');
        Schema::dropIfExists('task_attachments');
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_checklists');
        Schema::dropIfExists('task_dependencies');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'task_number']);
            $table->dropUnique(['organization_id', 'slug']);
            $table->dropIndex(['organization_id', 'project_id']);
            $table->dropIndex(['organization_id', 'status_id']);
            $table->dropIndex(['organization_id', 'due_date']);
            $table->dropIndex(['organization_id', 'is_archived']);

            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('parent_task_id');
            $table->dropConstrainedForeignId('milestone_id');
            $table->dropConstrainedForeignId('status_id');
            $table->dropConstrainedForeignId('priority_id');
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropColumn([
                'task_number',
                'slug',
                'estimated_hours',
                'actual_hours',
                'start_date',
                'due_date',
                'completion_percentage',
                'metadata',
                'settings',
                'sort_order',
                'is_archived',
            ]);
        });

        Schema::dropIfExists('task_priorities');
        Schema::dropIfExists('task_statuses');
    }
};
