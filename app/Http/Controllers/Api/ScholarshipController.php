<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScholarshipProgramRequest;
use App\Http\Resources\EligibilityRuleResource;
use App\Http\Resources\ScholarshipProgramResource;
use App\Http\Resources\ScholarshipRequirementResource;
use App\Models\AuditLog;
use App\Models\ScholarshipProgram;
use Illuminate\Http\Request;

class ScholarshipController extends Controller
{
    public function index(Request $request)
    {
        $query = ScholarshipProgram::where('status', 'published');

        if ($search = $request->get('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%"));
        }
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $perPage = min(max((int) $request->get('per_page', 9), 1), 50);
        $programs = $query->withCount('applications')->orderBy('deadline')->paginate($perPage);

        return ScholarshipProgramResource::collection($programs);
    }

    public function show(Request $request, ScholarshipProgram $scholarship)
    {
        abort_unless($scholarship->status === 'published', 404);

        $scholarship->load([
            'requirements',
            'eligibilityRules' => fn ($q) => $q->where('is_active', true),
        ]);

        return new ScholarshipProgramResource($scholarship);
    }

    public function store(ScholarshipProgramRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $program = ScholarshipProgram::create($data);

        AuditLog::record($request->user(), 'scholarship_create', $program, "Created program {$program->code} via API");

        return response()->json([
            'message' => 'Scholarship program created.',
            'data' => new ScholarshipProgramResource($program),
        ], 201);
    }

    public function update(ScholarshipProgramRequest $request, ScholarshipProgram $scholarship)
    {
        $old = $scholarship->toArray();
        $scholarship->update($request->validated());

        AuditLog::record(
            $request->user(),
            'scholarship_update',
            $scholarship,
            "Updated program {$scholarship->code} via API",
            $old,
            $scholarship->fresh()->toArray()
        );

        return response()->json([
            'message' => 'Program updated.',
            'data' => new ScholarshipProgramResource($scholarship->fresh()),
        ]);
    }

    public function destroy(Request $request, ScholarshipProgram $scholarship)
    {
        if ($scholarship->applications()->exists()) {
            $scholarship->update(['status' => 'archived']);
            AuditLog::record($request->user(), 'scholarship_update', $scholarship, "Archived program {$scholarship->code} via API");

            return response()->json([
                'message' => 'Program has applications — archived instead of deleted.',
                'data' => new ScholarshipProgramResource($scholarship->fresh()),
            ]);
        }

        $code = $scholarship->code;
        $scholarship->delete();
        AuditLog::record($request->user(), 'scholarship_delete', null, "Deleted program {$code} via API");

        return response()->json(['message' => 'Program deleted.']);
    }

    public function publish(Request $request, ScholarshipProgram $scholarship)
    {
        $scholarship->update(['status' => 'published']);
        AuditLog::record($request->user(), 'scholarship_publish', $scholarship, "Published program {$scholarship->code} via API");
        return response()->json(['message' => 'Scholarship published.', 'data' => new ScholarshipProgramResource($scholarship->fresh())]);
    }

    public function requirements(Request $request, ScholarshipProgram $scholarship)
    {
        $user = $request->user();
        $adminRoute = $request->is('api/admin/*', 'api/v1/admin/*') && $user?->isAdmin();
        abort_unless($scholarship->status === 'published' || $adminRoute, 404);
        return ScholarshipRequirementResource::collection($scholarship->requirements()->orderBy('id')->paginate(50));
    }

    public function storeRequirement(\App\Http\Requests\ScholarshipRequirementRequest $request, ScholarshipProgram $scholarship)
    {
        $data = $request->validated();
        $data['is_required'] = $request->boolean('is_required', true);

        $requirement = $scholarship->requirements()->create($data);

        return response()->json([
            'message' => 'Requirement added.',
            'data' => new ScholarshipRequirementResource($requirement),
        ], 201);
    }

    public function destroyRequirement(Request $request, ScholarshipProgram $scholarship, string $requirement)
    {
        $scholarship->requirements()->findOrFail($requirement)->delete();

        return response()->json(['message' => 'Requirement removed.']);
    }

    public function storeRule(Request $request, ScholarshipProgram $scholarship)
    {
        $data = $request->validate([
            'rule_key' => ['required', 'string', 'max:60', 'regex:/^[a-z][a-z0-9_.]{1,59}$/', new \App\Rules\SupportedScholarshipField],
            'rule_label' => ['required', 'string', 'max:200', new \App\Rules\SupportedScholarshipField],
            'rule_type' => ['nullable', 'in:text,number,date,boolean,select'],
            'operator' => ['required', 'in:=,==,!=,<>,>,>=,<,<=,in,not_in,between,contains,not_contains,starts_with,ends_with,exists,present,not_exists,absent,is_true,is_false'],
            'rule_value' => ['nullable', 'string', 'max:5000'],
            'failure_mode' => ['nullable', 'in:mandatory,manual_review'],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['rule_value'] = $data['rule_value'] ?? '';
        $data['rule_type'] = $data['rule_type'] ?? 'text';
        $data['failure_mode'] = $data['failure_mode'] ?? 'manual_review';

        $rule = $scholarship->eligibilityRules()->create($data);

        return response()->json([
            'message' => 'Eligibility rule added.',
            'data' => new EligibilityRuleResource($rule),
        ], 201);
    }

    public function destroyRule(Request $request, ScholarshipProgram $scholarship, string $rule)
    {
        $scholarship->eligibilityRules()->findOrFail($rule)->delete();

        return response()->json(['message' => 'Rule removed.']);
    }
}
