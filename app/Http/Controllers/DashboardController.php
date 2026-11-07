<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipAward;
use App\Models\ScholarshipProgram;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return match ($user->role) {
            'admin' => $this->admin(),
            'staff' => $this->staff(),
            default => $this->student(),
        };
    }

    private function admin()
    {
        $stats = [
            'students' => User::where('role', 'student')->count(),
            'programs' => ScholarshipProgram::count(),
            'applications' => ScholarshipApplication::count(),
            'pending' => ScholarshipApplication::whereIn('status', ['submitted', 'under_review', 'for_evaluation'])->count(),
            'approved' => ScholarshipApplication::where('status', 'approved')->count(),
            'rejected' => ScholarshipApplication::where('status', 'rejected')->count(),
            'recipients' => ScholarshipAward::where('recipient_status', 'active')->count(),
            'renewals' => \App\Models\RenewalApplication::count(),
            'unassigned' => ScholarshipApplication::whereNull('assigned_to')->whereIn('status', ScholarshipApplication::REVIEW_STATUSES)->count(),
            'decisions' => ScholarshipApplication::whereIn('status', ScholarshipApplication::DECISION_STATUSES)->count(),
            'other_assistance' => ScholarshipApplication::where('adfc_form->other_assistance', 'yes')->whereIn('status', ScholarshipApplication::ACTIVE_STATUSES)->count(),
            'docs_pending' => \App\Models\ApplicationDocument::where('verification_status','pending')->whereHas('application', fn ($q) => $q->whereIn('status', ScholarshipApplication::REVIEW_STATUSES))->count(),
        ];
        $byStatus = ScholarshipApplication::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $upcoming = ScholarshipProgram::where('status', 'published')->whereDate('deadline', '>=', today())->orderBy('deadline')->limit(5)->get();
        $recent = ScholarshipApplication::with(['student', 'program.requirements','assignee','documents'])->whereIn('status', ScholarshipApplication::ACTIVE_STATUSES)->orderByRaw("CASE WHEN status IN ('recommended_approval','recommended_rejection') THEN 0 WHEN assigned_to IS NULL THEN 1 ELSE 2 END")->oldest('submitted_at')->limit(8)->get();
        $announcements = Announcement::where('is_published', true)->latest()->limit(5)->get();
        $activity = \App\Models\AuditLog::with('user')->latest()->limit(8)->get();
        $renewalByStatus = \App\Models\RenewalApplication::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $pendingRenewals = \App\Models\RenewalApplication::with(['student', 'award.program'])->whereIn('status', ['submitted', 'under_review'])->latest()->limit(5)->get();
        $appsPerProgram = ScholarshipProgram::withCount('applications')->orderByDesc('applications_count')->limit(5)->get();
        $recentUsers = User::latest()->limit(5)->get();

        return view('dashboards.admin', compact('stats', 'byStatus', 'upcoming', 'recent', 'announcements', 'activity', 'renewalByStatus', 'pendingRenewals', 'appsPerProgram', 'recentUsers'));
    }

    private function staff()
    {
        $assigned = ScholarshipApplication::where('assigned_to', Auth::id());
        $stats = [
            'assigned' => (clone $assigned)->whereIn('status', ScholarshipApplication::REVIEW_STATUSES)->count(),
            'awaiting' => (clone $assigned)->where('status', 'submitted')->count(),
            'docs_pending' => \App\Models\ApplicationDocument::where('verification_status', 'pending')->whereHas('application', fn ($q) => $q->where('assigned_to', Auth::id())->whereIn('status', ScholarshipApplication::REVIEW_STATUSES))->count(),
            'corrections' => (clone $assigned)->where('status', 'incomplete_requirements')->count(),
            'evaluating' => (clone $assigned)->where('status','for_evaluation')->count(),
            'other_assistance' => (clone $assigned)->where('adfc_form->other_assistance','yes')->whereIn('status', ScholarshipApplication::REVIEW_STATUSES)->count(),
        ];
        $queue = ScholarshipApplication::with(['student', 'program.requirements','documents'])->where('assigned_to', Auth::id())->whereIn('status', ScholarshipApplication::REVIEW_STATUSES)->oldest('submitted_at')->limit(10)->get();
        $upcoming = ScholarshipProgram::where('status', 'published')->whereDate('deadline', '>=', today())->orderBy('deadline')->limit(5)->get();

        return view('dashboards.staff', compact('stats', 'queue', 'upcoming'));
    }

    private function student()
    {
        $user = Auth::user();
        $profile = $user->studentProfile;
        $completion = $profile ? $this->profileCompletion($profile) : 0;
        $studentApplications = ScholarshipApplication::where('user_id', $user->id);
        $applicationStats = [
            'total' => (clone $studentApplications)->count(),
            'drafts' => (clone $studentApplications)->where('status', 'draft')->count(),
            'in_review' => (clone $studentApplications)->whereIn('status', ['submitted', 'under_review', 'for_evaluation', 'recommended_approval', 'recommended_rejection'])->count(),
            'approved' => (clone $studentApplications)->where('status', 'approved')->count(),
        ];
        $applications = ScholarshipApplication::with('program')->where('user_id', $user->id)->latest('updated_at')->limit(5)->get();
        $activeApplication = ScholarshipApplication::with(['program.requirements','documents'])
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['rejected', 'withdrawn'])
            ->orderByRaw("CASE WHEN status = 'incomplete_requirements' THEN 0 WHEN status = 'draft' THEN 1 WHEN status = 'approved' THEN 3 ELSE 2 END")->latest('updated_at')
            ->first();
        $available = ScholarshipProgram::where('status', 'published')->where(fn ($q) => $q->whereNull('opening_date')->orWhereDate('opening_date','<=',today()))->where(fn ($q) => $q->whereNull('deadline')->orWhereDate('deadline','>=',today()))->orderBy('deadline')->limit(6)->get();
        $formSections = app(\App\Services\AdfcApplicationForm::class)->progress($activeApplication?->adfc_form ?? app(\App\Services\AdfcApplicationForm::class)->defaults($user));
        $formMissing = $activeApplication ? app(\App\Services\ApplicationSubmissionService::class)->missingRequirements($activeApplication) : [];
        $formDocuments = app(\App\Services\AdfcApplicationForm::class)->documentChecklist($activeApplication);
        $unreadCount = $user->notifications()->where('is_read', false)->count();
        $missingDocs = \App\Models\ApplicationDocument::with('application.program')->whereHas('application', fn ($q) => $q->where('user_id', $user->id)->where('status', 'incomplete_requirements'))
            ->whereIn('verification_status', ['rejected', 'requires_correction'])->latest()->limit(5)->get();
        $awards = ScholarshipAward::with('program')->where('user_id', $user->id)->latest()->get();
        $notifications = $user->notifications()->latest()->limit(5)->get();
        $announcements = Announcement::where('is_published', true)
            ->whereIn('audience', ['all', 'students'])->latest()->limit(5)->get();

        return view('dashboards.student', compact(
            'profile', 'completion', 'applications', 'applicationStats', 'activeApplication',
            'available', 'missingDocs', 'awards', 'notifications', 'announcements', 'unreadCount', 'formSections', 'formMissing', 'formDocuments'
        ));
    }

    private function profileCompletion($profile): int
    {
        $fields = ['student_id_no', 'first_name', 'last_name', 'contact_number', 'address', 'course', 'year_level', 'date_of_birth'];
        $filled = collect($fields)->filter(fn ($f) => ! empty($profile->$f))->count();
        return (int) round($filled / count($fields) * 100);
    }
}
