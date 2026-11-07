@extends('layouts.app')
@section('title','Eligibility')
@section('page-title','Eligibility Screening — '.$application->application_no)
@section('content')
<div class="card"><div class="card-body">
<div class="small mb-2">Student: <b>{{ $application->student?->name }}</b> · Program: <b>{{ $application->program?->name }}</b></div>
<h6 class="fw-bold">Active rules</h6>
<div class="table-scroll-region" role="region" aria-label="Eligibility conditions" tabindex="0">
<table class="table table-sm"><thead><tr><th>Rule</th><th>Condition</th></tr></thead><tbody>
@forelse($application->program->eligibilityRules->where('is_active',true) as $r)<tr><td>{{ $r->rule_label }}</td><td class="small">{{ $r->rule_key }} {{ $r->operator }} {{ $r->rule_value }}</td></tr>
@empty<tr><td colspan="2" class="text-muted">No active rules.</td></tr>@endforelse
</tbody></table></div>
<h6 class="fw-bold mt-3">Results</h6>
@forelse($application->eligibilityResults as $res)<div class="border rounded p-2 mb-2"><span class="badge {{ $res->result=='eligible'?'bg-success':($res->result=='not_eligible'?'bg-danger':'bg-warning text-dark') }}">{{ $res->result }}</span> <span class="small text-muted">{{ $res->checked_at?->format('M d, Y h:i') }}</span><pre class="small bg-light p-2 rounded mt-1 mb-0">{{ $res->reasons }}</pre></div>
@empty<p class="text-muted small">No screening run yet.</p>@endforelse
<a href="{{ route('applications.show',$application) }}" class="btn btn-sm btn-dark">Back to review</a>
</div></div>
@endsection
