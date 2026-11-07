@extends('layouts.app')
@section('title','Record: '.$student->name)
@section('page-title','Student Record')
@section('content')
<div class="row g-3">
<div class="col-lg-5">
<div class="card mb-3"><div class="card-body">
<h6 class="fw-bold">{{ $student->studentProfile->full_name ?? $student->name }}</h6>
<div class="small text-muted">{{ $student->email }} · ID {{ $student->studentProfile->student_id_no ?? '—' }}</div>
<hr>
<dl class="row small mb-0">
<dt class="col-5">Course</dt><dd class="col-7">{{ $student->studentProfile->course ?? '—' }}</dd>
<dt class="col-5">Year level</dt><dd class="col-7">{{ $student->studentProfile->year_level ?? '—' }}</dd>
<dt class="col-5">Enrollment</dt><dd class="col-7">{{ $student->studentProfile->enrollment_status ?? '—' }}</dd>
<dt class="col-5">Contact</dt><dd class="col-7">{{ $student->studentProfile->contact_number ?? '—' }}</dd>
<dt class="col-5">Address</dt><dd class="col-7">{{ $student->studentProfile->address ?? '—' }}</dd>
</dl>
</div></div>
@if($student->awards->count())
<div class="card"><div class="card-body"><h6 class="fw-bold">Awards</h6>
@foreach($student->awards as $w)<div class="small"><i class="bi bi-trophy-fill ico-gold me-1"></i>{{ $w->award_no }} — {{ $w->program?->name }} (<span class="badge bg-success">{{ $w->recipient_status }}</span>)</div>@endforeach
</div></div>
@endif
</div>
<div class="col-lg-7">
<div class="card"><div class="card-body">
<h6 class="fw-bold">Application history ({{ $student->applications->count() }})</h6>
<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>No</th><th>Program</th><th>Status</th><th>Docs</th><th></th></tr></thead><tbody>
@forelse($student->applications as $a)<tr>
<td class="small">{{ $a->application_no }}</td><td class="small">{{ $a->program?->name }}</td>
<td><span class="badge bg-info badge-status">{{ str_replace('_',' ',$a->status) }}</span></td>
<td class="small">{{ $a->documents->count() }}</td>
<td><a href="{{ route('applications.show',$a) }}" class="btn btn-sm btn-outline-primary">Open</a></td></tr>
@empty<tr><td colspan="5" class="text-muted small">No applications.</td></tr>@endforelse
</tbody></table></div>
</div></div>
</div>
</div>
@endsection
