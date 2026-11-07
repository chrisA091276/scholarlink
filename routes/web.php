<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\ScholarshipAppealController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EligibilityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScholarshipProgramController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Landing ──
Route::get('/', function () {
    $programs = \App\Models\ScholarshipProgram::where('status', 'published')->orderBy('deadline')->limit(6)->get();
    $announcements = \App\Models\Announcement::where('is_published', true)->where('audience', 'all')->latest()->limit(3)->get();
    $stats = [
        'programs' => \App\Models\ScholarshipProgram::where('status', 'published')->count(),
        'slots' => (int) \App\Models\ScholarshipProgram::where('status', 'published')->sum('slots'),
        'recipients' => \App\Models\ScholarshipAward::where('recipient_status', 'active')->count(),
        'applications' => \App\Models\ScholarshipApplication::count(),
    ];
    return view('landing', compact('programs', 'announcements', 'stats'));
})->name('landing');

// ── Guest: auth (rate-limited) ──
Route::middleware('guest')->group(function () {
    Route::get('/login/student/verify', [\App\Http\Controllers\StudentLoginController::class, 'show'])->name('login.student.verify');
    Route::post('/login/student/verify', [\App\Http\Controllers\StudentLoginController::class, 'verify'])->middleware('throttle:10,1')->name('login.student.verify.submit');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/login/{role}', [AuthController::class, 'showLogin'])
        ->whereIn('role', \App\Models\User::ROLES)->name('login.role');
    Route::post('/login/{role}', [AuthController::class, 'login'])
        ->whereIn('role', \App\Models\User::ROLES)->middleware('throttle:10,1')->name('login.role.submit');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

// Forgot / reset password (log mailer in dev, SMTP in prod)
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', function () { return view('auth.forgot-password'); })->name('password.request');
    Route::post('/forgot-password', function (\Illuminate\Http\Request $request) {
        $request->validate(['email' => 'required|email']);
        $status = \Illuminate\Support\Facades\Password::sendResetLink($request->only('email'));
        return back()->with('success', 'If this account exists, a reset link has been requested. Delivery depends on the configured mail service.');
    })->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', function (string $token) {
        return view('auth.reset-password', ['token' => $token, 'email' => request('email')]);
    })->name('password.reset');
    Route::post('/reset-password', function (\App\Http\Requests\ResetPasswordRequest $request) {
        $status = \Illuminate\Support\Facades\Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) { $user->forceFill(['password' => \Illuminate\Support\Facades\Hash::make($request->password), 'remember_token' => \Illuminate\Support\Str::random(60)])->save(); $user->tokens()->delete(); \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete(); }
        );
        return $status === \Illuminate\Support\Facades\Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password reset. Please log in.')
            : back()->withErrors(['email' => __($status)]);
    })->middleware('throttle:6,1')->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ── Public scholarship browsing (login required only to apply) ──
Route::get('/scholarships', [ScholarshipProgramController::class, 'publicIndex'])->name('scholarships.public');
Route::get('/scholarships/{scholarship}', [ScholarshipProgramController::class, 'publicShow'])->name('scholarships.view');

