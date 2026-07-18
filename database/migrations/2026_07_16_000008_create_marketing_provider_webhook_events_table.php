<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_provider_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('provider', 64);
            $table->string('event_type', 64)->nullable();
            $table->string('delivery_id', 128)->nullable();
            $table->json('payload');
            $table->string('signature', 255)->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('processing_status', 32);
            $table->timestamps();

            $table->unique(
                ['provider', 'delivery_id'],
                'mkt_prov_webhook_events_prov_delivery_unique'
            );
            $table->index(
                ['provider', 'received_at'],
                'mkt_prov_webhook_events_prov_received_idx'
            );
            $table->index(
                ['provider', 'event_type', 'received_at'],
                'mkt_prov_webhook_events_prov_type_idx'
            );
            $table->index(
                ['processing_status'],
                'mkt_prov_webhook_events_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_provider_webhook_events');
    }
};
