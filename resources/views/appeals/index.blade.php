@extends('layouts.app')
@section('title','Scholarship Appeals')
@section('page-title','Scholarship Appeals')
@section('content')
<div class="d-flex flex-wrap justify-content-between gap-3 mb-3"><div><h1 class="h4">Reinstatement appeals</h1><p class="text-muted">Review student explanations and record a decision. Approval restores active scholarship status.</p></div></div>
<form method="GET" class="d-flex flex-wrap gap-2 mb-3"><label for="appealStatusFilter" class="visually-hidden">Appeal status</label><select id="appealStatusFilter" name="status" class="form-select" style="max-width:240px"><option value="">All appeals</option>@foreach(\App\Models\ScholarshipAppeal::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>@endforeach</select><button class="btn btn-dark">Filter</button></form>
<div class="card"><div class="card-body table-responsive"><table class="table align-middle">
  <thead><tr><th>Appeal</th><th>Student</th><th>Scholarship</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
  <tbody>@forelse($appeals as $appeal)<tr><td>{{ $appeal->appeal_no }}</td><td>{{ $appeal->student?->name }}</td><td>{{ $appeal->award?->program?->name }}</td><td><span class="badge {{ $appeal->isPending() ? 'bg-primary' : 'bg-secondary' }}">{{ ucfirst(str_replace('_', ' ', $appeal->status)) }}</span></td><td>{{ $appeal->created_at->format('M d, Y') }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('appeals.show', $appeal) }}">Review</a></td></tr>@empty<tr><td colspan="6" class="text-muted">No appeals match this filter.</td></tr>@endforelse</tbody>
</table>{{ $appeals->links() }}</div></div>
@endsection
