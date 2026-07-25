<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_site_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->text('about_us')->nullable();
            $table->text('benefits')->nullable();
            $table->text('culture')->nullable();
            $table->text('mission')->nullable();
            $table->json('social_links')->nullable();
            $table->string('recruitment_contact_email')->nullable();
            $table->string('recruitment_contact_phone')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->text('custom_footer')->nullable();
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('candidate_portal_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('portal_enabled')->default(true);
            $table->boolean('allow_guest_apply')->default(true);
            $table->boolean('require_login_to_apply')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('candidates', function (Blueprint $table) {
            if (! Schema::hasColumn('candidates', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            if (! Schema::hasColumn('candidates', 'state')) {
                $table->string('state')->nullable()->after('address');
            }
            if (! Schema::hasColumn('candidates', 'country')) {
                $table->string('country')->nullable()->after('address');
            }
            if (! Schema::hasColumn('candidates', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('address');
            }
            if (! Schema::hasColumn('candidates', 'education')) {
                $table->json('education')->nullable()->after('skills');
            }
            if (! Schema::hasColumn('candidates', 'work_experience')) {
                $table->json('work_experience')->nullable()->after('skills');
            }
            if (! Schema::hasColumn('candidates', 'languages')) {
                $table->json('languages')->nullable()->after('skills');
            }
            if (! Schema::hasColumn('candidates', 'certifications')) {
                $table->json('certifications')->nullable()->after('skills');
            }
            if (! Schema::hasColumn('candidates', 'github')) {
                $table->string('github')->nullable()->after('linkedin');
            }
            if (! Schema::hasColumn('candidates', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable()->after('linkedin');
            }
            if (! Schema::hasColumn('candidates', 'availability_date')) {
                $table->date('availability_date')->nullable()->after('notice_period');
            }
            if (! Schema::hasColumn('candidates', 'preferred_locations')) {
                $table->json('preferred_locations')->nullable()->after('notice_period');
            }
        });

        Schema::create('candidate_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('candidate_id');
            $table->string('email');
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'candidate_accounts_org_id_unique');
            $table->unique(['organization_id', 'email'], 'candidate_accounts_org_email_unique');
            $table->foreign(['organization_id', 'candidate_id'], 'candidate_accounts_org_candidate_fk')
                ->references(['organization_id', 'id'])
                ->on('candidates')
                ->cascadeOnDelete();
            $table->index(['organization_id', 'candidate_id'], 'candidate_accounts_org_candidate_idx');
        });

        Schema::create('candidate_password_reset_tokens', function (Blueprint $table) {
            $table->string('email');
            $table->foreignId('organization_id');
            $table->string('token');
            $table->timestamp('created_at')->nullable();

            $table->primary(['organization_id', 'email']);
        });

        Schema::create('candidate_resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('candidate_id');
            $table->string('name');
            $table->string('disk', 50);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'candidate_resumes_org_id_unique');
            $table->foreign(['organization_id', 'candidate_id'], 'candidate_resumes_org_candidate_fk')
                ->references(['organization_id', 'id'])
                ->on('candidates')
                ->cascadeOnDelete();
            $table->index(['organization_id', 'candidate_id'], 'candidate_resumes_org_candidate_idx');
        });

        Schema::create('candidate_saved_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('candidate_account_id');
            $table->unsignedBigInteger('job_opening_id');
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'candidate_saved_jobs_org_id_unique');
            $table->unique(
                ['organization_id', 'candidate_account_id', 'job_opening_id'],
                'candidate_saved_jobs_org_account_opening_unique',
            );
            $table->foreign(['organization_id', 'candidate_account_id'], 'candidate_saved_jobs_org_account_fk')
                ->references(['organization_id', 'id'])
                ->on('candidate_accounts')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'job_opening_id'], 'candidate_saved_jobs_org_opening_fk')
                ->references(['organization_id', 'id'])
                ->on('job_openings')
                ->cascadeOnDelete();
        });

        Schema::create('candidate_job_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('candidate_account_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('skills')->nullable();
            $table->string('location')->nullable();
            $table->string('employment_type', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'candidate_job_alerts_org_id_unique');
            $table->foreign(['organization_id', 'candidate_account_id'], 'candidate_job_alerts_org_account_fk')
                ->references(['organization_id', 'id'])
                ->on('candidate_accounts')
                ->cascadeOnDelete();
            $table->index(['organization_id', 'department_id'], 'candidate_job_alerts_org_department_idx');
            $table->index(['organization_id', 'candidate_account_id'], 'candidate_job_alerts_org_account_idx');
        });

        Schema::create('candidate_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('candidate_account_id');
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign(['organization_id', 'candidate_account_id'], 'candidate_notifications_org_account_fk')
                ->references(['organization_id', 'id'])
                ->on('candidate_accounts')
                ->cascadeOnDelete();
            $table->index(['organization_id', 'candidate_account_id', 'read_at'], 'candidate_notifications_org_account_read_idx');
        });

        Schema::table('job_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('job_applications', 'is_draft')) {
                $table->boolean('is_draft')->default(false)->after('status');
            }
            if (! Schema::hasColumn('job_applications', 'candidate_resume_id')) {
                $table->unsignedBigInteger('candidate_resume_id')->nullable()->after('status');
            }
            if (! Schema::hasColumn('job_applications', 'profile_snapshot')) {
                $table->json('profile_snapshot')->nullable()->after('status');
            }
            if (! Schema::hasColumn('job_applications', 'submission_type')) {
                $table->string('submission_type', 30)->default('internal')->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            if (Schema::hasColumn('job_applications', 'is_draft')) {
                $table->dropColumn(['is_draft', 'candidate_resume_id', 'profile_snapshot', 'submission_type']);
            }
        });

        Schema::dropIfExists('candidate_notifications');
        Schema::dropIfExists('candidate_job_alerts');
        Schema::dropIfExists('candidate_saved_jobs');
        Schema::dropIfExists('candidate_resumes');
        Schema::dropIfExists('candidate_password_reset_tokens');
        Schema::dropIfExists('candidate_accounts');
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn([
                'city', 'state', 'country', 'postal_code',
                'education', 'work_experience', 'languages', 'certifications',
                'github', 'profile_photo_path', 'availability_date', 'preferred_locations',
            ]);
        });
        Schema::dropIfExists('candidate_portal_settings');
        Schema::dropIfExists('career_site_settings');
    }
};
