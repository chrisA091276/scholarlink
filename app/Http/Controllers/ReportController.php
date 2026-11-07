<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EligibilityResult;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipAward;
use App\Models\ScholarshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'applications');
        if (! in_array($tab, ['applications', 'students', 'programs', 'eligibility'], true)) $tab = 'applications';
        $programs = ScholarshipProgram::orderBy('name')->get();

        return match ($tab) {
            'students' => $this->students($request, $programs),
            'programs' => $this->programsReport($request, $programs),
            'eligibility' => $this->eligibility($request, $programs),
            default => $this->applications($request, $programs),
        };
    }

    // ── Applications tab (existing) ──
    private function applications(Request $request, $programs)
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $from = $request->get('from');
        $to = $request->get('to');
        $programId = $request->get('scholarship_id');
        $status = $request->get('status');

        $apps = ScholarshipApplication::with(['student', 'program']);
        if (auth()->user()->isStaff()) $apps->where('assigned_to', auth()->id());
        if ($from) $apps->whereDate('submitted_at', '>=', $from);
        if ($to) $apps->whereDate('submitted_at', '<=', $to);
        if ($programId) $apps->where('scholarship_program_id', $programId);
        if ($status) $apps->where('status', $status);

        $summary = [
            'total' => (clone $apps)->count(),
            'approved' => (clone $apps)->where('status', 'approved')->count(),
            'rejected' => (clone $apps)->where('status', 'rejected')->count(),
            'pending' => (clone $apps)->whereIn('status', ['submitted', 'under_review', 'for_evaluation'])->count(),
        ];
        $byProgram = (clone $apps)->reorder()->selectRaw('scholarship_program_id, COUNT(*) as total')
            ->groupBy('scholarship_program_id')->with('program')->get();
        $byStatus = (clone $apps)->reorder()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        if ($request->get('export') === 'pdf') {
            $this->authorizeExport();
            $exportRows = (clone $apps)->with(['student', 'program'])->latest()->limit(1000)->get();
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf', [
                'tab' => 'applications', 'rows' => $exportRows, 'summary' => $summary,
                'generated' => now()->format('M d, Y h:i A'),
            ])->setPaper('a4', 'landscape');
            AuditLog::record(Auth::user(), 'report_export', null, 'Exported applications PDF');
            return $pdf->download('adfc-applications-' . date('Ymd-His') . '.pdf');
        }

        if ($request->get('export') === 'csv') {
            $this->authorizeExport();
            $filename = 'adfc-applications-' . date('Ymd-His') . '.csv';
            $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];
            $exportRows = (clone $apps)->with(['student', 'program'])->latest()->limit(5000)->get();
            return response()->stream(function () use ($exportRows) {
                $out = fopen('php://output', 'w');
                \App\Support\Csv::write($out, ['Application No', 'Student', 'Email', 'Scholarship', 'Year', 'Status', 'Submitted', 'Decided']);
                foreach ($exportRows as $a) {
                    \App\Support\Csv::write($out, [$a->application_no, $a->student?->name, $a->student?->email, $a->program?->name, $a->academic_year, $a->status, $a->submitted_at, $a->decided_at]);
                }
                fclose($out);
            }, 200, $headers);
        }

        $rows = $apps->latest()->paginate(20)->withQueryString();
        return view('reports.index', ['tab' => 'applications'] + compact('rows', 'programs', 'summary', 'byProgram', 'byStatus'));
    }

    // ── Registered students tab ──
    private function students(Request $request, $programs)
    {
        $q = StudentProfile::with('user');
        if (auth()->user()->isStaff()) $q->whereHas('user.applications', fn ($a) => $a->where('assigned_to', auth()->id()));
        if ($s = $request->get('search')) {
            $q->where(fn ($qq) => $qq->where('student_id_no', 'like', "%{$s}%")
                ->orWhere('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%"));
        }
        if ($c = $request->get('course')) $q->where('course', 'like', "%{$c}%");

        if ($request->get('export') === 'csv') {
            $this->authorizeExport();
            $rows = (clone $q)->limit(5000)->get();
            return response()->stream(function () use ($rows) {
                $out = fopen('php://output', 'w');
                \App\Support\Csv::write($out, ['Student ID', 'Name', 'Email', 'Course', 'Year', 'Enrollment']);
                foreach ($rows as $p) {
                    \App\Support\Csv::write($out, [$p->student_id_no, $p->full_name, $p->user?->email, $p->course, $p->year_level, $p->enrollment_status]);
                }
                fclose($out);
            }, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename=adfc-students-' . date('Ymd-His') . '.csv']);
        }
        if ($request->get('export') === 'pdf') {
            $this->authorizeExport();
            $rows = (clone $q)->limit(1000)->get();
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf', [
                'tab' => 'students', 'rows' => $rows, 'generated' => now()->format('M d, Y h:i A'),
            ])->setPaper('a4', 'landscape')->download('adfc-students-' . date('Ymd-His') . '.pdf');
        }

        $rows = $q->latest()->paginate(20)->withQueryString();
        return view('reports.index', ['tab' => 'students'] + compact('rows', 'programs'));
    }

    // ── Programs utilization tab ──
    private function programsReport(Request $request, $programs)
    {
        $q = ScholarshipProgram::withCount(['applications', 'awards'])->with('creator');
        if ($st = $request->get('status')) $q->where('status', $st);

        if ($request->get('export') === 'csv') {
            $this->authorizeExport();
            $rows = (clone $q)->get();
            return response()->stream(function () use ($rows) {
                $out = fopen('php://output', 'w');
                \App\Support\Csv::write($out, ['Code', 'Name', 'Category', 'Status', 'Slots', 'Applications', 'Awards', 'Deadline']);
                foreach ($rows as $p) {
                    \App\Support\Csv::write($out, [$p->code, $p->name, $p->category, $p->status, $p->slots, $p->applications_count, $p->awards_count, $p->deadline?->format('Y-m-d')]);
                }
                fclose($out);
            }, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename=adfc-programs-' . date('Ymd-His') . '.csv']);
        }
        if ($request->get('export') === 'pdf') {
            $this->authorizeExport();
            $rows = (clone $q)->get();
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf', [
                'tab' => 'programs', 'rows' => $rows, 'generated' => now()->format('M d, Y h:i A'),
            ])->setPaper('a4', 'landscape')->download('adfc-programs-' . date('Ymd-His') . '.pdf');
        }

        $rows = $q->latest()->paginate(20)->withQueryString();
        return view('reports.index', ['tab' => 'programs'] + compact('rows', 'programs'));
    }

    // ── Eligibility results tab ──
    private function eligibility(Request $request, $programs)
    {
        $q = EligibilityResult::with(['application.student', 'application.program']);
        if (auth()->user()->isStaff()) $q->whereHas('application', fn ($a) => $a->where('assigned_to', auth()->id()));
        if ($r = $request->get('result')) $q->where('result', $r);
        if ($pid = $request->get('scholarship_id')) $q->whereHas('application', fn ($qq) => $qq->where('scholarship_program_id', $pid));

        if ($request->get('export') === 'csv') {
            $this->authorizeExport();
            $rows = (clone $q)->latest()->limit(5000)->get();
            return response()->stream(function () use ($rows) {
                $out = fopen('php://output', 'w');
                \App\Support\Csv::write($out, ['Application No', 'Student', 'Program', 'Result', 'Checked At', 'Reasons']);
                foreach ($rows as $er) {
                    \App\Support\Csv::write($out, [$er->application?->application_no, $er->application?->student?->name, $er->application?->program?->name, $er->result, $er->checked_at, str_replace("\n", ' | ', (string) $er->reasons)]);
                }
                fclose($out);
            }, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename=adfc-eligibility-' . date('Ymd-His') . '.csv']);
        }
        if ($request->get('export') === 'pdf') {
            $this->authorizeExport();
            $rows = (clone $q)->latest()->limit(1000)->get();
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf', [
                'tab' => 'eligibility', 'rows' => $rows, 'generated' => now()->format('M d, Y h:i A'),
            ])->setPaper('a4', 'landscape')->download('adfc-eligibility-' . date('Ymd-His') . '.pdf');
        }

        $rows = $q->latest()->paginate(20)->withQueryString();
        return view('reports.index', ['tab' => 'eligibility'] + compact('rows', 'programs'));
    }

    private function authorizeExport(): void
    {
        abort_unless(in_array(Auth::user()->role, ['admin', 'staff'], true), 403);
    }

    public function recipients(Request $request)
    {
        $awards = ScholarshipAward::with(['student', 'program'])->when(auth()->user()->isStaff(), fn ($q) => $q->whereHas('application', fn ($a) => $a->where('assigned_to', auth()->id())))->latest()->paginate(20);
        return view('reports.recipients', compact('awards'));
    }
}
