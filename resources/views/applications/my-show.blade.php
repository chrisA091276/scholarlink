@extends('layouts.app')
@section('title','Application '.$application->application_no)
@section('page-title','Application details')
@section('content')
@php
  $statusNames = [
    'draft' => 'Draft',
    'submitted' => 'Submitted',
    'under_review' => 'Under review',
    'incomplete_requirements' => 'Needs correction',
    'for_evaluation' => 'For evaluation',
    'recommended_approval' => 'Recommended for approval',
    'recommended_rejection' => 'Under decision',
    'approved' => 'Approved',
    'rejected' => 'Not approved',
    'withdrawn' => 'Withdrawn',
  ];
  $statusClass = in_array($application->status, ['approved', 'recommended_approval'], true)
    ? 'green'
    : (in_array($application->status, ['rejected'], true) ? 'red' : ($application->status === 'incomplete_requirements' ? 'gold' : 'blue'));
  $progress = match ($application->status) {
    'draft' => 20,
    'submitted' => 40,
    'under_review' => 60,
    'incomplete_requirements' => 45,
    'for_evaluation', 'recommended_approval', 'recommended_rejection' => 80,
    'approved' => 100,
    default => 0,
  };
@endphp

<div class="student-page">
  <a href="{{ route('my-applications.index') }}" class="student-note text-decoration-none d-inline-flex align-items-center gap-2 mb-3"><i class="bi bi-arrow-left"></i>Back to my applications</a>

  <div class="student-page-head">
    <div>
      <span class="student-eyebrow">APPLICATION {{ $application->application_no }}</span>
      <h1>{{ $application->program?->name }}</h1>
      <p>Review your saved information, document checklist, and application timeline.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('my-applications.download',$application) }}" class="btn btn-outline-primary"><i class="bi bi-download me-2"></i>Download form</a>
      @if(in_array($application->status, ['draft','incomplete_requirements'], true))
        <a href="{{ route('my-applications.edit',$application) }}" class="btn btn-gold"><i class="bi bi-pencil me-2"></i>Continue editing</a>
      @endif
    </div>
  </div>

  <div class="student-surface student-surface-body mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <span class="student-note">Academic year {{ $application->academic_year }} · Last updated {{ $application->updated_at?->diffForHumans() }}</span>
        <h2 class="h5 fw-bold mt-2 mb-0">Application status</h2>
      </div>
      <span class="status-chip {{ $statusClass }}">{{ $statusNames[$application->status] ?? str_replace('_',' ',$application->status) }}</span>
    </div>
    <div class="student-progress-track mt-4"><span style="width:{{ $progress }}%"></span></div>
    <div class="d-flex justify-content-between student-note mt-2"><span>Started</span><span>Submitted</span><span>Review</span><span>Decision</span></div>
    @if($application->decision_remarks)
      <div class="student-alert mt-3" style="background:#f5f8fc;border-color:#e1e8f1;color:#51647e"><i class="bi bi-chat-left-text"></i><span><strong>Decision remarks:</strong> {{ $application->decision_remarks }}</span></div>
    @endif
  </div>

