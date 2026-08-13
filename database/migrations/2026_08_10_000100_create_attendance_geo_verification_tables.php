<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_records', 'clock_in_latitude')) {
                $table->decimal('clock_in_latitude', 10, 7)->nullable()->after('source');
            }
            if (! Schema::hasColumn('attendance_records', 'clock_in_longitude')) {
                $table->decimal('clock_in_longitude', 10, 7)->nullable()->after('clock_in_latitude');
            }
            if (! Schema::hasColumn('attendance_records', 'clock_in_accuracy_meters')) {
                $table->unsignedInteger('clock_in_accuracy_meters')->nullable()->after('clock_in_longitude');
            }
            if (! Schema::hasColumn('attendance_records', 'clock_in_device_id')) {
                $table->string('clock_in_device_id', 191)->nullable()->after('clock_in_accuracy_meters');
            }
            if (! Schema::hasColumn('attendance_records', 'clock_in_geofence_id')) {
                $table->unsignedBigInteger('clock_in_geofence_id')->nullable()->after('clock_in_device_id');
            }
            if (! Schema::hasColumn('attendance_records', 'clock_in_verification_status')) {
                $table->string('clock_in_verification_status', 40)->nullable()->after('clock_in_geofence_id');
            }
            if (! Schema::hasColumn('attendance_records', 'clock_in_verification_metadata')) {
                $table->json('clock_in_verification_metadata')->nullable()->after('clock_in_verification_status');
            }
            if (! Schema::hasColumn('attendance_records', 'clock_out_latitude')) {
                $table->decimal('clock_out_latitude', 10, 7)->nullable()->after('clock_in_verification_metadata');
            }
            if (! Schema::hasColumn('attendance_records', 'clock_out_longitude')) {
                $table->decimal('clock_out_longitude', 10, 7)->nullable()->after('clock_out_latitude');
            }
            if (! Schema::hasColumn('attendance_records', 'clock_out_accuracy_meters')) {
                $table->unsignedInteger('clock_out_accuracy_meters')->nullable()->after('clock_out_longitude');
            }
            if (! Schema::hasColumn('attendance_records', 'clock_out_device_id')) {
                $table->string('clock_out_device_id', 191)->nullable()->after('clock_out_accuracy_meters');
            }
            if (! Schema::hasColumn('attendance_records', 'clock_out_geofence_id')) {
                $table->unsignedBigInteger('clock_out_geofence_id')->nullable()->after('clock_out_device_id');
            }
            if (! Schema::hasColumn('attendance_records', 'clock_out_verification_status')) {
                $table->string('clock_out_verification_status', 40)->nullable()->after('clock_out_geofence_id');
            }
            if (! Schema::hasColumn('attendance_records', 'clock_out_verification_metadata')) {
                $table->json('clock_out_verification_metadata')->nullable()->after('clock_out_verification_status');
            }
        });

        if (! Schema::hasTable('attendance_geofences')) {
            Schema::create('attendance_geofences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('name');
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->unsignedInteger('radius_meters');
                $table->boolean('is_active')->default(true);
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->timestamps();

                $table->unique(['organization_id', 'id'], 'attendance_geofences_org_id_unique');
                $table->foreign('branch_id', 'attendance_geofences_branch_fk')
                    ->references('id')->on('hrms_branches')->nullOnDelete();
                $table->index(['organization_id', 'branch_id', 'is_active'], 'attendance_geofences_org_branch_active_idx');
                $table->index(['organization_id', 'effective_from', 'effective_to'], 'attendance_geofences_org_effective_idx');
            });
        }

        if (Schema::hasTable('attendance_geofences') && Schema::hasTable('attendance_records')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                if (! $this->foreignKeyExists('attendance_records', 'ar_clock_in_geofence_fk')) {
                    $table->foreign('clock_in_geofence_id', 'ar_clock_in_geofence_fk')
                        ->references('id')->on('attendance_geofences')->nullOnDelete();
                }
                if (! $this->foreignKeyExists('attendance_records', 'ar_clock_out_geofence_fk')) {
                    $table->foreign('clock_out_geofence_id', 'ar_clock_out_geofence_fk')
                        ->references('id')->on('attendance_geofences')->nullOnDelete();
                }
            });
        }

        if (! Schema::hasTable('attendance_verification_audits')) {
            Schema::create('attendance_verification_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('attendance_record_id')->nullable();
                $table->unsignedBigInteger('employee_id');
                $table->string('event', 30);
                $table->string('verification_mode', 40);
                $table->string('verification_status', 40);
                $table->string('reason', 255)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->unsignedInteger('accuracy_meters')->nullable();
                $table->string('device_id', 191)->nullable();
                $table->unsignedBigInteger('geofence_id')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->useCurrent();
                $table->timestamps();

                $table->unique(['organization_id', 'id'], 'ava_org_id_unique');
                $table->foreign('employee_id', 'ava_employee_fk')
                    ->references('id')->on('employees')->cascadeOnDelete();
                $table->foreign('attendance_record_id', 'ava_record_fk')
                    ->references('id')->on('attendance_records')->nullOnDelete();
                $table->foreign('geofence_id', 'ava_geofence_fk')
                    ->references('id')->on('attendance_geofences')->nullOnDelete();
                $table->index(['organization_id', 'employee_id', 'verified_at'], 'ava_org_employee_verified_idx');
                $table->index(['organization_id', 'attendance_record_id'], 'ava_org_record_idx');
                $table->index(['organization_id', 'verification_status'], 'ava_org_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_verification_audits');

        if (Schema::hasTable('attendance_records')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                foreach (['ar_clock_in_geofence_fk', 'ar_clock_out_geofence_fk'] as $fk) {
                    if ($this->foreignKeyExists('attendance_records', $fk)) {
                        $table->dropForeign($fk);
                    }
                }

                $columns = [
                    'clock_in_latitude',
                    'clock_in_longitude',
                    'clock_in_accuracy_meters',
                    'clock_in_device_id',
                    'clock_in_geofence_id',
                    'clock_in_verification_status',
                    'clock_in_verification_metadata',
                    'clock_out_latitude',
                    'clock_out_longitude',
                    'clock_out_accuracy_meters',
                    'clock_out_device_id',
                    'clock_out_geofence_id',
                    'clock_out_verification_status',
                    'clock_out_verification_metadata',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('attendance_records', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('attendance_geofences');
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        $database = Schema::getConnection()->getDatabaseName();
        $result = Schema::getConnection()->selectOne(
            'select constraint_name from information_schema.table_constraints
             where table_schema = ? and table_name = ? and constraint_name = ? and constraint_type = ?',
            [$database, $table, $name, 'FOREIGN KEY']
        );

        return $result !== null;
    }
};
