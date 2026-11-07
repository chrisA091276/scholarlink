@extends('layouts.app')
@section('title','Reports')
@section('page-title','Reports & Analytics')
@section('content')
@php $tab = $tab ?? 'applications'; @endphp
<ul class="nav nav-pills mb-3 gap-1">
@foreach(['applications'=>'Applications','students'=>'Students','programs'=>'Programs','eligibility'=>'Eligibility'] as $k=>$label)
<li class="nav-item"><a class="nav-link {{ $tab==$k?'active':'' }}" href="{{ route('reports.index',['tab'=>$k]) }}">{{ $label }}</a></li>
@endforeach
<li class="nav-item ms-auto"><a class="nav-link" href="{{ route('reports.recipients') }}">Recipients →</a></li>
</ul>

@if($tab==='applications')
<div class="row g-2 mb-3">
<div class="col-lg-8"><div class="row g-2">
<div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="small text-muted">Total</div><h4 class="fw-bold">{{ $summary['total'] }}</h4></div></div></div>
<div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="small text-muted">Approved</div><h4 class="fw-bold text-success">{{ $summary['approved'] }}</h4></div></div></div>
<div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="small text-muted">Rejected</div><h4 class="fw-bold text-danger">{{ $summary['rejected'] }}</h4></div></div></div>
<div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="small text-muted">Pending</div><h4 class="fw-bold text-warning">{{ $summary['pending'] }}</h4></div></div></div>
</div></div>
<div class="col-lg-4"><div class="card"><div class="card-body"><h6 class="fw-bold">By status</h6>@foreach($byStatus as $s=>$c)<div class="d-flex justify-content-between small"><span>{{ str_replace('_',' ',$s) }}</span><b>{{ $c }}</b></div>@endforeach</div></div></div>
</div>
<form class="row g-2 mb-3" method="GET"><input type="hidden" name="tab" value="applications">
<div class="col-md-2"><input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}"></div>
<div class="col-md-2"><input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}"></div>
<div class="col-md-3"><select name="scholarship_id" class="form-select form-select-sm"><option value="">All programs</option>@foreach($programs as $p)<option value="{{ $p->id }}" {{ request('scholarship_id')==$p->id?'selected':'' }}>{{ $p->name }}</option>@endforeach</select></div>
<div class="col-md-2"><select name="status" class="form-select form-select-sm"><option value="">All statuses</option>@foreach(['submitted','under_review','for_evaluation','approved','rejected','incomplete_requirements'] as $s)<option {{ request('status')==$s?'selected':'' }}>{{ $s }}</option>@endforeach</select></div>
<div class="col-md-3 d-flex gap-1"><button class="btn btn-sm btn-dark">Filter</button><button name="export" value="csv" class="btn btn-sm btn-outline-success">CSV</button><button name="export" value="pdf" class="btn btn-sm btn-outline-danger">PDF</button><button class="btn btn-sm btn-outline-secondary" onclick="window.print();return false;">Print</button></div>
</form>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>No</th><th>Student</th><th>Program</th><th>Status</th><th>Submitted</th></tr></thead><tbody>
@forelse($rows as $a)<tr><td class="small">{{ $a->application_no }}</td><td>{{ $a->student?->name }}</td><td class="small">{{ $a->program?->name }}</td><td><span class="badge bg-info">{{ str_replace('_',' ',$a->status) }}</span></td><td class="small">{{ $a->submitted_at?->format('M d, Y') ?? '—' }}</td></tr>
@empty<tr><td colspan="5" class="text-muted">No records.</td></tr>@endforelse
</tbody></table>{{ $rows->links() }}</div></div>

