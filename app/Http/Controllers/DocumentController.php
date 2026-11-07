<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentUploadRequest;
use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipRequirement;
use App\Services\NotifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    use \App\Concerns\ActsOnAssignedApplications;
    // Staff document-verification queue: all non-verified documents
    public function queue(Request $request)
    {
        $query = ApplicationDocument::with(['application.student', 'application.program', 'requirement'])
            ->whereHas('application', fn ($q) => $q->whereIn('status', ScholarshipApplication::REVIEW_STATUSES))
            ->where('verification_status', $request->get('status') === 'requires_correction' ? 'requires_correction' : 'pending')->latest();
        if (Auth::user()->isStaff()) {
            $query->whereHas('application', fn ($q) => $q->where('assigned_to', Auth::id()));
        }
        if ($s = $request->get('search')) {
            $query->where(fn ($q) => $q->where('document_name', 'like', "%{$s}%")
                ->orWhereHas('application', fn ($qq) => $qq->where('application_no', 'like', "%{$s}%")));
        }
        if ($request->get('mine')) {
            $query->whereHas('application', fn ($q) => $q->where('assigned_to', Auth::id()));
        }
        $documents = $query->paginate(20)->withQueryString();
        return view('documents.queue', compact('documents'));
    }

    // Student: every own document across all applications
    public function myDocuments()
    {
        $documents = ApplicationDocument::with(['application.program', 'requirement'])
            ->whereHas('application', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest()->paginate(20);
        return view('documents.my-index', compact('documents'));
    }
    public function store(DocumentUploadRequest $request, ScholarshipApplication $application)
    {
        $doc = app(\App\Services\DocumentWorkflowService::class)->upload($request, $application);

        if ($request->expectsJson()) return response()->json(['message' => 'Document uploaded.', 'id' => $doc->id], 201);
        return back()->with('success', 'Document uploaded.');
    }

    public function destroy(ScholarshipApplication $application, ApplicationDocument $document)
    {
        app(\App\Services\DocumentWorkflowService::class)->remove($application, $document);

        return back()->with('success', 'Document removed.');
    }

    public function download(ScholarshipApplication $application, ApplicationDocument $document)
    {
        abort_unless($document->scholarship_application_id === $application->id, 404);
        $user = Auth::user();
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
            'verification_status' => 'required|in:pending,verified,rejected,requires_correction',
            'verification_notes' => 'required_if:verification_status,rejected,requires_correction|nullable|string|max:2000',
        ]);

        app(\App\Services\DocumentReviewService::class)->review($application, $request->user(), [
            ['id' => $document->id] + $data,
        ]);

        return back()->with('success', 'Document verification updated.');
    }
}