@include('applications.partials.adfc-summary')
  <div class="row g-4">
    <div class="col-xl-8">
      <div class="student-surface student-surface-body mb-4">
        <div class="student-section-title"><h2>Application information</h2><span class="student-note">Saved answers</span></div>
        <div class="student-info-grid">
          <div class="student-info-item"><span>Academic year</span><strong>{{ $application->academic_year }}</strong></div>
          <div class="student-info-item"><span>Enrollment status</span><strong>{{ $application->enrollment_status ?? 'Not set' }}</strong></div>
          <div class="student-info-item"><span>Parent / guardian</span><strong>{{ $application->guardian_name ?? 'Not set' }}</strong></div>
          <div class="student-info-item"><span>Household size</span><strong>{{ $application->household_size ?? 'Not set' }}</strong></div>
          <div class="student-info-item"><span>Monthly household income</span><strong>{{ $application->monthly_household_income !== null ? 'PHP '.number_format($application->monthly_household_income,2) : 'Not set' }}</strong></div>
        </div>
        <div class="student-info-item mt-2"><span>Personal statement</span><strong class="student-copy">{{ $application->statement ?: 'No statement provided.' }}</strong></div>

        @if($application->form_data)
          <div class="student-section-title mt-4"><h2>Scholarship-specific answers</h2></div>
          @foreach($application->program?->application_template ?? [] as $field)
            @if(array_key_exists($field['key'] ?? '', $application->form_data))
              @php($value = $application->form_data[$field['key']] ?? null)
              <div class="student-info-item"><span>{{ $field['label'] ?? str_replace('_',' ',ucfirst($field['key'])) }}</span><strong>{{ is_array($value) ? json_encode($value) : ($value !== null && $value !== '' ? $value : '—') }}</strong></div>
            @endif
          @endforeach
        @endif
      </div>

      <div class="student-surface student-surface-body mb-4">
        <div class="student-section-title"><h2><i class="bi bi-folder-check me-2 text-primary"></i>Supporting documents</h2><span class="student-note">{{ $application->documents->count() }} uploaded</span></div>
        <div class="student-note mb-3">Required: {{ $application->program->requirements->where('is_required',true)->pluck('name')->join(', ') ?: 'None configured' }}</div>

        @forelse($application->documents as $d)
          <div class="student-list-row">
            <span class="student-list-icon"><i class="bi bi-file-earmark-text"></i></span>
            <span><strong>{{ $d->document_name }}</strong><small>{{ $d->requirement?->name ?? 'General file' }} · {{ strtoupper($d->file_type) }} · {{ str_replace('_',' ',$d->verification_status) }}{{ $d->verification_notes ? ' · '.$d->verification_notes : '' }}</small></span>
            <a href="{{ route('documents.download',[$application,$d]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
            @if(in_array($application->status, ['draft','incomplete_requirements'], true))
              <form method="POST" action="{{ route('documents.destroy',[$application,$d]) }}" onsubmit="return confirm('Remove this document?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
            @endif
          </div>
        @empty
          <div class="student-empty py-3"><i class="bi bi-folder2-open"></i><p class="mb-0">No documents uploaded yet.</p></div>
        @endforelse

        @if(in_array($application->status, ['draft','incomplete_requirements'], true))
          <form method="POST" action="{{ route('documents.store',$application) }}" enctype="multipart/form-data" class="row g-2 mt-3 pt-3 border-top">
            @csrf
            <div class="col-md-4"><select name="scholarship_requirement_id" class="form-select student-filter"><option value="">General file</option>@foreach($application->program->requirements ?? [] as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><input name="document_name" class="form-control student-filter" placeholder="Document name" required></div>
            @if($application->form_version)<div class="col-md-5"><label for="uploadCategory" class="visually-hidden">Document category</label><select id="uploadCategory" name="document_category" class="form-select student-filter"><option value="">Additional program requirement</option>@foreach(config('adfc_form.documents') as $category => $label)<option value="{{ $category }}">{{ $label }}</option>@endforeach</select></div>@endif
            <div class="col-md-3"><input type="file" name="file" class="form-control student-filter" accept=".pdf,.jpg,.jpeg,.png" required></div>
            <div class="col-md-2"><button class="btn btn-navy w-100">Upload</button></div>
          </form>
        @endif
      </div>

      @if($application->eligibilityResults->count())
        <div class="student-surface student-surface-body">
          <div class="student-section-title"><h2>Eligibility screening</h2></div>
          @foreach($application->eligibilityResults as $r)
            <div class="student-alert mb-2" style="background:#f5f8fc;border-color:#e1e8f1;color:#51647e"><i class="bi bi-info-circle"></i><span><strong>{{ str_replace('_',' ',$r->result) }}</strong> · {{ $r->checked_at?->format('M d, Y') }}<br>{{ $r->reasons }}</span></div>
          @endforeach
        </div>
      @endif
    </div>

    <div class="col-xl-4">
      <div class="student-detail-side">
        <div class="student-section-title"><h2>Application timeline</h2></div>
        @foreach($application->statusHistories as $h)
          <div class="student-list-row"><span class="student-list-icon"><i class="bi bi-circle-fill" style="font-size:.45rem"></i></span><span><strong>{{ $statusNames[$h->to_status] ?? str_replace('_',' ',$h->to_status) }}</strong><small>{{ $h->created_at?->format('M d, Y h:i A') }}{{ $h->remarks ? ' · '.$h->remarks : '' }}</small></span></div>
        @endforeach
      </div>

      <div class="student-detail-side mt-3"><h2><i class="bi bi-file-earmark-pdf me-2" style="color:#c9a227"></i>Official application form</h2><p class="student-copy">Download the ADFC form with your saved details, checkboxes, photo box, consent, and signature area. Additional scholarship answers appear on a separate supporting page when needed.</p><a href="{{ route('my-applications.download',$application) }}" class="btn btn-navy w-100"><i class="bi bi-download me-2"></i>Download ADFC form</a></div>

      @if(in_array($application->status, ['draft','incomplete_requirements'], true))
        <div class="student-alert mt-3"><i class="bi bi-lightbulb"></i><span>Complete the required fields and upload all required documents before submitting.</span></div>
        @if($application->form_version) @include('applications.partials.adfc-notices') @endif
        <div class="d-flex gap-2 mt-3">
          <form method="POST" action="{{ route('my-applications.submit',$application) }}">@csrf<div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="declaration" value="1" id="submitDeclaration" required><label class="form-check-label" for="submitDeclaration">I confirm that my application is accurate and accept the <a href="{{ route('privacy') }}">privacy notice</a>.</label></div><button class="btn btn-gold">Submit for evaluation</button></form>
          <form method="POST" action="{{ route('my-applications.withdraw',$application) }}" onsubmit="return confirm('Withdraw this application?')">@csrf<button class="btn btn-outline-danger">Withdraw</button></form>
        </div>
      @endif

      @if($application->award)
        <div class="student-detail-side mt-3"><h2><i class="bi bi-trophy-fill me-2" style="color:#c9a227"></i>Award received</h2><p class="student-copy">{{ $application->award->award_no }} · {{ $application->award->recipient_status }}</p><a href="{{ route('my-awards') }}" class="btn btn-sm btn-gold">View awards</a></div>
      @endif
    </div>
  </div>
</div>
@endsection
