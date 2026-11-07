@extends('layouts.app')
@section('title','Eligibility Checklist')
@section('page-title','Eligibility Checklist')
@section('content')
<div class="student-page">
  <div class="student-page-head">
    <div>
      <span class="student-eyebrow">PREPARE WITH CONFIDENCE</span>
      <h1>Eligibility checklist</h1>
      <p>Use this view to understand what is complete, what is still pending, and what the review team will check after submission.</p>
    </div>
    <a href="{{ route('scholarships.public') }}" class="btn btn-gold"><i class="bi bi-compass me-2"></i>Explore scholarships</a>
  </div>

  @forelse($applications as $a)
    @php
      $result = $a->latestEligibilityResult?->result;
      $resultClass = $result === 'eligible' ? 'green' : ($result === 'not_eligible' ? 'red' : ($result ? 'gold' : 'gray'));
    @endphp
    <div class="student-surface student-surface-body mb-4">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div class="student-title-row">
          <span class="student-title-icon"><i class="bi bi-mortarboard"></i></span>
          <span>
            <strong>{{ $a->program?->name }}</strong>
            <small>{{ $a->application_no }} · {{ str_replace('_',' ',$a->status) }}</small>
          </span>
        </div>
        <span class="status-chip {{ $resultClass }}">{{ $result ? str_replace('_',' ',$result) : 'Not screened yet' }}</span>
      </div>
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="student-section-title">
            <h2>Document readiness</h2>
            <small>{{ $a->documents->where('verification_status','verified')->count() }}/{{ $a->program->requirements->count() }} verified</small>
          </div>
          @forelse($a->program->requirements as $req)
            @php($doc=$a->documents->firstWhere('scholarship_requirement_id',$req->id))
            <div class="student-list-row">
              <span class="student-list-icon"><i class="bi {{ $doc?->verification_status==='verified'?'bi-check-lg':($doc?'bi-clock':'bi-exclamation-lg') }}"></i></span>
              <span>
                <strong>{{ $req->name }} @if($req->is_required)<span class="status-chip red ms-1">Required</span>@endif</strong>
                <small>{{ $doc ? str_replace('_',' ',$doc->verification_status) : 'Missing · upload from your application' }}</small>
              </span>
            </div>
          @empty
            <div class="student-note">No requirements configured.</div>
          @endforelse
        </div>
        <div class="col-lg-6">
          <div class="student-section-title"><h2>Screening explanation</h2></div>
          @if($a->latestEligibilityResult)
            <div class="student-alert" style="background:#f5f8fc;border-color:#e1e8f1;color:#51647e"><i class="bi bi-info-circle"></i><span>{{ $a->latestEligibilityResult->reasons }}</span></div>
          @else
            <div class="student-alert" style="background:#f5f8fc;border-color:#e1e8f1;color:#51647e"><i class="bi bi-hourglass-split"></i><span>Staff will screen your eligibility after you submit. Screening is guidance and does not replace the final scholarship decision.</span></div>
          @endif
          <a href="{{ route('my-applications.show',$a) }}" class="btn btn-sm btn-outline-primary mt-3">Open application <i class="bi bi-arrow-up-right ms-1"></i></a>
        </div>
      </div>
    </div>
  @empty
    <div class="student-surface">
      <div class="student-empty">
        <i class="bi bi-list-check"></i>
        <h3>Your checklist will appear here</h3>
        <p>Start an application to see the exact documents and eligibility information for your chosen scholarship.</p>
        <a href="{{ route('scholarships.public') }}" class="btn btn-navy">Find a scholarship</a>
      </div>
    </div>
  @endforelse
</div>
@endsection
