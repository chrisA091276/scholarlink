@extends('layouts.app')
@section('title','Evaluations & Decisions')
@section('page-title','Evaluations & Final Decisions')
@section('content')
<div class="card mb-3"><div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-2"><h6 class="fw-bold mb-0">Awaiting final decision ({{ $pending->total() }})</h6></div>
<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Application</th><th>Student</th><th>Recommendation</th><th></th></tr></thead><tbody>
@forelse($pending as $a)<tr>
<td class="small">{{ $a->application_no }}<br>{{ $a->program?->name }}</td>
<td>{{ $a->student?->name }}</td>
<td><span class="badge {{ $a->status=='recommended_approval'?'bg-success':'bg-danger' }}">{{ str_replace('_',' ',$a->status) }}</span></td>
<td><a href="{{ route('applications.show',$a) }}" class="btn btn-sm btn-gold">Decide</a></td></tr>
@empty<tr><td colspan="4" class="text-muted">Nothing awaiting decision.</td></tr>@endforelse
</tbody></table>{{ $pending->links() }}</div>
</div></div>
<div class="card"><div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-2"><h6 class="fw-bold mb-0">Evaluation history</h6>
<form class="d-flex gap-1" method="GET"><select name="recommendation" class="form-select form-select-sm"><option value="">All</option>@foreach(['pending','recommended_approval','recommended_rejection'] as $r)<option {{ request('recommendation')==$r?'selected':'' }}>{{ $r }}</option>@endforeach</select><button class="btn btn-sm btn-dark">Go</button></form></div>
<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Application</th><th>Evaluator</th><th>Score</th><th>Recommendation</th><th>Notes</th></tr></thead><tbody>
@forelse($evaluations as $e)<tr>
<td class="small"><a href="{{ route('applications.show',$e->application) }}">{{ $e->application?->application_no }}</a><br>{{ $e->application?->student?->name }}</td>
<td>{{ $e->evaluator?->name }}<div class="small text-muted">{{ $e->evaluated_at?->format('M d, Y') }}</div></td>
<td>{{ $e->score ?? '—' }}</td>
<td><span class="badge bg-secondary">{{ $e->recommendation }}</span></td>
<td class="small">{{ \Illuminate\Support\Str::limit($e->evaluation_notes,100) }}</td></tr>
@empty<tr><td colspan="5" class="text-muted">No evaluations recorded.</td></tr>@endforelse
</tbody></table>{{ $evaluations->links() }}</div>
</div></div>
@endsection
