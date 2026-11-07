@extends('layouts.app')
@section('title','Users')
@section('page-title','User Management')
@section('content')
<div class="page-toolbar mb-3">
<form class="filter-toolbar" method="GET"><input name="search" class="form-control form-control-sm" placeholder="Search..." aria-label="Search users" value="{{ request('search') }}"><select name="role" class="form-select form-select-sm" aria-label="Filter by role"><option value="">All roles</option>@foreach(['admin','staff','student'] as $r)<option {{ request('role')==$r?'selected':'' }}>{{ $r }}</option>@endforeach</select><select name="status" class="form-select form-select-sm" aria-label="Filter by status"><option value="">All</option><option value="active" {{ request('status')=='active'?'selected':'' }}>active</option><option value="inactive" {{ request('status')=='inactive'?'selected':'' }}>inactive</option></select><button class="btn btn-sm btn-dark">Go</button></form>
<a href="{{ route('users.create') }}" class="btn btn-gold btn-sm">+ New user</a>
</div>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($users as $u)<tr><td>{{ $u->name }}</td><td class="small">{{ $u->email }}</td><td><span class="badge bg-dark">{{ $u->role }}</span></td><td><span class="badge {{ $u->status=='active'?'bg-success':'bg-danger' }}">{{ $u->status }}</span></td>
<td class="text-nowrap"><a href="{{ route('users.edit',$u) }}" class="btn btn-sm btn-outline-primary">Edit</a>
<form method="POST" action="{{ route('users.toggle',$u) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-warning">{{ $u->status=='active'?'Deactivate':'Activate' }}</button></form>
<form method="POST" action="{{ route('users.reset-link',$u) }}" class="d-inline" onsubmit="return confirm('Email a password reset link to {{ $u->email }}?')">@csrf<button class="btn btn-sm btn-outline-secondary" title="Send password reset link"><i class="bi bi-key"></i></button></form></td></tr>
@empty<tr><td colspan="5" class="text-muted">No users.</td></tr>@endforelse
</tbody></table>{{ $users->links() }}</div></div>
@endsection
