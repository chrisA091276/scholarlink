@extends('layouts.app')
@section('title','Recommendations')
@section('page-title','My Recommendations')
@section('content')
<form class="row g-2 mb-3" method="GET">
<div class="col-md-8"><select name="recommendation" class="form-select form-select-sm"><option value="">All recommendations</option>@foreach(['pending','recommended_approval','recommended_rejection'] as $r)<option {{ request('recommendation')==$r?'selected':'' }}>{{ $r }}</option>@endforeach</select></div>
<div class="col-md-2"><div class="form-check mt-1"><input type="checkbox" name="mine" value="1" class="form-check-input" {{ request('mine')?'checked':'' }} onchange="this.form.submit()"><label class="form-check-label small">Mine only</label></div></div>
<div class="col-md-2"><button class="btn btn-sm btn-dark w-100">Filter</button></div>
</form>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Application</th><th>Evaluator</th><th>Score</th><th>Recommendation</th><th>Forwarded</th><th></th></tr></thead><tbody>
@forelse($evaluations as $e)<tr>
<td class="small"><b>{{ $e->application?->application_no }}</b><br>{{ $e->application?->student?->name }} · {{ $e->application?->program?->name }}</td>
<td>{{ $e->evaluator?->name }}<div class="small text-muted">{{ $e->evaluated_at?->format('M d, Y h:i') }}</div></td>
<td>{{ $e->score ?? '—' }}</td>
<td><span class="badge {{ $e->recommendation=='recommended_approval'?'bg-success':($e->recommendation=='recommended_rejection'?'bg-danger':'bg-secondary') }}">{{ $e->recommendation }}</span></td>
<td class="small">{{ $e->application && in_array($e->application->status,['approved','rejected']) ? 'Decided: '.$e->application->status : 'Awaiting admin' }}</td>
<td><a href="{{ route('applications.show',$e->application) }}" class="btn btn-sm btn-outline-primary">Open</a></td></tr>
@empty<tr><td colspan="6" class="text-muted">No recommendations yet.</td></tr>@endforelse
</tbody></table>{{ $evaluations->links() }}</div></div>
@endsection
