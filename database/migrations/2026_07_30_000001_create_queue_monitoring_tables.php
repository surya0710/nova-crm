<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_job_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('job_uuid');
            $table->string('job_id')->nullable();
            $table->string('connection', 100);
            $table->string('queue', 255);
            $table->string('job_name');
            $table->unsignedInteger('attempt')->default(1);
            $table->string('status', 30);
            $table->string('worker_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->string('exception_class')->nullable();
            $table->text('exception_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['job_uuid', 'attempt'], 'queue_job_runs_uuid_attempt_unique');
            $table->index(['connection', 'queue', 'status'], 'queue_job_runs_connection_queue_status_idx');
            $table->index(['status', 'finished_at'], 'queue_job_runs_status_finished_idx');
            $table->index(['organization_id', 'finished_at'], 'queue_job_runs_org_finished_idx');
        });

        Schema::create('queue_worker_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->string('worker_id')->unique();
            $table->string('hostname');
            $table->unsignedBigInteger('process_id')->nullable();
            $table->string('connection', 100);
            $table->string('queue', 255);
            $table->string('status', 30)->default('active');
            $table->timestamp('started_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('stopped_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_seen_at'], 'queue_worker_heartbeats_status_seen_idx');
            $table->index(['connection', 'queue', 'last_seen_at'], 'queue_worker_heartbeats_connection_queue_seen_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_worker_heartbeats');
        Schema::dropIfExists('queue_job_runs');
    }
};