// ── Authenticated ──
Route::middleware(['auth', \App\Http\Middleware\EnsureActiveAccount::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Notifications + announcements (all roles)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/announcements-view', [NotificationController::class, 'announcements'])->name('announcements.view');

    // Account settings (all roles)
    Route::get('/account', [\App\Http\Controllers\AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account', [\App\Http\Controllers\AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [\App\Http\Controllers\AccountController::class, 'updatePassword'])->name('account.password');

    // ── Student ──
    Route::middleware('role:student')->group(function () {
        Route::get('/profile', [StudentProfileController::class, 'show'])->name('profile.show');
        Route::get('/profile/edit', [StudentProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [StudentProfileController::class, 'update'])->name('profile.update');

        Route::get('/my-applications', [ApplicationController::class, 'myIndex'])->name('my-applications.index');
        Route::get('/my-applications/create', [ApplicationController::class, 'create'])->name('my-applications.create');
        Route::post('/my-applications', [ApplicationController::class, 'store'])->name('my-applications.store');
        Route::get('/my-applications/{application}/edit', [ApplicationController::class, 'edit'])->name('my-applications.edit');
        Route::put('/my-applications/{application}', [ApplicationController::class, 'update'])->name('my-applications.update');
        Route::get('/my-applications/{application}/download', [ApplicationController::class, 'downloadSummary'])->name('my-applications.download');
        Route::get('/my-applications/{application}', [ApplicationController::class, 'myShow'])->name('my-applications.show');
        Route::post('/my-applications/{application}/submit', [ApplicationController::class, 'submitDraft'])->name('my-applications.submit');
        Route::post('/my-applications/{application}/withdraw', [ApplicationController::class, 'withdraw'])->name('my-applications.withdraw');

        Route::post('/applications/{application}/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::delete('/applications/{application}/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

        Route::get('/my-awards', [AwardController::class, 'myAwards'])->name('my-awards');
        Route::get('/my-awards/{award}/appeal', [ScholarshipAppealController::class, 'create'])->name('my-appeals.create');
        Route::post('/my-awards/{award}/appeal', [ScholarshipAppealController::class, 'store'])->name('my-appeals.store');
        Route::get('/awards/{award}/renew', [AwardController::class, 'createRenewal'])->name('renewals.create');
        Route::post('/awards/{award}/renew', [AwardController::class, 'storeRenewal'])->name('renewals.store');
        Route::get('/my-documents', [DocumentController::class, 'myDocuments'])->name('documents.my');
        Route::get('/eligibility-checklist', [EligibilityController::class, 'checklist'])->name('eligibility.checklist');
        Route::get('/my-renewals', [AwardController::class, 'myRenewals'])->name('my-renewals.index');
    });

    // Document download (owner + staff/admin)
    Route::get('/applications/{application}/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    // ── Staff + Admin ──
    Route::middleware('role:admin,staff')->group(function () {
        Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
        Route::get('/applications/{application}/download', [ApplicationController::class, 'downloadSummary'])->name('applications.download');
        Route::post('/applications/{application}/form-review', [ApplicationController::class, 'reviewForm'])->name('applications.form-review');
        Route::post('/applications/{application}/status', [ApplicationController::class, 'updateStatus'])->name('applications.status');
        Route::post('/applications/{application}/evaluate', [ApplicationController::class, 'storeEvaluation'])->name('applications.evaluate');
        Route::post('/applications/{application}/screen', [EligibilityController::class, 'screen'])->name('applications.screen');
        Route::get('/applications/{application}/eligibility', [EligibilityController::class, 'show'])->name('eligibility.show');
        Route::post('/applications/{application}/documents/{document}/verify', [DocumentController::class, 'verify'])->name('documents.verify');

        Route::get('/awards', [AwardController::class, 'index'])->name('awards.index');
        Route::get('/awards/{award}', [AwardController::class, 'show'])->name('awards.show');
        Route::get('/renewals', [AwardController::class, 'renewals'])->name('renewals.index');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/recipients', [ReportController::class, 'recipients'])->name('reports.recipients');

        Route::get('/student-records', [\App\Http\Controllers\StudentRecordController::class, 'index'])->name('students.index');
        Route::get('/student-records/{student}', [\App\Http\Controllers\StudentRecordController::class, 'show'])->name('students.show');
        Route::get('/verifications', [DocumentController::class, 'queue'])->name('verifications.index');
        Route::get('/screening', [EligibilityController::class, 'index'])->name('screening.index');
        Route::get('/recommendations', [ApplicationController::class, 'recommendations'])->name('recommendations.index');
    });

    // ── Admin only ──
    Route::middleware('role:admin')->group(function () {
        Route::get('/manage-scholarships', [ScholarshipProgramController::class, 'index'])->name('scholarships.index');
        Route::get('/manage-scholarships/create', [ScholarshipProgramController::class, 'create'])->name('scholarships.create');
        Route::post('/manage-scholarships', [ScholarshipProgramController::class, 'store'])->name('scholarships.store');
        Route::get('/manage-scholarships/{scholarship}/manage', [ScholarshipProgramController::class, 'show'])->name('scholarships.show');
        Route::get('/manage-scholarships/{scholarship}/edit', [ScholarshipProgramController::class, 'edit'])->name('scholarships.edit');
        Route::put('/manage-scholarships/{scholarship}', [ScholarshipProgramController::class, 'update'])->name('scholarships.update');
        Route::delete('/manage-scholarships/{scholarship}', [ScholarshipProgramController::class, 'destroy'])->name('scholarships.destroy');
        Route::post('/manage-scholarships/{scholarship}/requirements', [ScholarshipProgramController::class, 'storeRequirement'])->name('scholarships.requirements.store');
        Route::delete('/manage-scholarships/{scholarship}/requirements/{requirementId}', [ScholarshipProgramController::class, 'destroyRequirement'])->name('scholarships.requirements.destroy');
        Route::post('/manage-scholarships/{scholarship}/rules', [ScholarshipProgramController::class, 'storeRule'])->name('scholarships.rules.store');
        Route::delete('/manage-scholarships/{scholarship}/rules/{ruleId}', [ScholarshipProgramController::class, 'destroyRule'])->name('scholarships.rules.destroy');
        Route::get('/requirements-manage', [ScholarshipProgramController::class, 'requirementsIndex'])->name('requirements.manage');
        Route::get('/eligibility-criteria', [ScholarshipProgramController::class, 'eligibilityIndex'])->name('eligibility.manage');
        Route::get('/evaluations', [ApplicationController::class, 'evaluationsIndex'])->name('evaluations.index');
        Route::post('/applications/{application}/assign', [ApplicationController::class, 'assign'])->name('applications.assign');

        Route::post('/awards/{award}/status', [AwardController::class, 'updateStatus'])->name('awards.status');
        Route::get('/appeals', [ScholarshipAppealController::class, 'index'])->name('appeals.index');
        Route::get('/appeals/{appeal}', [ScholarshipAppealController::class, 'show'])->name('appeals.show');
        Route::post('/appeals/{appeal}/decide', [ScholarshipAppealController::class, 'decide'])->name('appeals.decide');
        Route::post('/renewals/{renewal}/decide', [AwardController::class, 'decideRenewal'])->name('renewals.decide');

        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::post('/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');
        Route::post('/users/{user}/reset-link', [UserController::class, 'sendResetLink'])->name('users.reset-link');

        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('/announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});

Route::middleware(['auth', \App\Http\Middleware\EnsureActiveAccount::class])->group(function () {
    Route::get('/email/verify', [\App\Http\Controllers\EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [\App\Http\Controllers\EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');
});
Route::view('/privacy', 'privacy')->name('privacy');
