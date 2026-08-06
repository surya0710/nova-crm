<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['organization_id', 'email']);
            $table->index(['organization_id', 'customer_id']);
        });

        Schema::create('client_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('client_project_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_user_id')->constrained('client_users')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->json('scopes')->nullable();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->nullable();
            $table->timestamps();

            $table->unique(['client_user_id', 'project_id']);
            $table->index(['organization_id', 'project_id']);
        });

        Schema::create('project_shared_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('shareable_type');
            $table->unsignedBigInteger('shareable_id');
            $table->json('scopes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_downloads')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shareable_type', 'shareable_id']);
            $table->index(['organization_id', 'expires_at']);
        });

        Schema::create('deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained('project_milestones')->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('draft');
            $table->date('due_date')->nullable();
            $table->unsignedTinyInteger('completion_percentage')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'project_id', 'status']);
            $table->index(['organization_id', 'due_date']);
        });

        Schema::create('deliverable_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deliverable_id')->constrained('deliverables')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('label')->nullable();
            $table->text('notes')->nullable();
            $table->string('disk')->default('local');
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('uploaded_by_client_user_id')->nullable()->constrained('client_users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['deliverable_id', 'version_number']);
        });

        Schema::create('client_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->string('status', 32)->default('draft');
            $table->text('request_message')->nullable();
            $table->text('decision_notes')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by_client_user_id')->nullable()->constrained('client_users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('client_discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('discussable_type');
            $table->unsignedBigInteger('discussable_id');
            $table->foreignId('parent_id')->nullable()->constrained('client_discussions')->nullOnDelete();
            $table->text('body');
            $table->string('visibility', 16)->default('client');
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('author_client_user_id')->nullable()->constrained('client_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['discussable_type', 'discussable_id']);
            $table->index(['organization_id', 'project_id']);
        });

        Schema::create('client_upload_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('client_user_id')->nullable()->constrained('client_users')->nullOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->string('status', 32)->default('open');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'project_id', 'status']);
        });

        Schema::create('client_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_user_id')->constrained('client_users')->cascadeOnDelete();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('action_url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['client_user_id', 'read_at']);
        });

        Schema::create('client_portal_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->boolean('portal_enabled')->default(true);
            $table->string('welcome_message')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_portal_settings');
        Schema::dropIfExists('client_notifications');
        Schema::dropIfExists('client_upload_requests');
        Schema::dropIfExists('client_discussions');
        Schema::dropIfExists('client_approvals');
        Schema::dropIfExists('deliverable_versions');
        Schema::dropIfExists('deliverables');
        Schema::dropIfExists('project_shared_links');
        Schema::dropIfExists('client_project_access');
        Schema::dropIfExists('client_password_reset_tokens');
        Schema::dropIfExists('client_users');
    }
};
