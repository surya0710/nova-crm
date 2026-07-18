<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketing_attribution_id')->constrained('marketing_attributions')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            $table->string('event_name', 50);
            $table->decimal('event_value', 15, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['organization_id', 'event_name', 'occurred_at'], 'mkt_conv_org_event_occurred_idx');
            $table->index(['organization_id', 'marketing_attribution_id'], 'mkt_conv_org_attr_idx');
            // Duplicate prevention: one canonical event per subject entity.
            $table->unique(['event_name', 'lead_id'], 'mkt_conv_event_lead_unique');
            $table->unique(['event_name', 'customer_id'], 'mkt_conv_event_customer_unique');
            $table->unique(['event_name', 'opportunity_id'], 'mkt_conv_event_opp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_conversions');
    }
};
