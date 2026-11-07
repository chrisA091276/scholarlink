@extends('layouts.app')
@section('title','Eligibility Criteria')
@section('page-title','Eligibility Criteria')
@section('content')
<form class="row g-2 mb-3" method="GET">
<div class="col-md-10"><select name="scholarship_id" class="form-select form-select-sm"><option value="">All programs</option>@foreach($programs as $p)<option value="{{ $p->id }}" {{ request('scholarship_id')==$p->id?'selected':'' }}>{{ $p->name }}</option>@endforeach</select></div>
<div class="col-md-2"><button class="btn btn-sm btn-dark w-100">Filter</button></div>
</form>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Program</th><th>Rule</th><th>Condition</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($rules as $r)<tr>
<td class="small">{{ $r->program?->name }}</td>
<td><b>{{ $r->rule_label }}</b><div class="small text-muted">{{ $r->rule_key }}</div></td>
<td class="small"><code>{{ $r->operator }} {{ $r->rule_value }}</code></td>
<td>@if($r->is_active)<span class="badge bg-success">active</span>@else<span class="badge bg-secondary">off</span>@endif</td>
<td><a href="{{ route('scholarships.show',$r->program) }}" class="btn btn-sm btn-outline-primary">Manage</a></td></tr>
@empty<tr><td colspan="5" class="text-muted">No eligibility rules configured. Open a program to add some.</td></tr>@endforelse
</tbody></table>{{ $rules->links() }}</div></div>
<p class="small text-muted mt-2">Example conditions: <code>enrollment_status = enrolled,continuing</code> · <code>course = all</code> · <code>year_level = all</code>.</p>
@endsection
