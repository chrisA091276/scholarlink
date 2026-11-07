<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecideScholarshipAppealRequest;
use App\Http\Requests\StoreScholarshipAppealRequest;
use App\Models\ScholarshipAppeal;
use App\Models\ScholarshipAward;
use App\Services\ScholarshipAppealService;
use Illuminate\Http\Request;

class ScholarshipAppealController extends Controller
{
    public function create(Request $request, ScholarshipAward $award)
    {
        abort_unless((int) $award->user_id === (int) $request->user()->id, 403);
        if (! $award->canAppeal() || $award->appeals()->whereIn('status', ScholarshipAppeal::PENDING_STATUSES)->exists()) {
            return redirect()->route('my-awards')->with('error', 'This award is not eligible for a new appeal, or an appeal is already awaiting a decision.');
        }
        $award->load('program');
        return view('appeals.create', compact('award'));
    }

    public function store(StoreScholarshipAppealRequest $request, ScholarshipAward $award, ScholarshipAppealService $service)
    {
        $service->submit($award, $request->user(), $request->validated());
        return redirect()->route('my-awards')->with('success', 'Your reinstatement appeal has been submitted. You can track the decision below.');
    }

    public function index(Request $request)
    {
        $status = $request->validate(['status' => ['nullable', \Illuminate\Validation\Rule::in(ScholarshipAppeal::STATUSES)]] )['status'] ?? null;
        $appeals = ScholarshipAppeal::with(['student', 'award.program'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByRaw('CASE WHEN pending_slot = 1 THEN 0 ELSE 1 END')->latest('id')->paginate(15)->withQueryString();
        return view('appeals.index', compact('appeals'));
    }

    public function show(ScholarshipAppeal $appeal)
    {
        $appeal->load(['student', 'award.program', 'decider']);
        return view('appeals.show', compact('appeal'));
    }

    public function decide(DecideScholarshipAppealRequest $request, ScholarshipAppeal $appeal, ScholarshipAppealService $service)
    {
        $service->decide($appeal, $request->user(), $request->validated());
        return back()->with('success', $request->input('status') === 'approved' ? 'Appeal approved. The student has been reinstated.' : 'Appeal decision saved and the student notified.');
    }
}
