@props(['label', 'value', 'icon', 'hint' => null, 'tone' => 'blue', 'href' => null, 'suffix' => ''])

@if($href)
<a href="{{ $href }}" class="dashboard-metric dashboard-metric--{{ $tone }}">
@else
<div class="dashboard-metric dashboard-metric--{{ $tone }}">
@endif
  <div class="dashboard-metric-heading">
    <span class="dashboard-metric-label">{{ $label }}</span>
    <span class="dashboard-metric-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
  </div>
  <strong class="dashboard-metric-value">{{ number_format($value) }}{{ $suffix }}</strong>
  @if($hint)<span class="dashboard-metric-hint">{{ $hint }}</span>@endif
@if($href)
</a>
@else
</div>
@endif
