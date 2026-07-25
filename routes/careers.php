<?php

use App\Http\Controllers\Careers\CandidateApplicationController;
use App\Http\Controllers\Careers\CandidateAuthController;
use App\Http\Controllers\Careers\CandidateDashboardController;
use App\Http\Controllers\Careers\CandidateJobAlertController;
use App\Http\Controllers\Careers\CandidateOfferController;
use App\Http\Controllers\Careers\CandidateProfileController;
use App\Http\Controllers\Careers\CandidateResumeController;
use App\Http\Controllers\Careers\CandidateSavedJobController;
use App\Http\Controllers\Careers\CareerJobController;
use App\Http\Controllers\Careers\CareerSiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('{organization:slug}/careers')
    ->middleware(['careers.organization'])
    ->name('careers.')
    ->group(function () {
        Route::get('/', [CareerSiteController::class, 'index'])->name('home');
        Route::get('/jobs/{job_opening}', [CareerJobController::class, 'show'])->name('jobs.show');

        Route::middleware('guest:candidate')->group(function () {
            Route::get('/login', [CandidateAuthController::class, 'createLogin'])->name('login');
            Route::post('/login', [CandidateAuthController::class, 'login'])->middleware('throttle:candidate-auth');
            Route::get('/register', [CandidateAuthController::class, 'createRegister'])->name('register');
            Route::post('/register', [CandidateAuthController::class, 'register'])->middleware('throttle:candidate-auth');
            Route::get('/forgot-password', [CandidateAuthController::class, 'createForgotPassword'])->name('password.request');
            Route::post('/forgot-password', [CandidateAuthController::class, 'forgotPassword'])->middleware('throttle:candidate-auth');
            Route::get('/reset-password/{token}', [CandidateAuthController::class, 'createResetPassword'])->name('password.reset');
            Route::post('/reset-password', [CandidateAuthController::class, 'resetPassword'])->middleware('throttle:candidate-auth')->name('password.store');
        });

        Route::post('/jobs/{job_opening}/apply/guest', [CandidateApplicationController::class, 'guestApply'])
            ->middleware('throttle:careers-apply')
            ->name('jobs.apply.guest');

        Route::middleware(['auth:candidate', 'careers.candidate'])->group(function () {
            Route::post('/logout', [CandidateAuthController::class, 'logout'])->name('logout');
            Route::get('/dashboard', [CandidateDashboardController::class, 'index'])->name('dashboard');
            Route::get('/profile', [CandidateProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [CandidateProfileController::class, 'update'])->name('profile.update');
            Route::get('/resumes', [CandidateResumeController::class, 'index'])->name('resumes.index');
            Route::post('/resumes', [CandidateResumeController::class, 'store'])->name('resumes.store');
            Route::post('/resumes/{candidate_resume}/default', [CandidateResumeController::class, 'setDefault'])->name('resumes.default');
            Route::delete('/resumes/{candidate_resume}', [CandidateResumeController::class, 'destroy'])->name('resumes.destroy');
            Route::get('/applications', [CandidateApplicationController::class, 'index'])->name('applications.index');
            Route::get('/applications/{job_application}', [CandidateApplicationController::class, 'show'])->name('applications.show');
            Route::post('/jobs/{job_opening}/apply', [CandidateApplicationController::class, 'apply'])->name('jobs.apply');
            Route::post('/applications/{job_application}/submit', [CandidateApplicationController::class, 'submitDraft'])->name('applications.submit');
            Route::post('/applications/{job_application}/withdraw', [CandidateApplicationController::class, 'withdraw'])->name('applications.withdraw');
            Route::post('/applications/{job_application}/resume', [CandidateApplicationController::class, 'updateResume'])->name('applications.resume');
            Route::get('/saved-jobs', [CandidateSavedJobController::class, 'index'])->name('saved-jobs.index');
            Route::post('/saved-jobs/{job_opening}', [CandidateSavedJobController::class, 'store'])->name('saved-jobs.store');
            Route::delete('/saved-jobs/{job_opening}', [CandidateSavedJobController::class, 'destroy'])->name('saved-jobs.destroy');
            Route::get('/job-alerts', [CandidateJobAlertController::class, 'index'])->name('job-alerts.index');
            Route::post('/job-alerts', [CandidateJobAlertController::class, 'store'])->name('job-alerts.store');
            Route::put('/job-alerts/{candidate_job_alert}', [CandidateJobAlertController::class, 'update'])->name('job-alerts.update');
            Route::delete('/job-alerts/{candidate_job_alert}', [CandidateJobAlertController::class, 'destroy'])->name('job-alerts.destroy');
            Route::get('/offers', [CandidateOfferController::class, 'index'])->name('offers.index');
        });
    });
