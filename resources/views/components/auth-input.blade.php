@props(['name', 'id', 'label', 'type' => 'text', 'optional' => false, 'help' => null, 'icon' => null])
@php
  $fieldValue = old($name, '');
  if (! is_scalar($fieldValue) || $type === 'password') $fieldValue = '';
  $descriptionIds = implode(' ', array_filter([$help ? $id.'-help' : null, $errors->has($name) ? $id.'-error' : null]));
@endphp
<div class="signup-field">
  <label for="{{ $id }}" class="form-label">{{ $label }} @if($optional)<span class="signup-optional">(optional)</span>@endif</label>
  @if($icon)<div class="input-group"><span class="input-group-text"><i class="bi bi-{{ $icon }}" aria-hidden="true"></i></span>@endif
  <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}" value="{{ $fieldValue }}" {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }} @if($descriptionIds) aria-describedby="{{ $descriptionIds }}" @endif @if($errors->has($name)) aria-invalid="true" @endif>
  @if($icon)</div>@endif
  @if($help)<p class="form-text" id="{{ $id }}-help">{{ $help }}</p>@endif
  @error($name)<div class="invalid-feedback d-block" id="{{ $id }}-error">{{ $message }}</div>@enderror
</div>
