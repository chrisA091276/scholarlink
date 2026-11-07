@extends('layouts.app')
@section('title','Review '.$application->application_no)
@section('page-title','Review '.$application->application_no)
@section('content')
@php $canAct = auth()->user()->isAdmin() || (int) $application->assigned_to === (int) auth()->id(); @endphp
@if(!$canAct)<div class="alert alert-warning small"><i class="bi bi-lock me-1"></i>This application is assigned to <b>{{ $application->assignee?->name ?? 'another staff member' }}</b> — you have read-only access. Ask an administrator to reassign it to act on it.</div>@endif
@include('applications.partials.adfc-summary')
<div class="row g-3">
<div class="col-lg-8">
<div class="card mb-3"><div class="card-body">
<div class="d-flex justify-content-between"><h6 class="fw-bold">{{ $application->student?->name }} <small class="text-muted">({{ $application->student?->email }})</small></h6><span class="badge bg-info">{{ str_replace('_',' ',$application->status) }}</span></div>
<div class="small">Program: <b>{{ $application->program?->name }}</b> · Year: {{ $application->academic_year }} · Enrollment: {{ $application->enrollment_status ?? '—' }}</div>
<x-app-progress :status="$application->status" />
@if($application->form_data)
<div class="border rounded-3 p-3 mt-3 bg-light"><h6 class="fw-semibold small">Scholarship-specific application details</h6>
@foreach($application->program?->application_template ?? [] as $field)
  @if(array_key_exists($field['key'] ?? '', $application->form_data))
    @php($value = $application->form_data[$field['key']] ?? null)
    <div class="small py-1"><b>{{ $field['label'] ?? str_replace('_', ' ', ucfirst($field['key'])) }}:</b> {{ is_array($value) ? json_encode($value) : ($value !== null && $value !== '' ? $value : '—') }}</div>
  @endif
@endforeach
</div>
@endif
@if($application->student?->studentProfile)<div class="small mt-1">Profile: {{ $application->student->studentProfile->course ?? '—' }} {{ $application->student->studentProfile->year_level ?? '' }} · ID {{ $application->student->studentProfile->student_id_no ?? '—' }} · {{ $application->student->studentProfile->contact_number ?? '—' }}</div>@endif
<p class="small mt-2"><b>Statement:</b> {{ $application->statement ?? '—' }}</p>
</div></div>

<div class="card mb-3"><div class="card-body">
<h6 class="fw-bold">Documents ({{ $application->documents->count() }})</h6>
<div class="table-scroll-region" role="region" aria-label="Application documents" tabindex="0">
<table class="table table-sm"><thead><tr><th>Doc</th><th>Status</th><th>Verify</th></tr></thead><tbody>
@forelse($application->documents as $d)<tr>
<td>{{ $d->document_name }}<div class="small text-muted">{{ $d->requirement?->name ?? 'General' }} · {{ $d->file_type }} · <a href="{{ route('documents.download',[$application,$d]) }}">download</a>@if($d->verification_notes)<br>Note: {{ $d->verification_notes }}@endif</div></td>
<td><span class="badge bg-secondary">{{ $d->verification_status }}</span></td>
<td>@if($canAct)<form method="POST" action="{{ route('documents.verify',[$application,$d]) }}" class="d-flex gap-1 document-verification-form">@csrf
<select name="verification_status" class="form-select form-select-sm"><option>pending</option><option {{ $d->verification_status=='verified'?'selected':'' }}>verified</option><option {{ $d->verification_status=='requires_correction'?'selected':'' }}>requires_correction</option><option {{ $d->verification_status=='rejected'?'selected':'' }}>rejected</option></select>
<input name="verification_notes" class="form-control form-control-sm" placeholder="Reason..." value="{{ $d->verification_notes }}">
<button class="btn btn-sm btn-dark">Save</button></form>@else<span class="small text-muted">Read-only</span>@endif</td>
</tr>@empty<tr><td colspan="3" class="text-muted small">No documents uploaded.</td></tr>@endforelse
</tbody></table>
</div>
</div></div>

<div class="card mb-3"><div class="card-body">
<div class="d-flex justify-content-between align-items-center"><h6 class="fw-bold mb-0">Eligibility screening</h6>@if($canAct)<form method="POST" action="{{ route('applications.screen',$application) }}">@csrf<button class="btn btn-sm btn-outline-primary">▶ Run screening</button></form>@endif</div>
@forelse($application->eligibilityResults as $r)<div class="mt-2"><span class="badge bg-dark">{{ $r->result }}</span> <span class="small text-muted">{{ $r->checked_at?->format('M d, Y h:i') }} by {{ $r->checker?->name ?? 'system' }}</span><pre class="small bg-light p-2 rounded mt-1">{{ $r->reasons }}</pre></div>
@empty<p class="small text-muted mt-2">No screening yet. Configure rules in program management, then run screening. Screening never auto-approves — final decision is human.</p>@endforelse
<a href="{{ route('eligibility.show',$application) }}" class="btn btn-sm btn-outline-secondary mt-1">Full screening view</a>
</div></div>

