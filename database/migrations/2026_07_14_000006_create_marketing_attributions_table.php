<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketing_visitor_id')->constrained('marketing_visitors')->cascadeOnDelete();
            $table->foreignId('marketing_session_id')->nullable()->constrained('marketing_sessions')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            $table->string('attribution_model', 50)->default('first_touch');
            $table->boolean('is_primary')->default(true);
            $table->timestamp('attributed_at')->useCurrent();
            $table->timestamps();

            $table->unique('lead_id', 'mkt_attr_lead_unique');
            $table->index(['organization_id', 'marketing_visitor_id'], 'mkt_attr_org_visitor_idx');
            $table->index(['organization_id', 'customer_id'], 'mkt_attr_org_customer_idx');
            $table->index(['organization_id', 'attribution_model'], 'mkt_attr_org_model_idx');
            $table->index(['marketing_visitor_id', 'is_primary'], 'mkt_attr_visitor_primary_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_attributions');
    }
};
