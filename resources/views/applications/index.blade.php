@extends('layouts.app')
@section('title','Applications')
@section('page-title','Application Review Queue')
@section('content')
<div class="adfc-document-note mb-3"><i class="bi bi-clipboard2-check" aria-hidden="true"></i><div><strong>ADFC application review</strong><p>Review the saved applicant, school, family, assistance, and consent details before recording a recommendation.</p></div></div>
<form class="row g-2 mb-3" method="GET">
<div class="col-md-4"><label for="queue-stage" class="form-label small">Workflow stage</label><select id="queue-stage" name="stage" class="form-select form-select-sm"><option value="">All stages</option>@foreach(['active'=>'All active applications','review'=>'Office review and corrections','decision'=>'Ready for administrator decision'] as $value => $label)<option value="{{ $value }}" @selected(request('stage') === $value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-4"><label for="queue-assistance" class="form-label small">Funding declaration</label><select id="queue-assistance" name="assistance" class="form-select form-select-sm"><option value="">All declarations</option><option value="yes" @selected(request('assistance') === 'yes')>Other assistance declared</option></select></div>
<div class="col-md-4 d-flex align-items-end"><a href="{{ route('applications.index') }}" class="btn btn-sm btn-outline-secondary">Clear filters</a></div>
<div class="col-md-3"><input name="search" class="form-control form-control-sm" placeholder="Search no. or student..." value="{{ request('search') }}"></div>
<div class="col-md-2"><select name="status" class="form-select form-select-sm"><option value="">All statuses</option>@foreach(['draft','submitted','under_review','incomplete_requirements','for_evaluation','recommended_approval','recommended_rejection','approved','rejected','withdrawn'] as $s)<option {{ request('status')==$s?'selected':'' }} value="{{ $s }}">{{ str_replace('_',' ',$s) }}</option>@endforeach</select></div>
<div class="col-md-3"><select name="scholarship_id" class="form-select form-select-sm"><option value="">All programs</option>@foreach($programs as $p)<option value="{{ $p->id }}" {{ request('scholarship_id')==$p->id?'selected':'' }}>{{ $p->name }}</option>@endforeach</select></div>
<div class="col-md-2"><select name="assigned_to" class="form-select form-select-sm"><option value="">All assignees</option><option value="unassigned" {{ request('assigned_to')=='unassigned'?'selected':'' }}>Unassigned</option>@foreach($staff as $m)<option value="{{ $m->id }}" {{ request('assigned_to')==$m->id?'selected':'' }}>{{ $m->name }}</option>@endforeach</select></div>
<div class="col-md-2 d-flex gap-1"><button class="btn btn-sm btn-dark flex-fill">Filter</button>@if(auth()->user()->isStaff())<a href="{{ route('applications.index',['mine'=>1]) }}" class="btn btn-sm btn-outline-primary">Mine</a>@endif</div>
</form>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm table-hover"><thead><tr><th>No</th><th>Student</th><th>Program</th><th>Status</th><th>Assignee</th><th>Submitted</th><th></th></tr></thead><tbody>
@forelse($applications as $a)<tr><td>{{ $a->application_no }}</td><td>{{ $a->applicantName() }}<div class="small text-muted">{{ $a->student?->email }}</div></td><td class="small">{{ $a->program?->name }}</td><td><span class="badge bg-info badge-status">{{ str_replace('_',' ',$a->status) }}</span></td>
<td class="small">@if(auth()->user()->isAdmin())
<form method="POST" action="{{ route('applications.assign',$a) }}" class="d-flex gap-1">@csrf
<select name="assigned_to" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">—</option>@foreach($staff as $m)<option value="{{ $m->id }}" {{ $a->assigned_to==$m->id?'selected':'' }}>{{ $m->name }}</option>@endforeach</select></form>
@else{{ $a->assignee?->name ?? '—' }}@endif</td>
<td class="small">{{ $a->submitted_at?->format('M d, Y') ?? '—' }}</td><td><a href="{{ route('applications.show',$a) }}" class="btn btn-sm btn-outline-primary">Review</a></td></tr>
@empty<tr><td colspan="7" class="text-muted">No applications.</td></tr>@endforelse
</tbody></table>{{ $applications->links() }}</div></div>
@endsection
