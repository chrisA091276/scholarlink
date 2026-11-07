@extends('layouts.app')
@section('title','Requirements Management')
@section('page-title','Requirements Management')
@section('content')
<form class="row g-2 mb-3" method="GET">
<div class="col-md-10"><select name="scholarship_id" class="form-select form-select-sm"><option value="">All programs</option>@foreach($programs as $p)<option value="{{ $p->id }}" {{ request('scholarship_id')==$p->id?'selected':'' }}>{{ $p->name }}</option>@endforeach</select></div>
<div class="col-md-2"><button class="btn btn-sm btn-dark w-100">Filter</button></div>
</form>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Program</th><th>Requirement</th><th>Required</th><th>Allowed types</th><th></th></tr></thead><tbody>
@forelse($requirements as $r)<tr>
<td class="small">{{ $r->program?->name }}</td>
<td><b>{{ $r->name }}</b><div class="small text-muted">{{ $r->description }}</div></td>
<td>@if($r->is_required)<span class="badge bg-danger">required</span>@else<span class="badge bg-secondary">optional</span>@endif</td>
<td class="small">{{ $r->allowed_types }} · {{ $r->max_size_kb/1024 }}MB</td>
<td><a href="{{ route('scholarships.show',$r->program) }}" class="btn btn-sm btn-outline-primary">Manage</a></td></tr>
@empty<tr><td colspan="5" class="text-muted">No requirements configured. Open a program to add some.</td></tr>@endforelse
</tbody></table>{{ $requirements->links() }}</div></div>
@endsection
