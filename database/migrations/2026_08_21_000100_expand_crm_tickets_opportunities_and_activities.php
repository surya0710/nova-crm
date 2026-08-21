<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_tickets', function (Blueprint $table) {
            $table->timestamp('due_at')->nullable()->after('resolved_at');
            $table->timestamp('first_response_at')->nullable()->after('due_at');
            $table->timestamp('closed_at')->nullable()->after('first_response_at');
            $table->unsignedInteger('sla_hours')->nullable()->after('closed_at');
            $table->index(['organization_id', 'due_at']);
            $table->index(['organization_id', 'assigned_to']);
            $table->index(['organization_id', 'priority']);
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('source', 50)->nullable()->after('lost_reason');
            $table->string('competitor')->nullable()->after('source');
            $table->timestamp('next_activity_at')->nullable()->after('competitor');
            $table->string('next_activity_type', 30)->nullable()->after('next_activity_at');
            $table->string('next_activity_subject')->nullable()->after('next_activity_type');
            $table->index(['organization_id', 'source']);
            $table->index(['organization_id', 'next_activity_at']);
        });

        Schema::create('opportunity_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('role', 40)->default('other');
            $table->timestamps();

            $table->unique(['opportunity_id', 'contact_id']);
            $table->index(['organization_id', 'opportunity_id']);
        });

        Schema::create('opportunity_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['organization_id', 'opportunity_id']);
        });

        Schema::table('crm_activities', function (Blueprint $table) {
            $table->foreignId('opportunity_id')->nullable()->after('contact_id')->constrained()->nullOnDelete();
            $table->string('status', 20)->default('completed')->after('outcome');
            $table->string('priority', 20)->nullable()->after('status');
            $table->timestamp('completed_at')->nullable()->after('priority');
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'assigned_to', 'due_at']);
            $table->index(['opportunity_id', 'occurred_at']);
        });

        Schema::create('customer_lifecycle_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('milestone', 80);
            $table->string('from_stage', 50)->nullable();
            $table->string('to_stage', 50)->nullable();
            $table->nullableMorphs('source');
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->unique(['organization_id', 'customer_id', 'milestone'], 'customer_lifecycle_milestones_unique');
        });

        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->timestamps();

            $table->unique(['organization_id', 'user_id', 'year', 'month'], 'sales_targets_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_targets');
        Schema::dropIfExists('customer_lifecycle_milestones');

        Schema::table('crm_activities', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'status']);
            $table->dropIndex(['organization_id', 'assigned_to', 'due_at']);
            $table->dropIndex(['opportunity_id', 'occurred_at']);
            $table->dropConstrainedForeignId('opportunity_id');
            $table->dropColumn(['status', 'priority', 'completed_at']);
        });

        Schema::dropIfExists('opportunity_products');
        Schema::dropIfExists('opportunity_contacts');

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'source']);
            $table->dropIndex(['organization_id', 'next_activity_at']);
            $table->dropColumn([
                'source',
                'competitor',
                'next_activity_at',
                'next_activity_type',
                'next_activity_subject',
            ]);
        });

        Schema::table('customer_tickets', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'due_at']);
            $table->dropIndex(['organization_id', 'assigned_to']);
            $table->dropIndex(['organization_id', 'priority']);
            $table->dropColumn(['due_at', 'first_response_at', 'closed_at', 'sla_hours']);
        });
    }
};
