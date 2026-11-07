@if($award->status_reason)<p class="mt-3 text-break" style="white-space:pre-line"><strong>Status reason:</strong> {{ $award->status_reason }}</p>@endif
@if(auth()->user()->isAdmin())
<form method="POST" action="{{ route('awards.status', $award) }}" class="row g-3 mt-2">
  @csrf
  <div class="col-12"><label for="awardStatus" class="form-label">Scholarship status</label><select id="awardStatus" name="recipient_status" class="form-select" required>@foreach(\App\Models\ScholarshipAward::STATUSES as $status)<option value="{{ $status }}" @selected(old('recipient_status', $award->recipient_status) === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
  <div class="col-md-6"><label for="awardStart" class="form-label">Start date</label><input id="awardStart" type="date" name="start_date" class="form-control" value="{{ old('start_date', $award->start_date?->format('Y-m-d')) }}"></div>
  <div class="col-md-6"><label for="awardEnd" class="form-label">End date</label><input id="awardEnd" type="date" name="end_date" class="form-control" value="{{ old('end_date', $award->end_date?->format('Y-m-d')) }}"></div>
  <div class="col-12"><label for="awardReason" class="form-label">Reason for removal or suspension</label><textarea id="awardReason" name="status_reason" class="form-control" rows="3" maxlength="2000" aria-describedby="awardReasonHelp">{{ old('status_reason', $award->status_reason) }}</textarea><div id="awardReasonHelp" class="form-text">Required when removing or suspending a student, for example documented absences or another valid reason. This explanation is visible to the student. Their account stays accessible so they can appeal.</div></div>
  <div class="col-12"><label for="awardRemarks" class="form-label">Other remarks</label><textarea id="awardRemarks" name="remarks" class="form-control" rows="2" maxlength="2000">{{ old('remarks', $award->remarks) }}</textarea></div>
  <div class="col-12"><button class="btn btn-dark">Save scholarship status</button></div>
</form>
<script>
(() => {
  const status = document.getElementById('awardStatus');
  const reason = document.getElementById('awardReason');
  function sync() { reason.required = ['removed', 'suspended'].includes(status.value); }
  status.addEventListener('change', sync);
  sync();
})();
</script>
@endif
@if(auth()->user()->isAdmin() && $award->appeals->isNotEmpty())
<div class="border-top mt-4 pt-3"><h2 class="h6">Reinstatement appeals</h2>@foreach($award->appeals as $appeal)<div class="d-flex flex-wrap justify-content-between gap-2 mb-2"><span class="small">{{ $appeal->appeal_no }} &middot; {{ str_replace('_', ' ', $appeal->status) }}</span><a href="{{ route('appeals.show', $appeal) }}" class="btn btn-sm btn-outline-primary">Review appeal</a></div>@endforeach</div>
@endif
