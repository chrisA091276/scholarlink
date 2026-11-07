@extends('layouts.app')
@section('title','Appeal for Reinstatement')
@section('page-title','Appeal for Reinstatement')
@section('content')
<div class="student-page">
  <div class="student-page-head"><div><span class="student-eyebrow">REQUEST A REVIEW</span><h1>Appeal for reinstatement</h1><p>Explain your circumstances and why you believe your scholarship should be restored.</p></div></div>
  <div class="row g-4">
    <div class="col-lg-5"><div class="student-surface student-surface-body">
      <h2 class="h5">{{ $award->program?->name }}</h2><p class="small text-muted">{{ $award->award_no }}</p>
      <span class="badge bg-danger mb-3">{{ ucfirst($award->recipient_status) }}</span>
      <p>{{ $award->statusDescription() }}</p>
      <div class="alert alert-warning text-break" style="white-space:pre-line"><strong>Reason provided:</strong> {{ $award->status_reason ?: $award->remarks ?: 'Contact the scholarship office for details.' }}</div>
      <p class="small text-muted mb-0">Submitting an appeal does not automatically restore your scholarship. An administrator will review your explanation and notify you of the decision.</p>
    </div></div>
    <div class="col-lg-7"><form method="POST" action="{{ route('my-appeals.store', $award) }}" class="student-surface student-surface-body">
      @csrf
      <label for="appealStatement" class="form-label fw-semibold">Why should your scholarship be reinstated?</label>
      <textarea id="appealStatement" name="statement" class="form-control" rows="9" minlength="20" maxlength="5000" required aria-describedby="appealHelp">{{ old('statement') }}</textarea>
      <div id="appealHelp" class="form-text">Describe the circumstances, relevant dates, and steps you have taken to address the issue. Use 20–5,000 characters.</div>
      <div class="d-flex flex-wrap gap-2 mt-4"><button type="submit" class="btn btn-navy">Submit appeal</button><a href="{{ route('my-awards') }}" class="btn btn-outline-secondary">Back to my status</a></div>
    </form></div>
  </div>
</div>
@endsection
