@extends('layouts.app')
@section('title','Scholarship Staff Dashboard')
@section('page-title','Staff Workspace')
@section('content')
<div class="form-workspace role-dashboard">
  <header class="form-workspace-head"><div><span class="adfc-eyebrow">ADFC &middot; SCHOLARSHIP OFFICE</span><h1>Application review workspace</h1><p>Welcome, {{ auth()->user()->name }}. Review assigned forms, verify supporting documents, and record your recommendation.</p></div><a href="{{ route('applications.index',['mine'=>1,'stage'=>'review']) }}" class="btn btn-navy"><i class="bi bi-inboxes me-2"></i>My review queue</a></header>
  <div class="dashboard-metrics" role="group" aria-label="Your assigned workload">
    <x-dashboard-metric label="Active assignments" :value="$stats['assigned']" icon="bi-inboxes" :href="route('applications.index',['mine'=>1,'stage'=>'review'])" hint="Assigned to you" />
    <x-dashboard-metric label="New submissions" :value="$stats['awaiting']" icon="bi-file-earmark-text" tone="indigo" :href="route('applications.index',['status'=>'submitted'])" hint="Start the form review" />
    <x-dashboard-metric label="Documents to verify" :value="$stats['docs_pending']" icon="bi-file-earmark-check" tone="gold" :href="route('verifications.index')" hint="Check uploaded evidence" />
    <x-dashboard-metric label="Needs corrections" :value="$stats['corrections']" icon="bi-arrow-repeat" tone="rose" :href="route('applications.index',['status'=>'incomplete_requirements'])" hint="Waiting for student updates" />
  </div>
  <div class="form-workspace-grid"><div class="form-workspace-main">
    <section class="form-overview"><span class="adfc-eyebrow">THE ADFC REVIEW PROCESS</span><h2>Every section. Every supporting document.</h2><p>Check applicant and school information, review family details and funding declarations, then complete the office assessment.</p><div class="form-workflow"><div><b>01</b><span>Read the form</span></div><div><b>02</b><span>Verify documents</span></div><div><b>03</b><span>Assess qualifications</span></div><div><b>04</b><span>Recommend a decision</span></div></div></section>
    <section class="adfc-paper">
      <div class="adfc-paper-heading"><div><span class="adfc-eyebrow">YOUR NEXT ACTIONS</span><h2 id="staff-queue-title">Assigned review queue</h2><p>Oldest submitted applications first</p></div><a href="{{ route('applications.index',['mine'=>1]) }}" class="small">All my assignments</a></div>
      <div class="table-responsive"><table class="form-workspace-table dashboard-records" role="table" aria-labelledby="staff-queue-title">
        <thead role="rowgroup"><tr role="row"><th scope="col" role="columnheader">Applicant / scholarship</th><th scope="col" role="columnheader">Form review</th><th scope="col" role="columnheader">Documents</th><th scope="col" role="columnheader"><span class="visually-hidden">Action</span></th></tr></thead>
        <tbody role="rowgroup">
        @forelse($queue as $application)
          @php($reviewed = app(\App\Services\AdfcApplicationForm::class)->reviewed($application))
          <tr role="row">
            <td role="cell" class="dashboard-record-title"><strong>{{ $application->applicantName() }}</strong><small>{{ $application->program?->name }}</small><small>{{ $application->application_no }} &middot; {{ str_replace('_',' ',$application->status) }}</small></td>
            <td role="cell" data-label="Form review"><div class="dashboard-cell-value"><span class="adfc-badge {{ $reviewed ? 'is-complete' : '' }}">{{ $application->form_version ? ($reviewed ? 'Completed' : 'Needs review') : 'Original form' }}</span>@if(($application->adfc_form['other_assistance'] ?? '') === 'yes')<small>Other assistance declared</small>@endif</div></td>
            <td role="cell" data-label="Documents"><div class="dashboard-cell-value">@include('dashboards.partials.review-evidence')</div></td>
            <td role="cell" class="dashboard-record-action"><a href="{{ route('applications.show',$application) }}" class="btn btn-sm btn-outline-primary" aria-label="Review application {{ $application->application_no }}">Review</a></td>
          </tr>
        @empty
          <tr role="row"><td role="cell" colspan="4"><div class="form-empty"><i class="bi bi-check2-circle"></i><strong>Your assigned queue is clear.</strong>New assignments will appear here.</div></td></tr>
        @endforelse
        </tbody>
      </table></div>
    </section>
  </div><aside class="form-workspace-aside" aria-label="Review tools and program deadlines">
    <section class="adfc-paper"><div class="adfc-paper-heading"><div><span class="adfc-eyebrow">OFFICE TOOLS</span><h2>Continue your review</h2></div></div><a class="form-quick-link" href="{{ route('verifications.index') }}"><i class="bi bi-file-earmark-check"></i><span>Document verification<small class="d-block text-muted fw-normal">{{ $stats['docs_pending'] }} pending</small></span></a><a class="form-quick-link" href="{{ route('screening.index') }}"><i class="bi bi-list-check"></i>Eligibility screening</a><a class="form-quick-link" href="{{ route('recommendations.index',['mine'=>1]) }}"><i class="bi bi-chat-square-text"></i>Open recommendations</a><a class="form-quick-link" href="{{ route('applications.index',['assistance'=>'yes','stage'=>'review']) }}"><i class="bi bi-cash-coin"></i><span>Other assistance declarations<small class="d-block text-muted fw-normal">{{ $stats['other_assistance'] }} active forms to check</small></span></a></section>
    <section class="adfc-paper"><div class="adfc-paper-heading"><div><span class="adfc-eyebrow">FORM REVIEW GUIDE</span><h2>Before recommending</h2></div></div><div class="p-3"><ul class="adfc-checklist">@foreach(config('adfc_form.sections') as $section)<li><i class="bi {{ $section['icon'] }}"></i><span>{{ $section['title'] }}</span></li>@endforeach</ul><p class="small text-muted mt-3 mb-0">Complete the office checklist and verify required files. The administrator records the final decision.</p></div></section>
    <section class="adfc-paper"><div class="adfc-paper-heading"><div><span class="adfc-eyebrow">PROGRAM DEADLINES</span><h2>Coming up</h2></div></div>@forelse($upcoming as $program)<div class="form-deadline"><strong>{{ $program->name }}</strong><span>{{ $program->deadline?->format('M d, Y') }}</span></div>@empty<div class="form-empty">No upcoming deadlines.</div>@endforelse<a class="form-quick-link" href="{{ route('scholarships.public') }}">View scholarship directory <i class="bi bi-arrow-up-right ms-auto"></i></a></section>
  </aside></div>
</div>
@endsection
