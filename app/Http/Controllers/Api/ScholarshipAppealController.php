<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DecideScholarshipAppealRequest;
use App\Http\Requests\StoreScholarshipAppealRequest;
use App\Http\Resources\ScholarshipAppealResource;
use App\Models\ScholarshipAppeal;
use App\Models\ScholarshipAward;
use App\Services\ScholarshipAppealService;
use Illuminate\Http\Request;

class ScholarshipAppealController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['status' => ['nullable', \Illuminate\Validation\Rule::in(ScholarshipAppeal::STATUSES)]]);
        $query = ScholarshipAppeal::with(['award.program', 'student']);
        if ($request->user()->isStudent()) $query->where('user_id', $request->user()->id);
        if ($status = $data['status'] ?? null) $query->where('status', $status);
        return ScholarshipAppealResource::collection($query->orderByRaw('CASE WHEN pending_slot = 1 THEN 0 ELSE 1 END')->latest('id')->paginate(15));
    }

    public function store(StoreScholarshipAppealRequest $request, ScholarshipAward $award, ScholarshipAppealService $service)
    {
        $appeal = $service->submit($award, $request->user(), $request->validated());
        return (new ScholarshipAppealResource($appeal->load('award.program')))->response()->setStatusCode(201);
    }

    public function decide(DecideScholarshipAppealRequest $request, ScholarshipAppeal $appeal, ScholarshipAppealService $service)
    {
        return new ScholarshipAppealResource($service->decide($appeal, $request->user(), $request->validated())->load('award.program'));
    }
}