@elseif($tab==='students')
<form class="row g-2 mb-3" method="GET"><input type="hidden" name="tab" value="students">
<div class="col-md-5"><input name="search" class="form-control form-control-sm" placeholder="Search ID or name..." value="{{ request('search') }}"></div>
<div class="col-md-4"><input name="course" class="form-control form-control-sm" placeholder="Course e.g. BSIT" value="{{ request('course') }}"></div>
<div class="col-md-3 d-flex gap-1"><button class="btn btn-sm btn-dark">Filter</button><button name="export" value="csv" class="btn btn-sm btn-outline-success">CSV</button><button name="export" value="pdf" class="btn btn-sm btn-outline-danger">PDF</button></div>
</form>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Student ID</th><th>Name</th><th>Course</th><th>Enrollment</th></tr></thead><tbody>
@forelse($rows as $p)<tr><td><b>{{ $p->student_id_no }}</b></td><td>{{ $p->full_name }}<div class="small text-muted">{{ $p->user?->email }}</div></td><td>{{ $p->course ?? '—' }} · {{ $p->year_level ?? '' }}</td><td>{{ $p->enrollment_status ?? '—' }}</td></tr>
@empty<tr><td colspan="4" class="text-muted">No records.</td></tr>@endforelse
</tbody></table>{{ $rows->links() }}</div></div>

@elseif($tab==='programs')
<form class="row g-2 mb-3" method="GET"><input type="hidden" name="tab" value="programs">
<div class="col-md-9"><select name="status" class="form-select form-select-sm"><option value="">All statuses</option>@foreach(['draft','published','closed','archived'] as $s)<option {{ request('status')==$s?'selected':'' }}>{{ $s }}</option>@endforeach</select></div>
<div class="col-md-3 d-flex gap-1"><button class="btn btn-sm btn-dark">Filter</button><button name="export" value="csv" class="btn btn-sm btn-outline-success">CSV</button><button name="export" value="pdf" class="btn btn-sm btn-outline-danger">PDF</button></div>
</form>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Code</th><th>Program</th><th>Status</th><th>Slots</th><th>Applications</th><th>Awards</th><th>Deadline</th></tr></thead><tbody>
@forelse($rows as $p)<tr><td><b>{{ $p->code }}</b></td><td>{{ $p->name }}</td><td><span class="badge bg-secondary">{{ $p->status }}</span></td><td>{{ $p->slots ?? '—' }}</td><td>{{ $p->applications_count }}</td><td>{{ $p->awards_count }}</td><td class="small">{{ $p->deadline?->format('M d, Y') ?? '—' }}</td></tr>
@empty<tr><td colspan="7" class="text-muted">No records.</td></tr>@endforelse
</tbody></table>{{ $rows->links() }}</div></div>

@elseif($tab==='eligibility')
<form class="row g-2 mb-3" method="GET"><input type="hidden" name="tab" value="eligibility">
<div class="col-md-4"><select name="result" class="form-select form-select-sm"><option value="">All results</option>@foreach(['eligible','manual_review','not_eligible'] as $r)<option {{ request('result')==$r?'selected':'' }} value="{{ $r }}">{{ str_replace('_',' ',$r) }}</option>@endforeach</select></div>
<div class="col-md-5"><select name="scholarship_id" class="form-select form-select-sm"><option value="">All programs</option>@foreach($programs as $p)<option value="{{ $p->id }}" {{ request('scholarship_id')==$p->id?'selected':'' }}>{{ $p->name }}</option>@endforeach</select></div>
<div class="col-md-3 d-flex gap-1"><button class="btn btn-sm btn-dark">Filter</button><button name="export" value="csv" class="btn btn-sm btn-outline-success">CSV</button><button name="export" value="pdf" class="btn btn-sm btn-outline-danger">PDF</button></div>
</form>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Application</th><th>Student</th><th>Result</th><th>Checked</th></tr></thead><tbody>
@forelse($rows as $er)<tr><td class="small">{{ $er->application?->application_no }}<br>{{ $er->application?->program?->name }}</td><td>{{ $er->application?->student?->name }}</td><td><span class="badge {{ $er->result=='eligible'?'bg-success':($er->result=='not_eligible'?'bg-danger':'bg-warning text-dark') }}">{{ $er->result }}</span></td><td class="small">{{ $er->checked_at?->format('M d, Y') ?? '—' }}</td></tr>
@empty<tr><td colspan="4" class="text-muted">No screening results.</td></tr>@endforelse
</tbody></table>{{ $rows->links() }}</div></div>
@endif
@endsection
