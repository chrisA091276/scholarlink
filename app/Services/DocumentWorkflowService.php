<?php

namespace App\Services;

use App\Http\Requests\DocumentUploadRequest;
use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\ScholarshipApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentWorkflowService
{
    public function upload(DocumentUploadRequest $request, ScholarshipApplication $application): ApplicationDocument
    {
        return DB::transaction(function () use ($request, $application) {
            $application = ScholarshipApplication::lockForUpdate()->findOrFail($application->id);
            abort_unless($application->user_id === $request->user()->id, 403);
            abort_unless(in_array($application->status, ['draft', 'incomplete_requirements'], true), 403, 'Uploads are locked until corrections are requested.');
            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension());
            $category = $request->input('document_category');
            if ($category === 'id_photo' && ! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                throw ValidationException::withMessages(['file' => 'Upload your ID photo as a JPG or PNG image.']);
            }
            $mimeTypes = ['pdf' => ['application/pdf'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png']];
            if (! in_array($file->getMimeType(), $mimeTypes[$ext] ?? [], true)) {
                throw ValidationException::withMessages(['file' => 'The file content does not match its extension.']);
            }
            $requirement = $request->filled('scholarship_requirement_id')
                ? $application->program->requirements()->findOrFail($request->integer('scholarship_requirement_id')) : null;
            if ($requirement) {
                $types = array_map('trim', explode(',', strtolower($requirement->allowed_types)));
                if (! in_array($ext, $types, true) || $file->getSize() > $requirement->max_size_kb * 1024) {
                    throw ValidationException::withMessages(['file' => 'The file does not meet this requirement\'s type or size limit.']);
                }
            }
            $old = $request->filled('replace_document_id')
                ? $application->documents()->findOrFail($request->integer('replace_document_id')) : null;
            if (! $old && $requirement) $old = $application->documents()->where('scholarship_requirement_id', $requirement->id)->first();
            if (! $old && $category && $category !== 'other') $old = $application->documents()->where('document_category', $category)->first();
            if ($old) {
                abort_unless($old->scholarship_requirement_id === $requirement?->id, 422, 'Replacement must match the original requirement.');
                abort_unless($old->document_category === $category, 422, 'Replacement must keep the original document category.');
                abort_if($application->status !== 'draft' && ! in_array($old->verification_status, ['rejected', 'requires_correction'], true), 403, 'Only documents requiring correction may be replaced.');
            }
            $stored = app(PrivateDocumentStorage::class)->store($file, $application->id);
            // Preserve the previous file and its review records for authorized audit access.
            if ($old) $old->delete();
            $document = $application->documents()->create($stored + [
                'scholarship_requirement_id' => $requirement?->id,
                'document_name' => $request->document_name,
                'document_category' => $category,
                'original_filename' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 255),
                'file_type' => $ext, 'file_size' => $file->getSize(), 'verification_status' => 'pending',
            ]);
            if ($application->form_review) $application->update(['form_review' => null]);
            AuditLog::record($request->user(), 'document_upload', $document, 'Uploaded document'.($old ? '; supersedes document '.$old->id : ''));
            return $document;
        });
    }

    public function remove(ScholarshipApplication $application, ApplicationDocument $document): void
    {
        DB::transaction(function () use ($application, $document) {
            $application = ScholarshipApplication::lockForUpdate()->findOrFail($application->id);
            abort_unless($application->user_id === auth()->id(), 403);
            abort_unless($document->scholarship_application_id === $application->id, 404);
            abort_unless($application->status === 'draft' || ($application->status === 'incomplete_requirements'
                && in_array($document->verification_status, ['rejected', 'requires_correction'], true)), 403);
            $document->delete();
            if ($application->form_review) $application->update(['form_review' => null]);
            AuditLog::record(auth()->user(), 'document_remove', $document, 'Document removed from active checklist; review history retained.');
        });
    }
}
