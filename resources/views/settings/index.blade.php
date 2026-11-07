@extends('layouts.app')
@section('title','Settings')
@section('page-title','System Settings')
@section('content')
<div class="card"><div class="card-body col-lg-8">
<form method="POST" action="{{ route('settings.update') }}">@csrf
@php $defaults = ['school_name'=>'Asian Development Foundation College','academic_year'=>'2026-2027','announcement_banner'=>'','maintenance_note'=>'']; @endphp
@foreach($defaults as $k=>$label)
<div class="mb-2"><label class="form-label small fw-semibold">{{ $k }}</label>
<input name="settings[{{ $k }}]" class="form-control" value="{{ old('settings.'.$k, $settings->firstWhere('key',$k)?->value ?? ($k=='school_name'?'Asian Development Foundation College':'')) }}"></div>
@endforeach
<button class="btn btn-gold mt-2">Save settings</button>
</form>
<hr>
<h6 class="fw-bold">Existing settings</h6>
<div class="table-scroll-region" role="region" aria-label="Existing settings" tabindex="0">
<table class="table table-sm">@foreach($settings as $s)<tr><td><b>{{ $s->key }}</b></td><td class="small">{{ $s->value }}</td></tr>@endforeach</table>
</div>
</div></div>
@endsection
