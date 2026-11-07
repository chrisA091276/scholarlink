@extends('layouts.app')
@section('title','My Scholarship Status')
@section('page-title','My Scholarship Status')
@section('content')
<div class="student-page">
  <div class="student-page-head">
    <div><span class="student-eyebrow">SCHOLARSHIP MEMBERSHIP</span><h1>My scholarship status</h1><p>Check whether you are still included, read status updates, and track reinstatement appeals.</p></div>
    <a href="{{ route('my-renewals.index') }}" class="btn btn-navy"><i class="bi bi-arrow-repeat me-2"></i>Renewal center</a>
  </div>
  @forelse($awards as $award)
    @php $pendingAppeal = $award->appeals->first(fn ($appeal) => $appeal->isPending()); @endphp
    <section class="student-surface student-surface-body mb-4" id="award-{{ $award->id }}">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="student-title-row"><span class="student-title-icon"><i class="bi bi-mortarboard"></i></span><span><strong>{{ $award->program?->name }}</strong><small>{{ $award->award_no }}</small></span></div>
        <span class="badge {{ $award->recipient_status === 'active' ? 'bg-success' : ($award->canAppeal() ? 'bg-danger' : 'bg-secondary') }}">{{ ucfirst($award->recipient_status) }}</span>
      </div>
      <div class="rounded-3 border p-3 {{ $award->recipient_status === 'active' ? 'bg-success-subtle text-success-emphasis' : ($award->canAppeal() ? 'bg-warning-subtle text-warning-emphasis' : 'bg-secondary-subtle') }} mt-3 mb-3">
        <strong>{{ $award->statusDescription() }}</strong>
        @if($award->status_reason || ($award->canAppeal() && $award->remarks))<div class="mt-2 text-break" style="white-space:pre-line">Reason: {{ $award->status_reason ?: $award->remarks }}</div>@endif
        @if($award->status_changed_at)<small class="d-block mt-2">Status updated {{ $award->status_changed_at->format('M d, Y g:i A') }}</small>@endif
      </div>
      <div class="row g-3">
        <div class="col-md-6"><div class="student-meta-row"><span>Benefits</span><strong>{{ $award->benefits ?: 'See program details' }}</strong></div></div>
        <div class="col-md-6"><div class="student-meta-row"><span>Award period</span><strong>{{ $award->start_date?->format('M d, Y') ?? 'Not set' }} &ndash; {{ $award->end_date?->format('M d, Y') ?? 'Not set' }}</strong></div></div>
      </div>
      @if($award->recipient_status === 'active')
        <a href="{{ route('renewals.create', $award) }}" class="btn btn-sm btn-navy mt-3">Apply for renewal</a>
      @elseif($pendingAppeal)
        <p class="mt-3 mb-0 text-primary"><i class="bi bi-hourglass-split me-1"></i>Your appeal is {{ str_replace('_', ' ', $pendingAppeal->status) }}. Your scholarship status remains {{ $award->recipient_status }} until an administrator approves reinstatement.</p>
      @elseif($award->canAppeal())
        <a href="{{ route('my-appeals.create', $award) }}" class="btn btn-navy mt-3"><i class="bi bi-chat-square-text me-2"></i>Appeal for reinstatement</a>
      @endif
      @if($award->appeals->isNotEmpty())
        <div class="border-top mt-4 pt-3">
          <h2 class="h6 fw-bold">Appeal history</h2>
          @foreach($award->appeals as $appeal)
            <details class="border rounded-3 p-3 mt-2" @if($loop->first) open @endif>
              <summary class="small"><strong>{{ $appeal->appeal_no }}</strong> &middot; {{ ucfirst(str_replace('_', ' ', $appeal->status)) }} &middot; {{ $appeal->created_at->format('M d, Y') }}</summary>
              <p class="small text-break mt-3 mb-2" style="white-space:pre-line"><strong>Your appeal:</strong> {{ $appeal->statement }}</p>
              @if($appeal->status_reason)<p class="small text-break" style="white-space:pre-line"><strong>Reason at time of appeal:</strong> {{ $appeal->status_reason }}</p>@endif
              @if($appeal->decision_reason)<p class="small text-break mb-1" style="white-space:pre-line"><strong>Review response:</strong> {{ $appeal->decision_reason }}</p>@endif
              @if($appeal->decided_at)<small class="text-muted">Decision recorded {{ $appeal->decided_at->format('M d, Y g:i A') }}</small>@endif
            </details>
          @endforeach
        </div>
      @endif
    </section>
  @empty
    <div class="student-surface"><div class="student-empty"><i class="bi bi-mortarboard"></i><h3>No scholarship awards yet</h3><p>Your membership status will appear here once a scholarship has been awarded.</p><a href="{{ route('my-applications.index') }}" class="btn btn-navy">View applications</a></div></div>
  @endforelse
  {{ $awards->links() }}
</div>
@endsection
