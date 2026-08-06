<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_visitors', function (Blueprint $table) {
            $table->id();
            // Nullable by design: an anonymous visitor may exist before any
            // tenant association. Ownership is resolved at attribution (7B.4).
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('visitor_uuid')->unique();
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->string('first_ip', 45)->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->string('first_user_agent', 1024)->nullable();
            $table->string('last_user_agent', 1024)->nullable();
            $table->timestamps();

            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_visitors');
    }
};
