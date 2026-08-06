<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
            $table->string('module', 64);
            $table->string('entity_type', 64);
            $table->string('format', 16);
            $table->string('selection_mode', 32)->default('ids');
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->json('record_ids')->nullable();
            $table->json('filters')->nullable();
            $table->json('columns')->nullable();
            $table->json('metadata')->nullable();
            $table->string('disk', 64)->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('download_token', 64)->nullable()->unique();
            $table->timestamp('download_expires_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'entity_type']);
            $table->index(['organization_id', 'format']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_sessions');
    }
};
