@extends('layouts.app')
@section('title','Student Records')
@section('page-title','Student Records')
@section('content')
<form class="row g-2 mb-3" method="GET">
<div class="col-md-6"><input name="search" class="form-control form-control-sm" placeholder="Search ID, name, or email..." value="{{ request('search') }}"></div>
<div class="col-md-2"><input name="course" class="form-control form-control-sm" placeholder="Course" value="{{ request('course') }}"></div>
<div class="col-md-2"><select name="year_level" class="form-select form-select-sm"><option value="">All years</option>@foreach(['1st','2nd','3rd','4th','5th'] as $y)<option {{ request('year_level')==$y?'selected':'' }}>{{ $y }}</option>@endforeach</select></div>
<div class="col-md-2"><button class="btn btn-sm btn-dark w-100">Search</button></div>
</form>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Student ID</th><th>Name</th><th>Course / Year</th><th>Applications</th><th></th></tr></thead><tbody>
@forelse($students as $p)<tr>
<td><b>{{ $p->student_id_no }}</b><div class="small text-muted">{{ $p->user?->email }}</div></td>
<td>{{ $p->full_name }}</td>
<td class="small">{{ $p->course ?? '—' }} · {{ $p->year_level ?? '—' }}</td>

<td><span class="badge bg-info">{{ $p->user?->applications_count ?? $p->user->applications->count() }}</span></td>
<td><a href="{{ route('students.show',$p->user) }}" class="btn btn-sm btn-outline-primary">Record</a></td></tr>
@empty<tr><td colspan="5" class="text-muted">No student records.</td></tr>@endforelse
</tbody></table>{{ $students->links() }}</div></div>
@endsection
