<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipAward;
use App\Models\ScholarshipProgram;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(): array
    {
        return [
            'data' => [
                'students' => User::where('role', 'student')->count(),
                'active_programs' => ScholarshipProgram::where('status', 'published')->count(),
                'applications' => ScholarshipApplication::count(),
                'awaiting_review' => ScholarshipApplication::whereIn('status', ['submitted', 'under_review', 'for_evaluation'])->count(),
                'requiring_corrections' => ScholarshipApplication::where('status', 'incomplete_requirements')->count(),
                'approved' => ScholarshipApplication::where('status', 'approved')->count(),
                'rejected' => ScholarshipApplication::where('status', 'rejected')->count(),
                'active_scholars' => ScholarshipAward::where('recipient_status', 'active')->count(),
                'documents_pending' => ApplicationDocument::where('verification_status', 'pending')
                    ->whereHas('application', fn ($q) => $q->whereIn('status', ScholarshipApplication::REVIEW_STATUSES))->count(),
                'awaiting_assignment' => ScholarshipApplication::whereNull('assigned_to')->whereIn('status', ScholarshipApplication::REVIEW_STATUSES)->count(),
                'awaiting_decision' => ScholarshipApplication::whereIn('status', ScholarshipApplication::DECISION_STATUSES)->count(),
                'by_status' => ScholarshipApplication::query()
                    ->selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')
                    ->orderBy('status')
                    ->get(),
                'by_program' => ScholarshipProgram::withCount('applications')
                    ->orderByDesc('applications_count')
                    ->limit(10)
                    ->get(['id', 'code', 'name']),
            ],
        ];
    }

    public function users(Request $request)
    {
        $query = User::query()->latest();
        if ($request->filled('role')) $query->where('role', $request->string('role'));
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        return UserResource::collection($query->paginate(min(max($request->integer('per_page', 20), 1), 50)));
    }

    public function reports(Request $request)
    {
        $request->validate(['from' => ['nullable','date'], 'to' => ['nullable','date','after_or_equal:from']]);
        $from = $request->date('from')?->startOfDay();
        $to = $request->date('to')?->endOfDay();
        $query = ScholarshipApplication::query();
        if ($from) $query->where('scholarship_applications.submitted_at', '>=', $from);
        if ($to) $query->where('scholarship_applications.submitted_at', '<=', $to);

        return response()->json([
            'data' => [
                'period' => ['from' => $from?->toDateString(), 'to' => $to?->toDateString()],
                'total_applications' => (clone $query)->count(),
                'by_status' => (clone $query)->selectRaw('status, COUNT(*) as total')->groupBy('status')->get(),
                'by_program' => (clone $query)->join('scholarship_programs', 'scholarship_programs.id', '=', 'scholarship_applications.scholarship_program_id')
                    ->selectRaw('scholarship_programs.id, scholarship_programs.code, scholarship_programs.name, COUNT(scholarship_applications.id) as total')
                    ->groupBy('scholarship_programs.id', 'scholarship_programs.code', 'scholarship_programs.name')
                    ->orderByDesc('total')->get(),
                'active_awards' => ScholarshipAward::where('recipient_status', 'active')->count(),
            ],
        ]);
    }

    public function auditLogs(Request $request)
    {
        $query = AuditLog::with('user')->latest();
        if ($request->filled('action')) $query->where('action', $request->string('action'));
        return response()->json([
            'data' => $query->paginate(min(max($request->integer('per_page', 30), 1), 100)),
        ]);
    }
}
