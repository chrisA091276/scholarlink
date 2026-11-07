@extends('layouts.app')
@section('title','Audit Logs')
@section('page-title','Audit Trail')
@section('content')
<form class="row g-2 mb-3" method="GET"><div class="col-md-8"><input name="search" class="form-control form-control-sm" placeholder="Search action or description..." value="{{ request('search') }}"></div><div class="col-md-2"><input name="action" class="form-control form-control-sm" placeholder="action" value="{{ request('action') }}"></div><div class="col-md-2"><button class="btn btn-sm btn-dark w-100">Search</button></div></form>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>When</th><th>User</th><th>Action</th><th>Description</th><th>IP</th></tr></thead><tbody>
@forelse($logs as $l)<tr><td class="small text-nowrap">{{ $l->created_at->format('M d, H:i') }}</td><td class="small">{{ $l->user?->name ?? 'system' }}</td><td><span class="badge bg-dark">{{ $l->action }}</span></td><td class="small">{{ $l->description }}</td><td class="small">{{ $l->ip_address }}</td></tr>
@empty<tr><td colspan="5" class="text-muted">No logs.</td></tr>@endforelse
</tbody></table>{{ $logs->links() }}</div></div>
<p class="small text-muted mt-2">Audit records are read-only — no edit/delete in the UI.</p>
@endsection
