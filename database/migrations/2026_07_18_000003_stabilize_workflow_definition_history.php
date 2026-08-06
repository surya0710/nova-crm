<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('workflow_conditions', 'workflow_version')) {
            Schema::table('workflow_conditions', function (Blueprint $table): void {
                $table->unsignedInteger('workflow_version')->default(1)->after('workflow_id');
                $table->softDeletes();
                $table->index(
                    ['organization_id', 'workflow_id', 'workflow_version'],
                    'workflow_conditions_version_idx',
                );
            });
        }

        if (! Schema::hasColumn('workflow_actions', 'workflow_version')) {
            Schema::table('workflow_actions', function (Blueprint $table): void {
                $table->unsignedInteger('workflow_version')->default(1)->after('workflow_id');
                $table->softDeletes();
                $table->index(
                    ['organization_id', 'workflow_id', 'workflow_version'],
                    'workflow_actions_version_idx',
                );
            });
        }
    }

    public function down(): void
    {
        // Historical workflow definitions are intentionally retained.
    }
};
