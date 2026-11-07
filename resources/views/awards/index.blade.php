@extends('layouts.app')
@section('title','Awards')
@section('page-title','Scholarship Awards')
@section('content')
<form class="row g-2 mb-3" method="GET"><div class="col-md-6"><input name="search" class="form-control form-control-sm" placeholder="Search award no. or student..." value="{{ request('search') }}"></div><div class="col-md-4"><select name="status" class="form-select form-select-sm"><option value="">All</option>@foreach(\App\Models\ScholarshipAward::STATUSES as $s)<option {{ request('status')==$s?'selected':'' }}>{{ $s }}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-sm btn-dark w-100">Filter</button></div></form>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Award</th><th>Student</th><th>Program</th><th>Period</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($awards as $w)<tr><td>{{ $w->award_no }}</td><td>{{ $w->student?->name }}</td><td class="small">{{ $w->program?->name }}</td><td class="small">{{ $w->start_date?->format('M Y') }} – {{ $w->end_date?->format('M Y') ?? '...' }}</td><td><span class="badge {{ $w->recipient_status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($w->recipient_status) }}</span></td><td><a href="{{ route('awards.show',$w) }}" class="btn btn-sm btn-outline-primary">Open</a></td></tr>
@empty<tr><td colspan="6" class="text-muted">No awards yet — approve an application to create one.</td></tr>@endforelse
</tbody></table>{{ $awards->links() }}</div></div>
@endsection
