@extends('layouts.app')
@section('title','Appeal '.$appeal->appeal_no)
@section('page-title','Review Scholarship Appeal')
@section('content')
<a href="{{ route('appeals.index') }}" class="btn btn-sm btn-outline-secondary mb-3">Back to appeals</a>
<div class="row g-4">
  <div class="col-lg-7"><div class="card"><div class="card-body">
    <h1 class="h5">{{ $appeal->appeal_no }}</h1><p>{{ $appeal->student?->name }} &middot; {{ $appeal->award?->program?->name }}</p>
    <span class="badge bg-primary mb-3">{{ ucfirst(str_replace('_', ' ', $appeal->status)) }}</span>
    <p class="small text-muted">Submitted {{ $appeal->created_at->format('M d, Y g:i A') }}</p>
    <h2 class="h6">Reason for {{ $appeal->award_status === 'removed' ? 'removal' : 'suspension' }} at submission</h2><p class="text-break" style="white-space:pre-line">{{ $appeal->status_reason ?: 'No reason was recorded for this earlier status.' }}</p>
    <h2 class="h6">Student's appeal</h2><p class="text-break" style="white-space:pre-line">{{ $appeal->statement }}</p>
    @if($appeal->decision_reason)<div class="alert alert-info text-break" style="white-space:pre-line"><strong>Review response:</strong> {{ $appeal->decision_reason }}</div>@endif
    @if($appeal->decided_at)<small class="text-muted">Decided by {{ $appeal->decider?->name ?? 'Administrator' }} on {{ $appeal->decided_at->format('M d, Y g:i A') }}</small>@endif
  </div></div></div>
  <div class="col-lg-5"><div class="card"><div class="card-body">
    <h2 class="h6">Current scholarship status</h2><p><strong>{{ ucfirst($appeal->award->recipient_status) }}</strong> &middot; {{ $appeal->award->award_no }}</p>
    <a href="{{ route('awards.show', $appeal->award) }}" class="btn btn-sm btn-outline-primary mb-3">View award</a>
    @if($appeal->isPending())
      <form method="POST" action="{{ route('appeals.decide', $appeal) }}">@csrf
        <label for="appealDecision" class="form-label">Review action</label><select id="appealDecision" name="status" class="form-select mb-3" required>
          <option value="under_review" @selected(old('status') === 'under_review')>Mark under review</option>
          <option value="approved" @selected(old('status') === 'approved')>Approve and reinstate</option>
          <option value="rejected" @selected(old('status') === 'rejected')>Reject appeal</option>
        </select>
        <label for="decisionReason" class="form-label">Reason for decision</label><textarea id="decisionReason" name="decision_reason" class="form-control" rows="5" maxlength="2000" aria-describedby="decisionHelp">{{ old('decision_reason') }}</textarea>
        <div id="decisionHelp" class="form-text">Required for approval or rejection. The student will see this explanation. Approving restores the existing award to active status, subject to available scholarship slots.</div>
        <button class="btn btn-dark mt-3">Save decision</button>
      </form>
    @else<p class="text-muted mb-0">This appeal is closed to further decisions. Its history is preserved.</p>@endif
  </div></div></div>
</div>
@endsection
