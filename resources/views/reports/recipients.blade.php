@extends('layouts.app')
@section('title','Recipients')
@section('page-title','Scholarship Recipients Report')
@section('content')
<div class="mb-2 d-flex gap-2"><button class="btn btn-sm btn-outline-secondary" onclick="window.print();return false;">Print / PDF</button><a href="{{ route('reports.index') }}" class="btn btn-sm btn-dark">Back to reports</a></div>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Award</th><th>Student</th><th>Program</th><th>Status</th><th>Start</th></tr></thead><tbody>
@forelse($awards as $w)<tr><td>{{ $w->award_no }}</td><td>{{ $w->student?->name }}</td><td>{{ $w->program?->name }}</td><td>{{ $w->recipient_status }}</td><td>{{ $w->start_date?->format('M d, Y') }}</td></tr>
@empty<tr><td colspan="5" class="text-muted">No recipients.</td></tr>@endforelse
</tbody></table>{{ $awards->links() }}</div></div>
@endsection
