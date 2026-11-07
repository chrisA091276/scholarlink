@extends('layouts.app')
@section('title','Eligibility Screening')
@section('page-title','Eligibility Screening Center')
@section('content')
<form class="row g-2 mb-3" method="GET">
<div class="col-md-6"><input name="search" class="form-control form-control-sm" placeholder="Search application no. or student..." value="{{ request('search') }}"></div>
<div class="col-md-4"><select name="result" class="form-select form-select-sm"><option value="">All results</option>@foreach(['eligible','manual_review','not_eligible'] as $r)<option {{ request('result')==$r?'selected':'' }} value="{{ $r }}">{{ str_replace('_',' ',$r) }}</option>@endforeach</select></div>
<div class="col-md-2"><button class="btn btn-sm btn-dark w-100">Filter</button></div>
</form>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Application</th><th>Student</th><th>Enrollment</th><th>Latest result</th><th></th></tr></thead><tbody>
@forelse($applications as $a)<tr>
<td class="small"><b>{{ $a->application_no }}</b><br>{{ $a->program?->name }}</td>
<td>{{ $a->student?->name }}<div class="small text-muted">{{ $a->student?->studentProfile->course ?? '' }} {{ $a->student?->studentProfile->year_level ?? '' }}</div></td>
<td class="small">{{ $a->enrollment_status ?? '—' }}</td>
<td>@if($a->latestEligibilityResult)<span class="badge {{ $a->latestEligibilityResult->result=='eligible'?'bg-success':($a->latestEligibilityResult->result=='not_eligible'?'bg-danger':'bg-warning text-dark') }}">{{ $a->latestEligibilityResult->result }}</span><div class="small text-muted">{{ $a->latestEligibilityResult->checked_at?->format('M d, Y') }}</div>@else<span class="badge bg-secondary">not screened</span>@endif</td>
<td class="text-nowrap"><form method="POST" action="{{ route('applications.screen',$a) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-primary">Run</button></form> <a href="{{ route('eligibility.show',$a) }}" class="btn btn-sm btn-outline-secondary">Details</a></td></tr>
@empty<tr><td colspan="5" class="text-muted">No applications.</td></tr>@endforelse
</tbody></table>{{ $applications->links() }}</div></div>
<p class="small text-muted mt-2">Screening supports — never replaces — human decisions. Final approval stays with authorized personnel.</p>
@endsection
