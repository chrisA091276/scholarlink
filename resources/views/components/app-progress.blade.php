{{-- Visual progress: draft → submitted → review → evaluation → decided --}}
@props(['status' => 'draft'])
@php
$steps = ['Submitted', 'Under Review', 'Evaluation', 'Decided'];
$map = ['draft' => 0, 'submitted' => 0, 'under_review' => 1, 'incomplete_requirements' => 1,
  'for_evaluation' => 2, 'recommended_approval' => 2, 'recommended_rejection' => 2,
  'approved' => 3, 'rejected' => 3, 'withdrawn' => -1];
$idx = $map[$status] ?? 0;
@endphp
@if($status === 'withdrawn')
<div class="alert alert-dark py-2 small mb-0"><i class="bi bi-flag me-1"></i>Application withdrawn</div>
@else
<div class="stepper mt-2" aria-label="Application progress: {{ str_replace('_', ' ', $status) }}">
  @foreach($steps as $i => $label)
  <div class="stp {{ $i < $idx ? 'done' : ($i === $idx ? 'now' : '') }}">
    <span class="dot">@if($i < $idx)<i class="bi bi-check"></i>@else{{ $i + 1 }}@endif</span>{{ $label }}
  </div>
  @if($i < count($steps) - 1)<div class="stp-line {{ $i < $idx ? 'done' : '' }}"></div>@endif
  @endforeach
</div>
@endif
