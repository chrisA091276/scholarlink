@extends('layouts.app')
@section('title','Document Verification')
@section('page-title','Document Verification Queue')
@section('content')
<div class="adfc-document-note mb-3"><i class="bi bi-file-earmark-check" aria-hidden="true"></i><div><strong>Verify the ADFC supporting documents</strong><p>Check the ID photo, COR / COE, income evidence, and additional program requirements. Only applications open for review appear here.</p></div></div>
<form class="row g-2 mb-3" method="GET">
<div class="col-md-3"><select class="form-select form-select-sm" name="status" aria-label="Document review status"><option value="pending" @selected(request('status') !== 'requires_correction')>Awaiting verification</option><option value="requires_correction" @selected(request('status') === 'requires_correction')>Returned for correction</option></select></div>
<div class="col-md-5"><input name="search" class="form-control form-control-sm" placeholder="Search document or application no..." value="{{ request('search') }}"></div>
<div class="col-md-2"><div class="form-check mt-1"><input type="checkbox" name="mine" value="1" class="form-check-input" {{ request('mine')?'checked':'' }} onchange="this.form.submit()"><label class="form-check-label small">Assigned to me</label></div></div>
<div class="col-md-2"><button class="btn btn-sm btn-dark w-100">Search</button></div>
</form>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Document</th><th>Application / Student</th><th>Status</th><th>Verify</th></tr></thead><tbody>
@forelse($documents as $d)<tr>
<td><b>{{ $d->document_name }}</b><div class="small text-muted">{{ config('adfc_form.documents.'.$d->document_category) ?: ($d->requirement?->name ?? 'Additional supporting document') }} · {{ $d->file_type }} · <a href="{{ route('documents.download',[$d->application,$d]) }}">download</a>@if($d->verification_notes)<br>Note: {{ $d->verification_notes }}@endif</div></td>
<td class="small">{{ $d->application?->application_no }}<br>{{ $d->application?->applicantName() }} · {{ $d->application?->program?->name }}</td>
<td><span class="badge bg-warning text-dark">{{ $d->verification_status }}</span></td>
<td><form method="POST" action="{{ route('documents.verify',[$d->application,$d]) }}" class="d-flex gap-1 document-verification-form">@csrf
<select name="verification_status" class="form-select form-select-sm"><option>pending</option><option value="verified">verified</option><option value="requires_correction">requires_correction</option><option value="rejected">rejected</option></select>
<input name="verification_notes" class="form-control form-control-sm" placeholder="Reason (required if not verified)">
<button class="btn btn-sm btn-dark">Save</button></form>
<a href="{{ route('applications.show',$d->application) }}" class="btn btn-sm btn-outline-primary mt-1">Full review</a></td></tr>
@empty<tr><td colspan="4" class="text-muted">Queue is clear — nothing awaiting verification.</td></tr>@endforelse
</tbody></table>{{ $documents->links() }}</div></div>
@endsection
