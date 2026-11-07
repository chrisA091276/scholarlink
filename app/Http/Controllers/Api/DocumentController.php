<?php

namespace App\Http\Controllers\Api;

use App\Concerns\ActsOnAssignedApplications;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentUploadRequest;
use App\Http\Resources\ApplicationDocumentResource;
use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipRequirement;
use App\Services\NotifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    use ActsOnAssignedApplications;

    public function queue(Request $request)
    {
        $query = ApplicationDocument::with(['application.student', 'application.program', 'requirement'])
            ->whereIn('verification_status', ['pending', 'requires_correction'])
            ->latest();
        if ($request->user()->isStaff()) {
            $query->whereHas('application', fn ($q) => $q->where('assigned_to', $request->user()->id));
        }

        if ($search = $request->get('search')) {
            $query->where(fn ($q) => $q->where('document_name', 'like', "%{$search}%")
                ->orWhereHas('application', fn ($qq) => $qq->where('application_no', 'like', "%{$search}%")));
        }
        if ($request->boolean('mine')) {
            $query->whereHas('application', fn ($q) => $q->where('assigned_to', $request->user()->id));
        }

        $perPage = min(max((int) $request->get('per_page', 20), 1), 50);

        return ApplicationDocumentResource::collection($query->paginate($perPage));
    }

    public function myIndex(Request $request)
    {
        $documents = ApplicationDocument::with(['application.program', 'requirement'])
            ->whereHas('application', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(20);

        return ApplicationDocumentResource::collection($documents);
    }

    public function applicationDocuments(Request $request, ScholarshipApplication $application)
    {
        abort_unless($application->user_id === $request->user()->id, 403);
        return ApplicationDocumentResource::collection(
            $application->documents()->with(['requirement', 'verifier'])->latest()->paginate(20)
        );
    }

    public function store(DocumentUploadRequest $request, ScholarshipApplication $application)
    {
        $document = app(\App\Services\DocumentWorkflowService::class)->upload($request, $application);

        return response()->json([
            'message' => 'Document uploaded.',
            'data' => new ApplicationDocumentResource($document->load(['requirement', 'application'])),
        ], 201);
    }

    public function destroy(Request $request, ScholarshipApplication $application, ApplicationDocument $document)
    {
        app(\App\Services\DocumentWorkflowService::class)->remove($application, $document);

        return response()->json(['message' => 'Document removed.']);
    }

    public function download(Request $request, ScholarshipApplication $application, ApplicationDocument $document)
    {
        abort_unless($document->scholarship_application_id === $application->id, 404);

        $user = $request->user();
        $isOwner = $application->user_id === $user->id;
        if ($user->isStaff()) abort_unless((int) $application->assigned_to === (int) $user->id, 403);
        abort_unless($isOwner || $user->isAdmin() || $user->isStaff(), 403);
        return app(\App\Services\PrivateDocumentStorage::class)->download($document);
    }

    public function verify(Request $request, ScholarshipApplication $application, ApplicationDocument $document)
    {
        abort_unless($document->scholarship_application_id === $application->id, 404);

        $this->ensureCanActOn($application);

        $data = $request->validate([
            'verification_status' => ['required', 'in:pending,verified,rejected,requires_correction'],
            'verification_notes' => ['required_if:verification_status,rejected,requires_correction', 'nullable', 'string', 'max:2000'],
        ]);

        app(\App\Services\DocumentReviewService::class)->review($application, $request->user(), [
            ['id' => $document->id] + $data,
        ]);

        return response()->json([
            'message' => 'Document verification updated.',
            'data' => new ApplicationDocumentResource($document->fresh()->load(['requirement', 'verifier'])),
        ]);
    }
}
