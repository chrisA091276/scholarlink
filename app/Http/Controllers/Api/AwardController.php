<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RenewalApplicationResource;
use App\Http\Resources\ScholarshipAwardResource;
use App\Http\Requests\UpdateAwardStatusRequest;
use App\Models\RenewalApplication;
use App\Models\ScholarshipAward;
use App\Services\AwardStatusService;
use App\Services\ApplicationTemplateService;
use Illuminate\Http\Request;

class AwardController extends Controller
{
    public function index(Request $request)
    {
        $query = ScholarshipAward::with(['student', 'program'])->latest();
        if (auth()->user()->isStaff()) $query->whereHas('application', fn ($q) => $q->where('assigned_to', auth()->id()));

        if ($search = $request->get('search')) {
            $query->where(fn ($q) => $q->where('award_no', 'like', "%{$search}%")
                ->orWhereHas('student', fn ($qq) => $qq->where('name', 'like', "%{$search}%")));
        }
        if ($status = $request->get('status')) {
            $query->where('recipient_status', $status);
        }

        $perPage = min(max((int) $request->get('per_page', 15), 1), 50);

        return ScholarshipAwardResource::collection($query->paginate($perPage));
    }

    public function show(ScholarshipAward $award)
    {
        if (auth()->user()->isStaff()) abort_unless((int) $award->application->assigned_to === auth()->id(), 403);
        $award->load(['student.studentProfile', 'program', 'application', 'renewals']);

        return new ScholarshipAwardResource($award);
    }

    public function updateStatus(UpdateAwardStatusRequest $request, ScholarshipAward $award, AwardStatusService $service)
    {
        $award = $service->update($award, $request->user(), $request->validated());

        return response()->json([
            'message' => 'Award updated.',
            'data' => new ScholarshipAwardResource($award->fresh()->load(['student', 'program'])),
        ]);
    }

    public function myAwards(Request $request)
    {
        $awards = ScholarshipAward::with(['program', 'renewals', 'appeals'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return ScholarshipAwardResource::collection($awards);
    }

    public function myRenewals(Request $request)
    {
        $renewals = RenewalApplication::with(['award.program'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return RenewalApplicationResource::collection($renewals);
    }

    public function storeRenewal(Request $request, ScholarshipAward $award, ApplicationTemplateService $templates)
    {
        abort_unless($award->user_id === $request->user()->id, 403);
        abort_unless($award->recipient_status === 'active', 403, 'Only active awards can be renewed.');
        $award->loadMissing('program');

        $data = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'statement' => ['nullable', 'string', 'max:5000'],
            'form_data' => ['nullable', 'array'],
            'action' => ['required', 'in:draft,submit'],
        ]);
        $renewal = app(\App\Services\RenewalService::class)->save($award, $request->user(), $data);

        return response()->json([
            'message' => 'Renewal application saved.',
            'data' => new RenewalApplicationResource($renewal->load('award.program')),
        ], 201);
    }

    public function indexRenewals(Request $request)
    {
        $query = RenewalApplication::with(['award.program', 'student'])->latest();
        if (auth()->user()->isStaff()) $query->whereHas('award.application', fn ($q) => $q->where('assigned_to', auth()->id()));

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $perPage = min(max((int) $request->get('per_page', 15), 1), 50);

        return RenewalApplicationResource::collection($query->paginate($perPage));
    }

    public function decideRenewal(Request $request, RenewalApplication $renewal)
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected,under_review'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        app(\App\Services\RenewalService::class)->decide($renewal, $request->user(), $data);

        return response()->json([
            'message' => 'Renewal decision recorded.',
            'data' => new RenewalApplicationResource($renewal->fresh()->load(['award.program', 'student'])),
        ]);
    }
}
