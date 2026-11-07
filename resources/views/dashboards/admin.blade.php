@extends('layouts.app')
@section('title','Admin Dashboard')
@section('page-title','Administrator Dashboard')
@section('content')
<div class="form-workspace role-dashboard">
  <header class="form-workspace-head"><div><span class="adfc-eyebrow">ADFC &middot; SCHOLARSHIP ADMINISTRATION</span><h1>A clear view of every application.</h1><p>Assign office reviews, monitor document verification, and record scholarship decisions with the complete applicant form.</p></div><div class="d-flex flex-wrap gap-2"><a href="{{ route('reports.index') }}" class="btn btn-outline-primary"><i class="bi bi-bar-chart me-2"></i>Reports</a><a href="{{ route('scholarships.create') }}" class="btn btn-navy"><i class="bi bi-plus-lg me-2"></i>New program</a></div></header>
  <div class="dashboard-metrics" role="group" aria-label="Scholarship administration overview">
    <x-dashboard-metric label="Awaiting assignment" :value="$stats['unassigned']" icon="bi-person-plus" :href="route('applications.index',['assigned_to'=>'unassigned','stage'=>'review'])" hint="Assign an office reviewer" />
    <x-dashboard-metric label="Documents pending" :value="$stats['docs_pending']" icon="bi-file-earmark-check" tone="gold" :href="route('verifications.index')" hint="Verification required" />
    <x-dashboard-metric label="Ready for decision" :value="$stats['decisions']" icon="bi-clipboard2-check" tone="indigo" :href="route('applications.index',['stage'=>'decision'])" hint="Office recommendation recorded" />
    <x-dashboard-metric label="Active scholars" :value="$stats['recipients']" icon="bi-mortarboard" tone="rose" :href="route('awards.index')" hint="Current scholarship recipients" />
  </div>
  <div class="form-workspace-grid"><div class="form-workspace-main">
    <section class="form-overview"><span class="adfc-eyebrow">APPLICATION / STUDENT FINANCIAL ASSISTANCE</span><h2>Keep the scholarship cycle moving.</h2><p>Prioritize recommended applications and unassigned submissions. Each review follows the ADFC applicant, school, family, assistance, and consent sections.</p><div class="form-workflow"><div><b>{{ $byStatus['submitted'] ?? 0 }}</b><span>Submitted</span></div><div><b>{{ $byStatus['under_review'] ?? 0 }}</b><span>Under review</span></div><div><b>{{ $stats['decisions'] }}</b><span>For decision</span></div><div><b>{{ $stats['approved'] }}</b><span>Approved</span></div></div></section>
    <section class="adfc-paper">
      <div class="adfc-paper-heading"><div><span class="adfc-eyebrow">PRIORITY QUEUE</span><h2 id="admin-queue-title">Applications needing action</h2><p>Decisions first, then forms awaiting assignment</p></div><a href="{{ route('applications.index',['stage'=>'active']) }}" class="small">All active applications</a></div>
      <div class="table-responsive"><table class="form-workspace-table dashboard-records" role="table" aria-labelledby="admin-queue-title">
        <thead role="rowgroup"><tr role="row"><th scope="col" role="columnheader">Applicant / scholarship</th><th scope="col" role="columnheader">Office review</th><th scope="col" role="columnheader">Required evidence</th><th scope="col" role="columnheader">Status</th><th scope="col" role="columnheader"><span class="visually-hidden">Action</span></th></tr></thead>
        <tbody role="rowgroup">
        @forelse($recent as $application)
          <tr role="row">
            <td role="cell" class="dashboard-record-title"><strong>{{ $application->applicantName() }}</strong><small>{{ $application->program?->name }}</small><small>{{ $application->application_no }}</small></td>
            <td role="cell" data-label="Office review"><div class="dashboard-cell-value">{{ $application->assignee?->name ?? 'Unassigned' }}<small>{{ $application->form_review ? str_replace('_',' ',$application->form_review['qualification'] ?? 'Reviewed') : 'Assessment pending' }}</small>@if(($application->adfc_form['other_assistance'] ?? '') === 'yes')<small>Other assistance declared</small>@endif</div></td>
            <td role="cell" data-label="Required evidence"><div class="dashboard-cell-value">@include('dashboards.partials.review-evidence')</div></td>
            <td role="cell" data-label="Status"><div class="dashboard-cell-value"><span class="adfc-badge">{{ str_replace('_',' ',$application->status) }}</span></div></td>
            <td role="cell" class="dashboard-record-action"><a href="{{ route('applications.show',$application) }}" class="btn btn-sm btn-outline-primary" aria-label="{{ in_array($application->status,['recommended_approval','recommended_rejection']) ? 'Decide' : ($application->assigned_to ? 'Review' : 'Assign') }} application {{ $application->application_no }}">{{ in_array($application->status,['recommended_approval','recommended_rejection']) ? 'Decide' : ($application->assigned_to ? 'Review' : 'Assign') }}</a></td>
          </tr>
        @empty
          <tr role="row"><td role="cell" colspan="5"><div class="form-empty"><i class="bi bi-check2-circle"></i><strong>No applications awaiting action.</strong>New submissions and recommendations will appear here.</div></td></tr>
        @endforelse
        </tbody>
      </table></div>
    </section>
    <section class="adfc-paper"><div class="adfc-paper-heading"><div><span class="adfc-eyebrow">RENEWALS</span><h2>Continuing student support</h2></div><a href="{{ route('renewals.index') }}" class="small">Manage renewals</a></div>@forelse($pendingRenewals as $renewal)<a href="{{ route('renewals.index') }}" class="form-quick-link"><i class="bi bi-arrow-repeat"></i><span class="flex-grow-1">{{ $renewal->student?->name }}<small class="d-block text-muted fw-normal">{{ $renewal->award?->program?->name }}</small></span><span class="adfc-badge">{{ str_replace('_',' ',$renewal->status) }}</span></a>@empty<div class="form-empty">No renewals awaiting a decision.</div>@endforelse</section>
  </div><aside class="form-workspace-aside" aria-label="Administration tools">
    <section class="adfc-paper"><div class="adfc-paper-heading"><div><span class="adfc-eyebrow">ADMINISTRATION</span><h2>Manage the process</h2></div></div><a class="form-quick-link" href="{{ route('applications.index',['assigned_to'=>'unassigned','stage'=>'review']) }}"><i class="bi bi-person-workspace"></i>Assign applications</a><a class="form-quick-link" href="{{ route('scholarships.index') }}"><i class="bi bi-award"></i>Programs and requirements</a><a class="form-quick-link" href="{{ route('applications.index',['assistance'=>'yes','stage'=>'active']) }}"><i class="bi bi-cash-coin"></i><span>Funding declarations<small class="d-block text-muted fw-normal">{{ $stats['other_assistance'] }} applicants declared other assistance</small></span></a><a class="form-quick-link" href="{{ route('users.index') }}"><i class="bi bi-people"></i>Students and office accounts</a><a class="form-quick-link" href="{{ route('appeals.index') }}"><i class="bi bi-chat-square-text"></i>Scholarship appeals</a></section>
    <section class="adfc-paper"><div class="adfc-paper-heading"><div><span class="adfc-eyebrow">RECORDS AT A GLANCE</span><h2>Scholarship totals</h2></div></div><div class="form-mini-stat"><span>Registered students</span><b>{{ $stats['students'] }}</b></div><div class="form-mini-stat"><span>Scholarship programs</span><b>{{ $stats['programs'] }}</b></div><div class="form-mini-stat"><span>Total applications</span><b>{{ $stats['applications'] }}</b></div><div class="form-mini-stat"><span>Approved applications</span><b>{{ $stats['approved'] }}</b></div><div class="form-mini-stat"><span>Rejected applications</span><b>{{ $stats['rejected'] }}</b></div></section>
    <section class="adfc-paper"><div class="adfc-paper-heading"><div><span class="adfc-eyebrow">PROGRAM DEADLINES</span><h2>Coming up</h2></div></div>@forelse($upcoming as $program)<div class="form-deadline"><strong>{{ $program->name }}</strong><span>{{ $program->deadline?->format('M d, Y') }}</span></div>@empty<div class="form-empty">No upcoming deadlines.</div>@endforelse</section>
  </aside></div>
</div>
@endsection
