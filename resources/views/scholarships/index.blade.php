@extends('layouts.app')
@section('title','Manage Programs')
@section('page-title','Scholarship Program Management')
@section('content')
<div class="page-toolbar mb-3">
<form class="filter-toolbar" method="GET"><input name="search" class="form-control form-control-sm" placeholder="Search..." aria-label="Search programs" value="{{ request('search') }}"><select name="status" class="form-select form-select-sm" aria-label="Filter by status"><option value="">All</option>@foreach(['draft','published','closed','archived'] as $s)<option {{ request('status')==$s?'selected':'' }}>{{ $s }}</option>@endforeach</select><button class="btn btn-sm btn-dark">Go</button></form>
<a href="{{ route('scholarships.create') }}" class="btn btn-gold btn-sm">+ New program</a>
</div>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Deadline</th><th>Apps</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($programs as $p)<tr>
<td><b>{{ $p->code }}</b></td><td>{{ $p->name }}</td><td>{{ $p->category }}</td><td class="small">{{ $p->deadline?->format('M d, Y') ?? '—' }}</td><td>{{ $p->applications_count }}</td>
<td><span class="badge bg-secondary">{{ $p->status }}</span></td>
<td class="text-nowrap"><a href="{{ route('scholarships.show',$p) }}" class="btn btn-sm btn-outline-primary">Manage</a> <a href="{{ route('scholarships.edit',$p) }}" class="btn btn-sm btn-outline-secondary">Edit</a></td>
</tr>@empty<tr><td colspan="7" class="text-muted">No programs. <a href="{{ route('scholarships.create') }}">Create one</a>.</td></tr>@endforelse
</tbody></table>{{ $programs->links() }}</div></div>
@endsection
