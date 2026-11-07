<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScholarshipProgramRequest;
use App\Models\AuditLog;
use App\Models\ScholarshipProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScholarshipProgramController extends Controller
{
    public function publicIndex(Request $request)
    {
        $query = ScholarshipProgram::where('status', 'published');
        if ($s = $request->get('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('category', 'like', "%{$s}%"));
        }
        if ($c = $request->get('category')) $query->where('category', $c);
        $programs = $query->orderBy('deadline')->paginate(9)->withQueryString();
        $categories = ScholarshipProgram::where('status', 'published')->distinct()->pluck('category');

        return view('scholarships.public-index', compact('programs', 'categories'));
    }

    public function publicShow(ScholarshipProgram $scholarship)
    {
        $canViewPrivate = Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isStaff());
        abort_unless($scholarship->status === 'published' || $canViewPrivate, 404);
        $scholarship->load(['requirements', 'eligibilityRules' => fn ($q) => $q->where('is_active', true)]);
        $existing = Auth::check()
            ? Auth::user()->applications()->where('scholarship_program_id', $scholarship->id)->first()
            : null;

        return view('scholarships.public-show', compact('scholarship', 'existing'));
    }

    public function index(Request $request)
    {
        $query = ScholarshipProgram::withCount('applications')->latest();
        if ($s = $request->get('search')) $query->where('name', 'like', "%{$s}%");
        if ($st = $request->get('status')) $query->where('status', $st);
        $programs = $query->paginate(12)->withQueryString();

        return view('scholarships.index', compact('programs'));
    }

    public function create()
    {
        return view('scholarships.create');
    }

    public function store(ScholarshipProgramRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();
        $program = ScholarshipProgram::create($data);
        AuditLog::record(Auth::user(), 'scholarship_create', $program, "Created program {$program->code}");

        return redirect()->route('scholarships.show', $program)->with('success', 'Scholarship program created.');
    }

    public function show(ScholarshipProgram $scholarship)
    {
        $scholarship->load(['requirements', 'eligibilityRules', 'applications.student', 'creator']);
        return view('scholarships.show', compact('scholarship'));
    }

    public function edit(ScholarshipProgram $scholarship)
    {
        return view('scholarships.edit', compact('scholarship'));
    }

    public function update(ScholarshipProgramRequest $request, ScholarshipProgram $scholarship)
    {
        $old = $scholarship->toArray();
        $scholarship->update($request->validated());
        AuditLog::record(Auth::user(), 'scholarship_update', $scholarship, "Updated program {$scholarship->code}", $old, $scholarship->fresh()->toArray());

        return redirect()->route('scholarships.show', $scholarship)->with('success', 'Program updated.');
    }

    public function destroy(ScholarshipProgram $scholarship)
    {
        if ($scholarship->applications()->exists()) {
            $scholarship->update(['status' => 'archived']);
            return redirect()->route('scholarships.index')->with('success', 'Program has applications — archived instead of deleted.');
        }
        $scholarship->delete();
        AuditLog::record(Auth::user(), 'scholarship_delete', null, "Deleted program {$scholarship->code}");

        return redirect()->route('scholarships.index')->with('success', 'Program deleted.');
    }

    // ── Requirements management (all programs) ──
    public function requirementsIndex(Request $request)
    {
        $query = \App\Models\ScholarshipRequirement::with('program')->latest();
        if ($pid = $request->get('scholarship_id')) $query->where('scholarship_program_id', $pid);
        $requirements = $query->paginate(20)->withQueryString();
        $programs = ScholarshipProgram::orderBy('name')->get();
        return view('requirements.index', compact('requirements', 'programs'));
    }

    // ── Eligibility criteria overview (all programs) ──
    public function eligibilityIndex(Request $request)
    {
        $query = \App\Models\EligibilityRule::with('program')->latest();
        if ($pid = $request->get('scholarship_id')) $query->where('scholarship_program_id', $pid);
        $rules = $query->paginate(20)->withQueryString();
        $programs = ScholarshipProgram::orderBy('name')->get();
        return view('eligibility-criteria.index', compact('rules', 'programs'));
    }

    // ── Requirements ──
    public function storeRequirement(\App\Http\Requests\ScholarshipRequirementRequest $request, ScholarshipProgram $scholarship)
    {
        $data = $request->validated();
        $data['is_required'] = $request->boolean('is_required', true);
        $scholarship->requirements()->create($data);

        return back()->with('success', 'Requirement added.');
    }

    public function destroyRequirement(ScholarshipProgram $scholarship, $requirementId)
    {
        $scholarship->requirements()->findOrFail($requirementId)->delete();
        return back()->with('success', 'Requirement removed.');
    }

    // ── Eligibility rules ──
    public function storeRule(Request $request, ScholarshipProgram $scholarship)
    {
        $data = $request->validate([
            'rule_key' => ['required', 'string', 'max:60', 'regex:/^[a-z][a-z0-9_.]{1,59}$/', new \App\Rules\SupportedScholarshipField],
            'rule_label' => ['required', 'string', 'max:200', new \App\Rules\SupportedScholarshipField],
            'rule_type' => 'nullable|in:text,number,date,boolean,select',
            'operator' => ['required', 'in:=,==,!=,<>,>,>=,<,<=,in,not_in,between,contains,not_contains,starts_with,ends_with,exists,present,not_exists,absent,is_true,is_false'],
            'rule_value' => 'nullable|string|max:5000',
            'failure_mode' => 'nullable|in:mandatory,manual_review',
            'is_active' => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['rule_value'] = $data['rule_value'] ?? '';
        $data['rule_type'] = $data['rule_type'] ?? 'text';
        $data['failure_mode'] = $data['failure_mode'] ?? 'manual_review';
        $scholarship->eligibilityRules()->create($data);

        return back()->with('success', 'Eligibility rule added.');
    }

    public function destroyRule(ScholarshipProgram $scholarship, $ruleId)
    {
        $scholarship->eligibilityRules()->findOrFail($ruleId)->delete();
        return back()->with('success', 'Rule removed.');
    }
}