<div class="card mb-3"><div class="card-body">
<h6 class="fw-bold">Evaluations</h6>
@forelse($application->evaluations as $e)<div class="border rounded p-2 small mb-2"><b>{{ $e->evaluator?->name }}</b> · score {{ $e->score ?? '—' }} · <span class="badge bg-secondary">{{ $e->recommendation }}</span><br>{{ $e->evaluation_notes }}<div class="text-muted">{{ $e->evaluated_at?->format('M d, Y h:i') }}</div></div>
@empty<p class="small text-muted">No evaluations yet.</p>@endforelse
@if($application->status === 'for_evaluation')
<form method="POST" action="{{ route('applications.evaluate',$application) }}" class="row g-2 mt-1">@csrf
<div class="col-md-2"><input type="number" name="score" min="0" max="100" class="form-control form-control-sm" placeholder="Score"></div>
<div class="col-md-4"><select name="recommendation" class="form-select form-select-sm"><option value="pending">pending</option><option value="recommended_approval">Recommend APPROVAL</option><option value="recommended_rejection">Recommend REJECTION</option></select></div>
<div class="col-md-6"><input name="evaluation_notes" class="form-control form-control-sm" placeholder="Evaluation notes & recommendation reason..." required></div>
<div class="col-12"><button class="btn btn-sm btn-navy">Save evaluation</button></div>
</form>
@else<p class="small text-muted">Evaluation opens after the form review, document verification, and eligibility screening are complete.</p>@endif
</div></div>
</div>
<div class="col-lg-4">
@include('applications.partials.adfc-office-review')
<a href="{{ route('applications.download', $application) }}" class="btn btn-outline-primary w-100 mb-3"><i class="bi bi-file-earmark-pdf me-2"></i>Download ADFC application form</a>
<div class="card mb-3"><div class="card-body">
<h6 class="fw-bold">Assignment</h6>
<div class="small mb-2">Assigned to: <b>{{ $application->assignee?->name ?? 'Nobody' }}</b></div>
@if(auth()->user()->isAdmin())
<form method="POST" action="{{ route('applications.assign',$application) }}" class="d-flex gap-1">@csrf
<select name="assigned_to" class="form-select form-select-sm"><option value="">— Unassigned —</option>@foreach(\App\Models\User::where('role','staff')->where('status','active')->orderBy('name')->get() as $m)<option value="{{ $m->id }}" {{ $application->assigned_to==$m->id?'selected':'' }}>{{ $m->name }}</option>@endforeach</select>
<button class="btn btn-sm btn-dark">Assign</button></form>
@else
<p class="small text-muted mb-0">Assignment is managed by the administrator.</p>
@endif
</div></div>
<div class="card mb-3"><div class="card-body">
<h6 class="fw-bold">Change status</h6>
<form method="POST" action="{{ route('applications.status',$application) }}">@csrf
<select name="to_status" class="form-select form-select-sm mb-2">
@foreach(array_filter(\App\Models\ScholarshipApplication::TRANSITIONS[$application->status] ?? [], fn ($status) => $status !== 'submitted' && (auth()->user()->isAdmin() || !in_array($status, ['approved','rejected']))) as $s)<option value="{{ $s }}" {{ $application->status==$s?'selected':'' }}>{{ str_replace('_',' ',$s) }}</option>@endforeach
</select>
<textarea name="remarks" class="form-control form-control-sm mb-2" rows="2" placeholder="Remarks / feedback to student..."></textarea>
<button class="btn btn-sm btn-gold w-100" onclick="return confirm('Change status?')">Update status</button>
</form>
@if(!auth()->user()->isAdmin())<div class="form-text small mt-1">Note: final <b>approved / rejected</b> decisions require an administrator.</div>@endif
<div class="small mt-2">Valid next: <b>{{ implode(', ', \App\Models\ScholarshipApplication::TRANSITIONS[$application->status] ?? []) ?: '— (terminal)' }}</b></div>
</div></div>
<div class="card"><div class="card-body"><h6 class="fw-bold">History</h6>@foreach($application->statusHistories as $h)<div class="small border-bottom py-1">{{ $h->from_status ?? '—' }} → <b>{{ $h->to_status }}</b> by {{ $h->changer?->name ?? 'system' }}<br><span class="text-muted">{{ $h->created_at->format('M d, Y h:i') }} {{ $h->remarks?'· '.$h->remarks:'' }}</span></div>@endforeach</div></div>
</div>
</div>
@endsection
