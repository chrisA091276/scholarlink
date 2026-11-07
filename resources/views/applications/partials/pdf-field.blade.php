@php
  $field = collect($service->sections())->pluck('fields')->collapse()->get($key);
  $value = $answers[$key] ?? null;
  if ($key === 'other_assistance_details' && ($answers['other_assistance'] ?? null) === 'no') $value = 'NA';
@endphp
<td style="width:{{ $width }}%">
  <span class="field-label">{{ $label ?? $field['label'] }}</span>
  <div class="field-answer">
    @if(isset($field['options']))
      @foreach($field['options'] as $option => $optionLabel)
        @continue($option === 'na' && $value !== 'na')
        <span class="choice"><span class="check-box">{{ $value === $option ? '✓' : '' }}</span> {{ $optionLabel }}</span>
      @endforeach
      @if(blank($value))<span class="unrecorded">Not provided</span>@endif
    @else
      {{ $service->display($key, $value) }}
    @endif
  </div>
</td>
