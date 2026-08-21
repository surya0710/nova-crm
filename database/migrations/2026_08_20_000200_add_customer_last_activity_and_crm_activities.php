<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('source');
            $table->index(['organization_id', 'last_activity_at']);
        });

        DB::table('customers')
            ->whereNull('last_activity_at')
            ->update(['last_activity_at' => DB::raw('updated_at')]);

        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('subject');
            $table->text('body')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('direction', 20)->nullable();
            $table->string('outcome', 50)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'type']);
            $table->index(['customer_id', 'occurred_at']);
            $table->index(['contact_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'last_activity_at']);
            $table->dropColumn('last_activity_at');
        });
    }
};
