<?php

namespace App\Http\Controllers\Api;

use App\Concerns\ActsOnAssignedApplications;
use App\Http\Controllers\Controller;
use App\Http\Resources\EligibilityResultResource;
use App\Http\Resources\ScholarshipApplicationResource;
use App\Models\AuditLog;
use App\Models\ScholarshipApplication;
use App\Services\EligibilityService;
use App\Services\NotifyService;
use Illuminate\Http\Request;

class EligibilityController extends Controller
{
    use ActsOnAssignedApplications;

    public function checklist(Request $request)
    {
        $applications = ScholarshipApplication::with([
            'program.requirements',
            'program.eligibilityRules' => fn ($q) => $q->where('is_active', true),
            'documents.requirement',
            'latestEligibilityResult',
        ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return ScholarshipApplicationResource::collection($applications);
    }

    public function screen(Request $request, ScholarshipApplication $application, EligibilityService $service)
    {
        $this->ensureCanActOn($application);

        $result = $service->runAndStore($application, $request->user()->id);

        AuditLog::record(
            $request->user(),
            'eligibility_screen',
            $result,
            "Screened {$application->application_no}: {$result->result} via API"
        );

        NotifyService::send(
            $application->user_id,
            'Eligibility screening completed',
            "Your application {$application->application_no} was screened: {$result->result}.",
            route('my-applications.show', $application)
        );

        return response()->json([
            'message' => 'Screening completed: ' . $result->result,
            'data' => new EligibilityResultResource($result->load('checker')),
        ]);
    }

    public function show(ScholarshipApplication $application)
    {
        $application->load(['eligibilityResults.checker', 'program.eligibilityRules']);

        return response()->json([
            'application_id' => $application->id,
            'application_no' => $application->application_no,
            'status' => $application->status,
            'latest_result' => $application->eligibilityResults->sortByDesc('checked_at')->first()
                ? new EligibilityResultResource($application->eligibilityResults->sortByDesc('checked_at')->first()->load('checker'))
                : null,
            'history' => EligibilityResultResource::collection($application->eligibilityResults->load('checker')),
            'rules' => $application->program->eligibilityRules->map(fn ($rule) => [
                'id' => $rule->id,
                'rule_key' => $rule->rule_key,
                'rule_label' => $rule->rule_label,
                'operator' => $rule->operator,
                'rule_value' => $rule->rule_value,
                'rule_type' => $rule->rule_type,
                'failure_mode' => $rule->failure_mode,
                'is_active' => (bool) $rule->is_active,
            ]),
        ]);
    }
}
