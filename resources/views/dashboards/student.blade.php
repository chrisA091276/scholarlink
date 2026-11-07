@extends('layouts.app')
@section('title', 'Student Dashboard')
@section('page-title', 'My dashboard')
@push('styles')
<link href="{{ asset('vendor/instrument-sans/instrument-sans.css') }}" rel="stylesheet">
<link href="{{ asset('css/student-dashboard.css') }}?v={{ filemtime(public_path('css/student-dashboard.css')) }}" rel="stylesheet">
@endpush
@section('content')
@php
  $editable = $activeApplication && in_array($activeApplication->status, ['draft', 'incomplete_requirements']);
  $continueUrl = $activeApplication ? route($editable ? 'my-applications.edit' : 'my-applications.show', $activeApplication) : route('my-applications.create');
  $completedSections = collect($formSections)->where('complete', true)->count();
  $uploadedCount = collect($formDocuments)->whereIn('status', ['pending', 'verified'])->count();
  $firstName = $profile?->first_name ?: \Illuminate\Support\Str::before(auth()->user()->name, ' ');
  $needsCorrection = $activeApplication?->status === 'incomplete_requirements';
  $actionLabel = $needsCorrection ? 'Update application' : ($activeApplication ? ($editable ? 'Continue application' : 'View application') : 'Start an application');
  $currentStage = match ($activeApplication?->status) {
      'submitted' => 1,
      'under_review', 'for_evaluation', 'recommended_approval', 'recommended_rejection' => 2,
      'approved' => 3,
      default => 0,
  };
  $nextDeadline = $available->filter(fn ($program) => $program->deadline)->sortBy('deadline')->first();
  $statusLabels = ['draft' => 'Draft', 'submitted' => 'Submitted', 'under_review' => 'Under review', 'incomplete_requirements' => 'Action needed', 'for_evaluation' => 'Being evaluated', 'recommended_approval' => 'Awaiting decision', 'recommended_rejection' => 'Awaiting decision', 'approved' => 'Approved', 'rejected' => 'Not approved', 'withdrawn' => 'Withdrawn'];
