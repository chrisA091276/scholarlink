<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AwardController;
use App\Http\Controllers\Api\ScholarshipAppealController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EligibilityController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ScholarshipController;
use App\Http\Controllers\Api\StudentProfileController;
use Illuminate\Support\Facades\Route;

/*
| ScholarLink REST API
|
| Laravel automatically prefixes this file with /api. The versioned routes
| therefore live at /api/v1. The unversioned group is retained as a small
| compatibility layer for existing local Postman collections.
*/
$registerRoutes = function (): void {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:6,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1');

    Route::get('/scholarships', [ScholarshipController::class, 'index']);
    Route::get('/scholarships/{scholarship}', [ScholarshipController::class, 'show']);
    Route::get('/scholarships/{scholarship}/requirements', [ScholarshipController::class, 'requirements']);
    Route::get('/announcements', [AnnouncementController::class, 'publicIndex']);

    Route::middleware(['auth:sanctum', \App\Http\Middleware\EnsureActiveAccount::class])->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/account', [AccountController::class, 'update']);
        Route::put('/account/password', [AccountController::class, 'updatePassword']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::get('/announcements/feed', [AnnouncementController::class, 'index']);

        Route::middleware('role:student')->group(function (): void {
            // Canonical student resource names.
            Route::get('/student/profile', [StudentProfileController::class, 'show']);
            Route::put('/student/profile', [StudentProfileController::class, 'update']);
            Route::get('/student/dashboard', [StudentProfileController::class, 'dashboard']);
            Route::get('/student/applications', [ApplicationController::class, 'myIndex']);
            Route::post('/student/applications', [ApplicationController::class, 'store']);
            Route::get('/student/applications/{application}', [ApplicationController::class, 'myShow']);
            Route::put('/student/applications/{application}', [ApplicationController::class, 'update']);
            Route::post('/student/applications/{application}/submit', [ApplicationController::class, 'submit']);
            Route::post('/student/applications/{application}/withdraw', [ApplicationController::class, 'withdraw']);
            Route::get('/student/applications/{application}/history', [ApplicationController::class, 'history']);
            Route::post('/student/applications/{application}/documents', [DocumentController::class, 'store']);
            Route::get('/student/applications/{application}/documents', [DocumentController::class, 'applicationDocuments']);
            Route::delete('/student/applications/{application}/documents/{document}', [DocumentController::class, 'destroy']);

            // Backward-compatible student names.
            Route::get('/profile', [StudentProfileController::class, 'show']);
            Route::put('/profile', [StudentProfileController::class, 'update']);
            Route::get('/my-applications', [ApplicationController::class, 'myIndex']);
            Route::post('/my-applications', [ApplicationController::class, 'store']);
            Route::get('/my-applications/{application}', [ApplicationController::class, 'myShow']);
            Route::post('/my-applications/{application}/submit', [ApplicationController::class, 'submit']);
            Route::post('/my-applications/{application}/withdraw', [ApplicationController::class, 'withdraw']);
            Route::post('/applications/{application}/documents', [DocumentController::class, 'store']);
            Route::delete('/applications/{application}/documents/{document}', [DocumentController::class, 'destroy']);
            Route::get('/my-documents', [DocumentController::class, 'myIndex']);
            Route::get('/my-awards', [AwardController::class, 'myAwards']);
            Route::get('/my-appeals', [ScholarshipAppealController::class, 'index']);
            Route::post('/awards/{award}/appeals', [ScholarshipAppealController::class, 'store']);
            Route::get('/my-renewals', [AwardController::class, 'myRenewals']);
            Route::post('/awards/{award}/renewals', [AwardController::class, 'storeRenewal']);
            Route::get('/eligibility-checklist', [EligibilityController::class, 'checklist']);
        });

        Route::get('/applications/{application}/documents/{document}/download', [DocumentController::class, 'download'])
            ->name('api.documents.download');

        Route::middleware('role:admin,staff')->prefix('staff')->group(function (): void {
            Route::get('/applications', [ApplicationController::class, 'index']);
            Route::get('/applications/{application}', [ApplicationController::class, 'show']);
            Route::post('/applications/{application}/verify-documents', [ApplicationController::class, 'verifyDocuments']);
            Route::post('/applications/{application}/eligibility', [EligibilityController::class, 'screen']);
            Route::post('/applications/{application}/evaluation', [ApplicationController::class, 'evaluate']);
            Route::post('/applications/{application}/recommendation', [ApplicationController::class, 'recommendation']);
            Route::post('/applications/{application}/request-correction', [ApplicationController::class, 'requestCorrection']);
        });

        // Existing staff/admin routes without the /staff resource prefix.
        Route::middleware('role:admin,staff')->group(function (): void {
            Route::get('/applications', [ApplicationController::class, 'index']);
            Route::get('/applications/{application}', [ApplicationController::class, 'show']);
            Route::post('/applications/{application}/screen', [EligibilityController::class, 'screen']);
            Route::get('/applications/{application}/eligibility', [EligibilityController::class, 'show']);
            Route::post('/applications/{application}/evaluate', [ApplicationController::class, 'evaluate']);
            Route::post('/applications/{application}/status', [ApplicationController::class, 'updateStatus']);
            Route::post('/applications/{application}/documents/{document}/verify', [DocumentController::class, 'verify']);
            Route::get('/verifications', [DocumentController::class, 'queue']);
            Route::get('/recommendations', [ApplicationController::class, 'recommendations']);
            Route::get('/awards', [AwardController::class, 'index']);
            Route::get('/awards/{award}', [AwardController::class, 'show']);
            Route::get('/renewals', [AwardController::class, 'indexRenewals']);
        });

        Route::middleware('role:admin')->group(function (): void {
            Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
            Route::get('/admin/users', [AdminController::class, 'users']);
            Route::get('/admin/applications', [ApplicationController::class, 'index']);
            Route::get('/admin/reports', [AdminController::class, 'reports']);
            Route::get('/admin/audit-logs', [AdminController::class, 'auditLogs']);

            Route::post('/scholarships', [ScholarshipController::class, 'store']);
            Route::put('/scholarships/{scholarship}', [ScholarshipController::class, 'update']);
            Route::delete('/scholarships/{scholarship}', [ScholarshipController::class, 'destroy']);
            Route::post('/scholarships/{scholarship}/publish', [ScholarshipController::class, 'publish']);
            Route::post('/scholarships/{scholarship}/requirements', [ScholarshipController::class, 'storeRequirement']);
            Route::delete('/scholarships/{scholarship}/requirements/{requirement}', [ScholarshipController::class, 'destroyRequirement']);
            Route::post('/scholarships/{scholarship}/rules', [ScholarshipController::class, 'storeRule']);
            Route::delete('/scholarships/{scholarship}/rules/{rule}', [ScholarshipController::class, 'destroyRule']);

            Route::post('/applications/{application}/assign', [ApplicationController::class, 'assign']);
            Route::post('/applications/{application}/decision', [ApplicationController::class, 'updateStatus']);
            Route::get('/evaluations', [ApplicationController::class, 'evaluations']);
            Route::post('/awards/{award}/status', [AwardController::class, 'updateStatus']);
            Route::get('/appeals', [ScholarshipAppealController::class, 'index']);
            Route::post('/appeals/{appeal}/decide', [ScholarshipAppealController::class, 'decide']);
            Route::post('/renewals/{renewal}/decide', [AwardController::class, 'decideRenewal']);

            // Explicit admin namespace required by the public API contract.
            Route::prefix('admin')->group(function (): void {
                Route::post('/scholarships', [ScholarshipController::class, 'store']);
                Route::put('/scholarships/{scholarship}', [ScholarshipController::class, 'update']);
                Route::delete('/scholarships/{scholarship}', [ScholarshipController::class, 'destroy']);
                Route::post('/scholarships/{scholarship}/publish', [ScholarshipController::class, 'publish']);
                Route::get('/scholarships/{scholarship}/requirements', [ScholarshipController::class, 'requirements']);
                Route::post('/scholarships/{scholarship}/requirements', [ScholarshipController::class, 'storeRequirement']);
                Route::delete('/scholarships/{scholarship}/requirements/{requirement}', [ScholarshipController::class, 'destroyRequirement']);
                Route::post('/scholarships/{scholarship}/rules', [ScholarshipController::class, 'storeRule']);
                Route::delete('/scholarships/{scholarship}/rules/{rule}', [ScholarshipController::class, 'destroyRule']);
                Route::post('/applications/{application}/decision', [ApplicationController::class, 'updateStatus']);
            });
        });
    });
};

// Legacy aliases first; the versioned API is canonical and appears last.
Route::group([], $registerRoutes);
Route::prefix('v1')->group($registerRoutes);
