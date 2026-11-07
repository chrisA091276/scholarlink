<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ScholarshipApplication;
use App\Services\EligibilityService;
use App\Services\NotifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EligibilityController extends Controller
{
    use \App\Concerns\ActsOnAssignedApplications;
    // Staff screening center: every application + latest result + run action
    public function index(Request $request)
    {
        $query = ScholarshipApplication::with(['student.studentProfile', 'program', 'latestEligibilityResult'])->latest();
        if ($s = $request->get('search')) {
            $query->where(fn ($q) => $q->where('application_no', 'like', "%{$s}%")
                ->orWhereHas('student', fn ($qq) => $qq->where('name', 'like', "%{$s}%")));
        }
        if ($r = $request->get('result')) {
            $query->whereHas('eligibilityResults', fn ($q) => $q->where('result', $r)->latest()->limit(1));
        }
        $applications = $query->paginate(15)->withQueryString();
        return view('eligibility.index', compact('applications'));
    }

    // Student checklist: program requirements + which are met/unmet
    public function checklist()
    {
        $applications = ScholarshipApplication::with(['program.requirements', 'program.eligibilityRules', 'documents', 'latestEligibilityResult'])
            ->where('user_id', Auth::id())->latest()->get();
        return view('eligibility.checklist', compact('applications'));
    }
    public function screen(ScholarshipApplication $application, EligibilityService $service)
    {
        $this->ensureCanActOn($application);
        $result = $service->runAndStore($application, Auth::id());
        AuditLog::record(Auth::user(), 'eligibility_screen', $result, "Screened {$application->application_no}: {$result->result}");
        NotifyService::send($application->user_id, 'Eligibility screening completed', "Your application {$application->application_no} was screened: {$result->result}.", route('my-applications.show', $application));

        return back()->with('success', 'Screening completed: ' . $result->result);
    }

    public function show(ScholarshipApplication $application)
    {
        $application->load(['eligibilityResults.checker', 'program.eligibilityRules']);
        return view('eligibility.show', compact('application'));
    }
}
