@extends('layouts.app')
@section('title','Award '.$award->award_no)
@section('page-title','Award '.$award->award_no)
@section('content')
<div class="row g-3">
<div class="col-lg-7"><div class="card"><div class="card-body">
<h6 class="fw-bold">{{ $award->student?->name }} — {{ $award->program?->name }}</h6>
<div class="small">Benefits: {{ $award->benefits ?? '—' }}</div>
<div class="small">Period: {{ $award->start_date?->format('M d, Y') }} to {{ $award->end_date?->format('M d, Y') ?? '—' }} · Status: <span class="badge {{ $award->recipient_status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($award->recipient_status) }}</span></div>
@include('awards.status-form')
</div></div>
<div class="card mt-3"><div class="card-body"><h6 class="fw-bold">Renewal applications ({{ $award->renewals->count() }})</h6>
<div class="table-scroll-region" role="region" aria-label="Award renewals" tabindex="0">
<table class="table table-sm"><thead><tr><th>No</th><th>Year</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($award->renewals as $r)<tr><td>{{ $r->renewal_no }}</td><td>{{ $r->academic_year }}</td><td><span class="badge bg-secondary">{{ $r->status }}</span></td>
<td>@if(auth()->user()->isAdmin())<form method="POST" action="{{ route('renewals.decide',$r) }}" class="d-flex gap-1">@csrf<select name="status" class="form-select form-select-sm"><option>under_review</option><option>approved</option><option>rejected</option></select><input name="remarks" class="form-control form-control-sm" aria-label="Decision remarks" placeholder="Reason for decision"><button class="btn btn-sm btn-dark">Record decision</button></form>@else<span class="small text-muted">Admin decides</span>@endif</td></tr>
@empty<tr><td colspan="4" class="text-muted small">No renewals.</td></tr>@endforelse
</tbody></table></div></div></div>
</div>
<div class="col-lg-5"><div class="card"><div class="card-body"><h6 class="fw-bold">Application</h6><div class="small">{{ $award->application?->application_no }} — {{ $award->application?->status }}</div><a href="{{ route('applications.show',$award->application) }}" class="btn btn-sm btn-outline-primary mt-2">View application</a></div></div></div>
</div>
@endsection
