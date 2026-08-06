<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_users', function (Blueprint $table) {
            $table->json('preferences')->nullable()->after('two_factor_ready');
            $table->timestamp('locked_at')->nullable()->after('status');
            $table->unsignedInteger('failed_login_attempts')->default(0)->after('locked_at');
        });

        Schema::create('platform_support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->string('subject');
            $table->text('body')->nullable();
            $table->string('status')->default('open');
            $table->string('priority')->default('normal');
            $table->string('category')->default('general');
            $table->string('requester_name')->nullable();
            $table->string('requester_email')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
        });

        Schema::create('platform_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('type')->default('announcement');
            $table->string('status')->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('broadcast')->default(false);
            $table->timestamps();

            $table->index(['type', 'status']);
        });

        Schema::create('platform_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->default('percent');
            $table->decimal('value', 10, 2);
            $table->string('applies_to_plan')->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('redemptions')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('platform_billing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('number')->nullable();
            $table->string('status')->default('pending');
            $table->string('plan')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('occurred_at');
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->index();
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('platform_billing_records');
        Schema::dropIfExists('platform_coupons');
        Schema::dropIfExists('platform_announcements');
        Schema::dropIfExists('platform_support_tickets');

        Schema::table('platform_users', function (Blueprint $table) {
            $table->dropColumn(['preferences', 'locked_at', 'failed_login_attempts']);
        });
    }
};
