@extends('layouts.app')
@section('title','Renewals')
@section('page-title','Renewal Applications')
@section('content')
<form class="row g-2 mb-3" method="GET"><div class="col-md-10"><select name="status" class="form-select form-select-sm"><option value="">All statuses</option>@foreach(['draft','submitted','under_review','approved','rejected'] as $s)<option {{ request('status')==$s?'selected':'' }}>{{ $s }}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-sm btn-dark w-100">Filter</button></div></form>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>No</th><th>Award</th><th>Student</th><th>Year</th><th>Status</th><th>Decide</th></tr></thead><tbody>
@forelse($renewals as $r)<tr><td>{{ $r->renewal_no }}</td><td class="small">{{ $r->award?->award_no }}</td><td>{{ $r->student?->name }}</td><td>{{ $r->academic_year }}</td><td><span class="badge bg-secondary">{{ $r->status }}</span></td>
<td>@if(auth()->user()->isAdmin())<form method="POST" action="{{ route('renewals.decide',$r) }}" class="d-flex gap-1">@csrf<select name="status" class="form-select form-select-sm"><option>under_review</option><option>approved</option><option>rejected</option></select><input name="remarks" class="form-control form-control-sm" placeholder="Remarks"><button class="btn btn-sm btn-dark">Go</button></form>@else<span class="small text-muted">Admin decides</span>@endif</td></tr>
@empty<tr><td colspan="6" class="text-muted">No renewals.</td></tr>@endforelse
</tbody></table>{{ $renewals->links() }}</div></div>
@endsection