@endphp
<div class="form-workspace scholar-home role-dashboard">
  <header class="scholar-heading">
    <div><span class="sh-eyebrow">YOUR STUDENT WORKSPACE</span><h1>Welcome back, {{ $firstName }}<span class="sh-greeting-dot">.</span></h1><p>A clear view of your scholarship journey, all in one place.</p></div>
    <a href="{{ route('profile.show') }}" class="sh-text-link sh-profile-link" aria-label="My student profile"><i class="bi bi-person-circle" aria-hidden="true"></i> My student profile <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
  </header>

  <section class="scholar-hero" aria-labelledby="scholar-hero-title">
    <div class="scholar-hero-media" aria-hidden="true">
      <img src="{{ asset('images/dashboard/campus-walk.jpg') }}" alt="" width="1280" height="720" fetchpriority="high">
      <video id="scholarCampusVideo" data-src="{{ asset('videos/dashboard/campus-walk.mp4') }}" poster="{{ asset('images/dashboard/campus-walk.jpg') }}" muted loop playsinline preload="none" tabindex="-1"></video>
    </div>
    <div class="scholar-hero-copy">
      <span class="scholar-hero-kicker"><span></span> ADFC SCHOLARSHIP SUPPORT</span>
      <h2 id="scholar-hero-title">Your future.<br><em>One step closer.</em></h2>
      <p>{{ $needsCorrection ? 'A few updates will keep your application moving. Review the feedback from your scholarship office.' : ($activeApplication ? ($editable ? 'Pick up where you left off. Your saved application is ready for your next step.' : 'Your application is in. Stay connected with your progress and updates from the scholarship office.') : 'Discover opportunities, prepare your application, and take the next step toward your goals.') }}</p>
      <div class="scholar-hero-actions"><a href="{{ $continueUrl }}" class="sh-button sh-button-gold">{{ $actionLabel }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a><a href="{{ route('scholarships.public') }}" class="scholar-hero-secondary">Explore scholarships <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div>
    </div>
    <div class="scholar-hero-caption"><i class="bi bi-mortarboard" aria-hidden="true"></i><span>Make room for<br><strong>what comes next.</strong></span></div>
    <button type="button" id="scholarVideoToggle" class="scholar-video-toggle" aria-label="Play background video" aria-controls="scholarCampusVideo" hidden><i class="bi bi-play-fill" aria-hidden="true"></i><span>Play video</span></button>
  </section>

  <div class="dashboard-metrics scholar-metrics" role="group" aria-label="Your application overview">
    <x-dashboard-metric label="My applications" :value="$applicationStats['total']" icon="bi-files" :href="route('my-applications.index')" hint="All scholarship records" />
    <x-dashboard-metric label="Saved drafts" :value="$applicationStats['drafts']" icon="bi-pencil-square" tone="gold" :href="route('my-applications.index', ['status' => 'draft'])" hint="Ready to continue" />
    <x-dashboard-metric label="Under review" :value="$applicationStats['in_review']" icon="bi-hourglass-split" tone="indigo" :href="route('my-applications.index', ['stage' => 'review'])" hint="With the scholarship office" />
    <x-dashboard-metric label="Unread updates" :value="$unreadCount" icon="bi-bell" tone="green" :href="route('notifications.index')" hint="Your latest notifications" />
  </div>

  @if($missingDocs->isNotEmpty())
    <section class="scholar-attention" aria-label="Documents needing your attention"><span class="sh-attention-icon"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></span><div><strong>A quick update is needed</strong><p>The scholarship office returned the following documents for correction.</p>@foreach($missingDocs as $document)<div class="scholar-correction"><a href="{{ route('my-applications.edit', $document->application) }}#step-5">{{ $document->document_name }} <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a><small>{{ $document->application->application_no }} &middot; {{ $document->verification_notes }}</small></div>@endforeach</div></section>
  @endif

  <div class="scholar-content-grid">
    <div class="scholar-main">
      <section class="sh-panel scholar-application" aria-labelledby="current-application-title">
        <div class="sh-panel-head"><div><span class="sh-eyebrow">YOUR NEXT STEP</span><h2 id="current-application-title">{{ $activeApplication ? 'Keep your application on track' : 'Let’s get you started' }}</h2></div><span class="sh-status {{ $needsCorrection ? 'sh-status-amber' : ($activeApplication?->status === 'approved' ? 'sh-status-green' : '') }}">{{ $statusLabels[$activeApplication?->status] ?? 'Getting started' }}</span></div>
        <div class="scholar-application-body">
          <div class="scholar-program-title"><span class="sh-program-icon"><i class="bi bi-mortarboard" aria-hidden="true"></i></span><div><h3>{{ $activeApplication?->program?->name ?? 'Your first scholarship application' }}</h3><p>{{ $activeApplication ? $activeApplication->application_no.' · '.$activeApplication->academic_year : 'Choose a program and complete the ADFC application form.' }}</p></div></div>
          <ol class="scholar-journey" aria-label="Application progress">
            @foreach(['Prepare', 'Submitted', 'In review', 'Decision'] as $stage => $stageLabel)
              <li class="{{ $stage < $currentStage || ($stage === 3 && $activeApplication?->status === 'approved') ? 'is-done' : '' }} {{ $stage === $currentStage ? 'is-current' : '' }}" @if($stage === $currentStage) aria-current="step" @endif><span class="scholar-journey-marker">@if($stage < $currentStage || ($stage === 3 && $activeApplication?->status === 'approved'))<i class="bi bi-check-lg" aria-hidden="true"></i>@else{{ $stage + 1 }}@endif</span><span>{{ $stageLabel }}</span></li>
            @endforeach
          </ol>
          <div class="scholar-next-action"><div><strong>{{ $needsCorrection ? 'Review your returned documents' : ($editable || !$activeApplication ? 'Every completed section is progress.' : ($activeApplication->status === 'approved' ? 'Your application has been approved.' : 'Your application is with the scholarship office.')) }}</strong><p>{{ $editable || !$activeApplication ? $completedSections.' of '.count($formSections).' form sections filled · '.$uploadedCount.' of '.count($formDocuments).' required uploads received' : 'View your application for the latest status and review details.' }}</p></div><a href="{{ $continueUrl }}{{ $needsCorrection ? '#step-5' : '' }}" class="sh-button sh-button-navy">{{ $needsCorrection ? 'Review feedback' : ($editable ? 'Continue' : ($activeApplication ? 'View details' : 'Get started')) }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
          @if($editable && count($formMissing) && $missingDocs->isEmpty())<a class="scholar-upload-note" href="{{ $continueUrl }}#step-5"><i class="bi bi-paperclip" aria-hidden="true"></i><span>{{ count($formMissing) }} required {{ count($formMissing) === 1 ? 'document still needs' : 'documents still need' }} to be uploaded</span><i class="bi bi-arrow-right" aria-hidden="true"></i></a>@endif
        </div>
        <details class="scholar-checklists" @if($needsCorrection) open @endif>
          <summary><span><i class="bi bi-list-check" aria-hidden="true"></i> Application & document checklist</span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
          <div class="scholar-checklist-grid">@include('dashboards.partials.application-checklist')</div>
        </details>
      </section>

      <section class="sh-panel scholar-recent" aria-labelledby="recent-applications-title">
        <div class="sh-panel-head"><div><span class="sh-eyebrow">AT A GLANCE</span><h2 id="recent-applications-title">Recent applications</h2></div><a href="{{ route('my-applications.index') }}" class="sh-text-link">View all <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div>
        <div class="table-responsive"><table class="scholar-table dashboard-records" role="table" aria-labelledby="recent-applications-title"><thead role="rowgroup"><tr role="row"><th scope="col" role="columnheader">Scholarship</th><th scope="col" role="columnheader">Status</th><th scope="col" role="columnheader">Updated</th><th scope="col" role="columnheader"><span class="visually-hidden">Action</span></th></tr></thead><tbody role="rowgroup">
          @forelse($applications as $item)
            <tr role="row"><td role="cell" class="dashboard-record-title"><strong>{{ $item->program?->name }}</strong><small>{{ $item->application_no }}</small></td><td role="cell" data-label="Status"><div class="dashboard-cell-value"><span class="sh-status {{ $item->status === 'approved' ? 'sh-status-green' : ($item->status === 'incomplete_requirements' ? 'sh-status-amber' : '') }}">{{ $statusLabels[$item->status] ?? str_replace('_', ' ', $item->status) }}</span></div></td><td role="cell" class="scholar-table-date" data-label="Updated"><div class="dashboard-cell-value">{{ $item->updated_at?->format('M j, Y') }}</div></td><td role="cell" class="dashboard-record-action"><a class="scholar-row-link" href="{{ route('my-applications.show', $item) }}" aria-label="Open {{ $item->application_no }}"><span class="dashboard-action-label">View application</span><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></td></tr>
          @empty
            <tr role="row"><td role="cell" colspan="4"><div class="scholar-empty"><i class="bi bi-file-earmark-plus" aria-hidden="true"></i><strong>A fresh start, full of possibilities.</strong><p>Your applications will appear here when you start a form.</p><a href="{{ route('scholarships.public') }}" class="sh-text-link">Find a scholarship <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div></td></tr>
          @endforelse
        </tbody></table></div>
      </section>

      @if($awards->isNotEmpty())
        <section class="sh-panel" aria-labelledby="student-awards-title"><div class="sh-panel-head"><div><span class="sh-eyebrow">SCHOLARSHIP MEMBERSHIP</span><h2 id="student-awards-title">Your awards</h2></div><a href="{{ route('my-awards') }}" class="sh-text-link">View all <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div>@foreach($awards as $award)<div class="scholar-award"><div><i class="bi bi-award" aria-hidden="true"></i><strong>{{ $award->program?->name }}</strong><span class="sh-status {{ $award->recipient_status === 'active' ? 'sh-status-green' : 'sh-status-amber' }}">{{ ucfirst($award->recipient_status) }}</span></div><p>{{ $award->statusDescription() }}</p>@if($award->status_reason)<p>{{ $award->status_reason }}</p>@endif @if($award->canAppeal())<a href="{{ route('my-awards') }}#award-{{ $award->id }}" class="sh-text-link">View status and appeal options <i class="bi bi-arrow-right" aria-hidden="true"></i></a>@endif</div>@endforeach</section>
      @endif
    </div>

    <aside class="scholar-aside" aria-label="Profile, deadlines and updates">
      <section class="sh-panel scholar-profile"><div class="sh-profile-top"><div class="scholar-profile-ring" style="--profile-progress:{{ $completion }}%" role="img" aria-label="Profile {{ $completion }} percent complete"><span>{{ $completion }}<small>%</small></span></div><div><h2>Your student profile</h2><p>{{ $completion === 100 ? 'Looking good. You’re all set.' : 'A strong application starts here.' }}</p></div></div><p class="sh-profile-description">{{ $completion === 100 ? 'Your essential details are ready to help you complete your scholarship forms.' : 'Add your personal and enrollment details to make applying easier.' }}</p><a href="{{ route($completion === 100 ? 'profile.show' : 'profile.edit') }}" class="sh-profile-button">{{ $completion === 100 ? 'View profile' : 'Complete profile' }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a></section>

      @if($nextDeadline)
        <section class="scholar-deadline"><div class="scholar-deadline-heading"><span class="sh-eyebrow">UPCOMING DEADLINE</span><i class="bi bi-calendar-event" aria-hidden="true"></i></div><time datetime="{{ $nextDeadline->deadline->toDateString() }}">{{ $nextDeadline->deadline->format('d') }} <span>{{ $nextDeadline->deadline->format('M Y') }}</span></time><h2>{{ $nextDeadline->name }}</h2><p>{{ $nextDeadline->deadline->isToday() ? 'Applications close today.' : 'Plan ahead and give yourself time to prepare.' }}</p><a href="{{ route('scholarships.view', $nextDeadline) }}" class="sh-text-link">View requirements <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></section>
      @endif

      <section class="sh-panel scholar-updates"><div class="sh-panel-head"><div><span class="sh-eyebrow">STAY IN THE LOOP</span><h2>Latest updates</h2></div><a href="{{ route('notifications.index') }}" class="sh-icon-link" aria-label="View all notifications"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div>
        @forelse($notifications->take(3) as $notification)<a class="scholar-update" href="{{ route('notifications.index') }}"><span class="sh-update-dot {{ $notification->is_read ? 'is-read' : '' }}" aria-hidden="true"></span><div><strong>{{ $notification->title }}</strong><p>{{ \Illuminate\Support\Str::limit($notification->message, 110) }}</p><time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time></div></a>@empty<div class="scholar-updates-empty"><i class="bi bi-bell" aria-hidden="true"></i><p>You’re all caught up.</p><small>New application updates will appear here.</small></div>@endforelse
        @foreach($announcements->take(2) as $announcement)<div class="scholar-announcement"><span><i class="bi bi-megaphone" aria-hidden="true"></i> FROM THE OFFICE</span><h3>{{ $announcement->title }}</h3><p>{{ \Illuminate\Support\Str::limit($announcement->content, 150) }}</p></div>@endforeach
      </section>
    </aside>
  </div>

  <section class="scholar-opportunities" aria-labelledby="scholarships-title">
    <div class="scholar-section-heading"><div><span class="sh-eyebrow">FIND YOUR OPPORTUNITY</span><h2 id="scholarships-title">A little support. A world of possibility.</h2><p>Explore scholarships currently accepting applications.</p></div><a href="{{ route('scholarships.public') }}" class="sh-text-link">Browse all scholarships <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div>
    <div class="scholar-opportunity-grid">
      @forelse($available->take(3) as $program)
        @php
          $programCategory = strtolower($program->category ?? '');
          $programPhoto = str_contains($programCategory, 'athlet') ? 'scholarship-athletic.jpg' : (str_contains($programCategory, 'merit') || str_contains($programCategory, 'academic') ? 'scholarship-merit.jpg' : (str_contains($programCategory, 'need') || str_contains($programCategory, 'financial') ? 'scholarship-need-based.jpg' : 'scholarship-default.jpg'));
          $photos = [$programPhoto, 'student-hero.jpg', 'scholarship-merit.jpg'];
        @endphp
        <a href="{{ route('scholarships.view', $program) }}" class="scholar-opportunity"><div class="scholar-opportunity-image"><img src="{{ asset('images/dashboard/'.$photos[$loop->index]) }}" alt="" width="900" height="500" loading="lazy"><span><span></span> Applications open</span></div><div class="scholar-opportunity-body"><small>{{ $program->category ?: 'Student scholarship' }}</small><h3>{{ $program->name }}</h3><p>{{ \Illuminate\Support\Str::limit(strip_tags($program->benefits ?: $program->description ?: 'Discover the requirements and support available through this scholarship.'), 105) }}</p><div><span><i class="bi bi-calendar3" aria-hidden="true"></i> {{ $program->deadline ? 'Apply by '.$program->deadline->format('M j, Y') : 'Open for applications' }}</span><span class="scholar-opportunity-arrow"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></span></div></div></a>
      @empty
        <div class="sh-panel scholar-empty"><i class="bi bi-mortarboard" aria-hidden="true"></i><strong>New opportunities are on the way.</strong><p>No scholarships are accepting applications today. Check the directory for future openings.</p><a href="{{ route('scholarships.public') }}" class="sh-text-link">View scholarship directory <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
      @endforelse
    </div>
  </section>
  <footer class="scholar-footer"><span><i class="bi bi-shield-check" aria-hidden="true"></i> Your information is used for scholarship processing.</span><span>ADFC ScholarLink <span aria-hidden="true">&middot;</span> Supporting your next chapter</span></footer>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/student-dashboard.js') }}?v={{ filemtime(public_path('js/student-dashboard.js')) }}" defer></script>
@endpush
