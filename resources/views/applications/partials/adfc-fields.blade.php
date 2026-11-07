@php($definition = config('adfc_form.sections.'.$section))
<div class="adfc-section-heading"><span class="adfc-section-icon"><i class="bi {{ $definition['icon'] }}" aria-hidden="true"></i></span><div><h3>{{ $definition['title'] }}</h3><p>{{ $definition['subtitle'] }}</p></div></div>
<div class="row g-3 adfc-field-grid" data-adfc-section="{{ $section }}">
@foreach($definition['fields'] as $key => $field)
  <div class="{{ !empty($field['wide']) ? 'col-12' : ($section === 'applicant' && !in_array($key, ['mobile_number','email']) ? 'col-sm-6 col-xl-3' : 'col-md-6') }}" @if($key === 'other_assistance_details') data-assistance-details @endif>
    <label class="form-label" for="adfc-{{ $key }}">{{ $field['label'] }} @if(!empty($field['required']))<span class="text-danger" aria-hidden="true">*</span>@elseif($key === 'other_assistance_details')<span class="adfc-optional">required if yes</span>@else<span class="adfc-optional">optional</span>@endif</label>
    @if(isset($field['options']))
      <select id="adfc-{{ $key }}" name="adfc_form[{{ $key }}]" class="form-select" @required(!empty($field['required'])) data-adfc-label="{{ $field['label'] }}">
        <option value="">Select an option</option>
        @foreach($field['options'] as $value => $label)<option value="{{ $value }}" @selected(($adfcData[$key] ?? '') === $value)>{{ $label }}</option>@endforeach
      </select>
    @else
      <input id="adfc-{{ $key }}" name="adfc_form[{{ $key }}]" type="{{ $field['type'] ?? 'text' }}" class="form-control" value="{{ $adfcData[$key] ?? '' }}" maxlength="{{ $key === 'zip_code' ? 4 : 500 }}" @required(!empty($field['required'])) @if(($field['type'] ?? '') === 'date') max="{{ now()->toDateString() }}" @endif @if($key === 'zip_code') inputmode="numeric" pattern="[0-9]{4}" @endif @if($key === 'mobile_number') pattern="(\+63|0)9[0-9]{9}" @endif data-adfc-label="{{ $field['label'] }}">
    @endif
    @if(($field['type'] ?? '') === 'date')<div class="form-text">Use the date picker; the printed form uses DD/MM/YYYY.</div>@endif
    @if($key === 'mobile_number')<div class="form-text">Use 09XXXXXXXXX or +639XXXXXXXXX.</div>@endif
  </div>
@endforeach
</div>
