@extends('layouts.app')
@section('title', isset($scholarship) ? 'Edit Program' : 'New Program')
@section('page-title', isset($scholarship) ? 'Edit Scholarship Program' : 'New Scholarship Program')
@section('content')
@php
  $oldTemplate = old('application_template');
  $templateValue = is_string($oldTemplate) ? $oldTemplate : (isset($scholarship) && $scholarship->application_template ? json_encode($scholarship->application_template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '');
  $oldRenewalTemplate = old('renewal_template');
  $renewalTemplateValue = is_string($oldRenewalTemplate) ? $oldRenewalTemplate : (isset($scholarship) && $scholarship->renewal_template ? json_encode($scholarship->renewal_template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '');
@endphp
<div class="card"><div class="card-body">
<form method="POST" action="{{ isset($scholarship) ? route('scholarships.update',$scholarship) : route('scholarships.store') }}">
@csrf @if(isset($scholarship)) @method('PUT') @endif
<div class="row g-3">
<div class="col-md-4"><label class="form-label small fw-semibold">Scholarship ID / Code *</label><input name="code" class="form-control" value="{{ old('code', $scholarship->code ?? '') }}" required placeholder="e.g. ADFC-MERIT-2026"></div>
<div class="col-md-8"><label class="form-label small fw-semibold">Scholarship name *</label><input name="name" class="form-control" value="{{ old('name', $scholarship->name ?? '') }}" required></div>
<div class="col-12"><label class="form-label small fw-semibold">Description</label><textarea name="description" class="form-control" rows="3">{{ old('description', $scholarship->description ?? '') }}</textarea></div>
<div class="col-md-4"><label class="form-label small fw-semibold">Category *</label><input name="category" class="form-control" value="{{ old('category', $scholarship->category ?? 'General') }}" required></div>
<div class="col-md-4"><label class="form-label small fw-semibold">Opening date</label><input type="date" name="opening_date" class="form-control" value="{{ old('opening_date', isset($scholarship)&&$scholarship->opening_date ? $scholarship->opening_date->format('Y-m-d') : '') }}"></div>
<div class="col-md-4"><label class="form-label small fw-semibold">Deadline</label><input type="date" name="deadline" class="form-control" value="{{ old('deadline', isset($scholarship)&&$scholarship->deadline ? $scholarship->deadline->format('Y-m-d') : '') }}"></div>
<div class="col-12"><label class="form-label small fw-semibold">Benefits / financial assistance</label><textarea name="benefits" class="form-control" rows="2">{{ old('benefits', $scholarship->benefits ?? '') }}</textarea></div>
<div class="col-12"><label class="form-label small fw-semibold">Eligibility criteria</label><textarea name="eligibility_criteria" class="form-control" rows="2">{{ old('eligibility_criteria', $scholarship->eligibility_criteria ?? '') }}</textarea></div>
<div class="col-md-6"><label class="form-label small fw-semibold">Slots</label><input type="number" name="slots" class="form-control" value="{{ old('slots', $scholarship->slots ?? '') }}" min="1"></div>
<div class="col-md-6"><label class="form-label small fw-semibold">Status *</label><select name="status" class="form-select">@foreach(['draft','published','closed','archived'] as $s)<option value="{{ $s }}" {{ old('status', $scholarship->status ?? 'draft')==$s?'selected':'' }}>{{ ucfirst($s) }}</option>@endforeach</select><div class="form-text">Only <b>published</b> + within dates accepts applications.</div></div>
<div class="col-12"><label class="form-label small fw-semibold">Student application template</label><textarea name="application_template" class="form-control font-monospace" rows="10" placeholder='[{"key":"example_field","label":"Example field","type":"text","required":true}]'>{{ $templateValue }}</textarea><div class="form-text">Optional JSON array. Each field needs a unique key, label, and type. Supported types: text, textarea, number, date, select, multi_select, checkbox, email, url. Use <code>options</code> for select and multi-select fields. Students will see these fields in the application wizard.</div></div>
<div class="col-12"><label class="form-label small fw-semibold">Renewal application template</label><textarea name="renewal_template" class="form-control font-monospace" rows="6" placeholder='[{"key":"progress_update","label":"Progress update","type":"textarea","required":true}]'>{{ $renewalTemplateValue }}</textarea><div class="form-text">Optional JSON array using the same field types. These questions appear only when an active award is renewed.</div></div>
</div>
<div class="mt-3 d-flex gap-2"><button class="btn btn-gold">Save program</button><a href="{{ route('scholarships.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
</form>
</div></div>
@endsection
