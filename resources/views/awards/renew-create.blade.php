@extends('layouts.app')
@section('title','Renew Award')
@section('page-title','Renew Scholarship Award')
@section('content')
<div class="student-page">
  <div class="student-page-head"><div><span class="student-eyebrow">CONTINUE YOUR SCHOLARSHIP</span><h1>Renew your award</h1><p>Submit your updated academic information for the next scholarship period.</p></div><a href="{{ route('my-awards') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Back to awards</a></div>
  <div class="row g-4">
    <div class="col-xl-4"><div class="student-detail-side"><span class="student-eyebrow">CURRENT AWARD</span><h2 class="mt-2">{{ $award->program?->name }}</h2><div class="student-meta-row"><span>Award number</span><strong>{{ $award->award_no }}</strong></div><div class="student-meta-row"><span>Status</span><span class="status-chip green">{{ $award->recipient_status }}</span></div><div class="student-meta-row"><span>Benefits</span><strong>{{ $award->benefits ?: 'See program details' }}</strong></div></div></div>
    <div class="col-xl-8"><form method="POST" action="{{ route('renewals.store',$award) }}" class="student-form-card">@csrf
      <section class="student-form-section"><h2>Renewal information</h2><p>Provide your current academic information. You can save a draft and return later.</p><div class="row g-3">
        <div class="col-md-6"><label class="form-label">Next academic year <span class="text-danger">*</span></label><input name="academic_year" class="form-control" value="{{ old('academic_year', $renewal?->academic_year ?? (now()->year+1).'-'.(now()->year+2)) }}" required></div>
        <div class="col-12"><label class="form-label">Renewal statement</label><textarea name="statement" class="form-control" rows="5" placeholder="Share your academic progress or any information the scholarship team should know.">{{ old('statement', $renewal?->statement) }}</textarea></div>
        @foreach($renewalFields as $field)
          @php($fieldValue = old('form_data.'.$field['key'], $renewal?->form_data[$field['key']] ?? null))
          <div class="col-md-6"><label class="form-label">{{ $field['label'] }} @if($field['required'])<span class="text-danger">*</span>@endif</label>
            @if($field['type'] === 'textarea')
              <textarea name="form_data[{{ $field['key'] }}]" class="form-control" rows="4" @if($field['required']) required @endif>{{ is_scalar($fieldValue) ? $fieldValue : '' }}</textarea>
            @elseif($field['type'] === 'select')
              <select name="form_data[{{ $field['key'] }}]" class="form-select" @if($field['required']) required @endif><option value="">— Select —</option>@foreach($field['options'] as $option)<option value="{{ $option }}" @selected((string) $fieldValue === (string) $option)>{{ $option }}</option>@endforeach</select>
            @elseif($field['type'] === 'multi_select')
              @php($selectedValues = is_array($fieldValue) ? $fieldValue : [])
              <select multiple name="form_data[{{ $field['key'] }}][]" class="form-select" @if($field['required']) required @endif>@foreach($field['options'] as $option)<option value="{{ $option }}" @selected(in_array($option, $selectedValues, true))>{{ $option }}</option>@endforeach</select>
            @elseif($field['type'] === 'checkbox')
              <div class="form-check mt-2"><input type="checkbox" name="form_data[{{ $field['key'] }}]" value="1" class="form-check-input" @checked($fieldValue) @if($field['required']) required @endif><label class="form-check-label">Yes</label></div>
            @else
              <input type="{{ in_array($field['type'], ['text','number','date','email','url'], true) ? $field['type'] : 'text' }}" name="form_data[{{ $field['key'] }}]" class="form-control" value="{{ is_scalar($fieldValue) ? $fieldValue : '' }}" @if($field['required']) required @endif>
            @endif
            @if($field['help'])<div class="form-text">{{ $field['help'] }}</div>@endif
          </div>
        @endforeach
      </div></section>
      <div class="d-flex flex-wrap justify-content-end gap-2 pt-3 border-top"><button name="action" value="draft" formnovalidate class="btn btn-outline-secondary">Save draft</button><button name="action" value="submit" class="btn btn-gold"><i class="bi bi-send me-1"></i>Submit renewal</button></div>
    </form></div>
  </div>
</div>
@endsection
