@php
  $evidence = app(\App\Services\AdfcApplicationForm::class)->documentChecklist($application);
  $verifiedCount = collect($evidence)->where('status', 'verified')->count();
  $missingEvidence = collect($evidence)->whereNotIn('status', ['pending', 'verified']);
@endphp
<strong>{{ $verifiedCount }} / {{ count($evidence) }}</strong><small>required items verified</small>
@if($missingEvidence->isNotEmpty())
  <small class="text-warning-emphasis">{{ $missingEvidence->count() }} missing or returned</small>
@endif
@if($evidence)
<details class="form-evidence-details"><summary>View checklist</summary><ul class="adfc-checklist">
  @foreach($evidence as $item)<li><span>{{ $item['label'] }}<small>{{ $item['status_label'] }}</small></span></li>@endforeach
</ul></details>
@endif
