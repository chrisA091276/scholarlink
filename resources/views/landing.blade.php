<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0a1931">
  <title>ScholarLink — Student Scholarship Management System</title>
  <link href="{{ asset('vendor/bootstrap/5.3.3/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css') }}" rel="stylesheet">
  <link href="{{ asset('css/adfc.css') }}" rel="stylesheet">
  <link href="{{ asset('css/academic.css') }}" rel="stylesheet">
  <link href="{{ asset('css/responsive.css') }}?v={{ filemtime(public_path('css/responsive.css')) }}" rel="stylesheet">
  <link href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}" rel="stylesheet">
</head>
<body class="landing-page">
<nav class="navbar navbar-expand-lg navbar-dark sticky-top landing-header" aria-label="Main navigation">
  <div class="container">
    <a class="navbar-brand landing-brand" href="{{ route('landing') }}" aria-label="ScholarLink — Asian Development Foundation College home">
      <x-brand-logo :size="42" />
      <span class="landing-brand-copy">
        <strong>ScholarLink</strong>
        <span class="landing-brand-school">Asian Development Foundation College</span>
        <span class="landing-brand-short">ADFC Scholarship Portal</span>
      </span>
    </a>
    <button class="navbar-toggler landing-menu-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#landingNavigation" aria-controls="landingNavigation" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon" aria-hidden="true"></span>
    </button>
    <div class="collapse navbar-collapse" id="landingNavigation">
      <div class="landing-nav-links">
        <a href="#how-it-works">How it works</a>
        <a href="#scholarships">Scholarships</a>
        <a href="#application-guide">Application guide</a>
        <a href="#faq">FAQs</a>
      </div>
      <div class="landing-nav-actions">
        @auth
          <a href="{{ route('dashboard') }}" class="btn btn-gold nav-action"><i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>Dashboard</a>
        @else
          <a href="{{ route('login') }}" class="btn btn-nav-ghost nav-action">Log in</a>
          <a href="{{ route('register') }}" class="btn btn-gold nav-action">Sign up<i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
        @endauth
      </div>
    </div>
  </div>
</nav>

@if($announcements->isNotEmpty())
  <div class="ticker py-2 text-white small" style="background:#12295e">
    <span class="ticker-track"><i class="bi bi-megaphone-fill me-1"></i>{{ $announcements->pluck('title')->join('  •  ') }} &nbsp; • &nbsp; {{ $announcements->pluck('title')->join('  •  ') }}</span>
  </div>
@endif

@php
  $campus = collect(['images/adfc-campus.jpg', 'images/adfc-campus.png', 'images/adfc-campus.jpeg'])
    ->first(fn ($path) => file_exists(public_path($path)), 'images/adfc-campus.jpg');
@endphp
<header class="hero-campus has-photo landing-hero">
  <div class="hero-bg" style="background-image:url('{{ asset($campus) }}')"></div>
  <div class="hero-veil"></div>
  <div class="container position-relative text-white">
    <div class="landing-hero-grid">
      <div class="hero-copy">
          <span class="landing-eyebrow"><i class="bi bi-mortarboard" aria-hidden="true"></i>Official ADFC scholarship portal</span>
          <h1>Scholarships made <span class="text-gradient-gold">clearer and easier</span></h1>
          <p class="landing-hero-intro">The official scholarship portal of Asian Development Foundation College — helping deserving students in Tacloban City and beyond apply, track, and achieve their academic dreams since 1984.</p>
          <div class="d-flex gap-2 flex-wrap landing-hero-actions">
            @auth
              <a href="{{ route('dashboard') }}" class="btn btn-gold"><i class="bi bi-speedometer2 me-1"></i>Go to Dashboard</a>
            @else
              <a href="{{ route('register') }}" class="btn btn-gold"><i class="bi bi-person-plus-fill me-1"></i>Create account</a>
              <a href="{{ route('login') }}" class="btn btn-outline-light"><i class="bi bi-box-arrow-in-right me-1"></i>Log in</a>
            @endauth
            <a href="{{ route('scholarships.public') }}" class="btn btn-outline-light"><i class="bi bi-award me-1"></i>Browse scholarships</a>
          </div>
      </div>
      <div class="landing-hero-visual">
        <div class="reveal">
          <div class="poster-frame">
            <img src="{{ asset('images/scholara.png') }}" alt="ADFC scholarship programs — Your Future, Our Priority — Apply Now">
            @auth
              <a href="{{ route('scholarships.public') }}" class="poster-cta"><i class="bi bi-arrow-right-circle-fill"></i>Explore Scholarships</a>
            @else
              <a href="{{ route('register') }}" class="poster-cta"><i class="bi bi-arrow-right-circle-fill"></i>Apply Now</a>
            @endauth
          </div>
        </div>
      </div>
    </div>
  </div>
</header>

<section class="stat-band text-white py-4" aria-label="Scholarship statistics">
  <div class="container"><div class="row text-center g-3">
    <div class="col-6 col-md-3 reveal"><div class="stat-num count-up" data-count="{{ $stats['programs'] }}">0</div><div class="small opacity-75">Open programs</div></div>
    <div class="col-6 col-md-3 reveal reveal-d1"><div class="stat-num count-up" data-count="{{ $stats['slots'] }}">0</div><div class="small opacity-75">Available slots</div></div>
    <div class="col-6 col-md-3 reveal reveal-d2"><div class="stat-num count-up" data-count="{{ $stats['applications'] }}">0</div><div class="small opacity-75">Applications filed</div></div>
    <div class="col-6 col-md-3 reveal reveal-d3"><div class="stat-num count-up" data-count="{{ $stats['recipients'] }}">0</div><div class="small opacity-75">Active scholars</div></div>
  </div></div>
</section>

<section id="how-it-works" class="portal-section portal-anchor bg-white">
  <div class="container">
    <span class="portal-section-kicker">A simpler scholarship journey</span>
    <h2 class="portal-section-title">From finding a program to following your application</h2>
    <p class="portal-section-intro">ScholarLink brings the key steps together so applicants can find current opportunities and keep application details organized.</p>
    <div class="portal-flow-grid">
      <article class="portal-flow-card reveal"><div class="portal-flow-top"><span class="portal-flow-number">STEP 01</span><span class="portal-flow-icon"><i class="bi bi-search"></i></span></div><h3>Explore open programs</h3><p>Compare scholarship categories, available slots, descriptions, and deadlines from the current listings.</p></article>
      <article class="portal-flow-card reveal reveal-d1"><div class="portal-flow-top"><span class="portal-flow-number">STEP 02</span><span class="portal-flow-icon"><i class="bi bi-list-check"></i></span></div><h3>Review the requirements</h3><p>Read the eligibility rules and document checklist for the specific program before you begin.</p></article>
      <article class="portal-flow-card reveal reveal-d2"><div class="portal-flow-top"><span class="portal-flow-number">STEP 03</span><span class="portal-flow-icon"><i class="bi bi-file-earmark-arrow-up"></i></span></div><h3>Complete your application</h3><p>Fill in the requested information and attach the supporting documents listed for that scholarship.</p></article>
      <article class="portal-flow-card reveal reveal-d3"><div class="portal-flow-top"><span class="portal-flow-number">STEP 04</span><span class="portal-flow-icon"><i class="bi bi-bell"></i></span></div><h3>Follow its progress</h3><p>Return to your account to check application updates and any next steps requested by the scholarship team.</p></article>
    </div>
  </div>
</section>

<main id="scholarships" class="py-5 bg-light portal-anchor"><div class="container">
  <div class="portal-section-heading mb-4 reveal">
    <div><span class="portal-section-kicker">Current opportunities</span><h2 class="portal-section-title">Open scholarships</h2><p class="portal-section-intro">Review the programs accepting applications and open a listing for its full details.</p></div>
    <a href="{{ route('scholarships.public') }}" class="btn btn-sm btn-dark">View all programs</a>
  </div>
  <div class="row g-3">
    @forelse($programs as $i => $program)
      <div class="col-md-6 col-lg-4 reveal {{ $i % 3 === 1 ? 'reveal-d1' : ($i % 3 === 2 ? 'reveal-d2' : '') }}">
        <article class="card card-lift h-100"><div class="card-body d-flex flex-column">
          <span class="badge bg-primary align-self-start mb-2">{{ $program->category }}</span>
          <h3 class="h6 fw-bold">{{ $program->name }}</h3>
          <p class="small text-muted flex-grow-1">{{ \Illuminate\Support\Str::limit($program->description, 110) }}</p>
          <div class="small mb-2"><i class="bi bi-calendar-event me-1"></i>Deadline: <b>{{ $program->deadline?->format('M d, Y') ?? '—' }}</b>@if($program->slots) · <i class="bi bi-mortarboard-fill ico-navy"></i> {{ $program->slots }} slots @endif</div>
          <a href="{{ route('scholarships.view', $program) }}" class="btn btn-sm btn-dark">Details &amp; Apply</a>
        </div></article>
      </div>
    @empty
      <div class="col-12"><div class="alert alert-info">No open scholarships right now. Please check back later.</div></div>
    @endforelse
  </div>

  @if($announcements->isNotEmpty())
    <h2 class="h5 fw-bold mt-5 mb-3 reveal">Announcements</h2>
    <div class="row g-3">
      @foreach($announcements as $i => $announcement)
        <div class="col-md-6 col-lg-4 reveal {{ $i === 1 ? 'reveal-d1' : ($i === 2 ? 'reveal-d2' : '') }}"><article class="card card-lift h-100"><div class="card-body"><h3 class="h6 fw-bold">{{ $announcement->title }}</h3><p class="small text-muted">{{ \Illuminate\Support\Str::limit($announcement->content, 120) }}</p></div></article></div>
      @endforeach
    </div>
  @endif
</div></main>

<section id="application-guide" class="portal-section portal-anchor bg-white">
  <div class="container">
    <span class="portal-section-kicker">Prepare with confidence</span>
    <h2 class="portal-section-title">A quick application checklist</h2>
    <p class="portal-section-intro">Every scholarship can have different criteria. Use the program page as the source of truth and make sure your application is complete before submitting.</p>
    <div class="portal-checklist">
      <div class="portal-check-item"><i class="bi bi-check-circle-fill"></i><span><strong>Confirm eligibility.</strong> Check the program's academic and other qualifications.</span></div>
      <div class="portal-check-item"><i class="bi bi-check-circle-fill"></i><span><strong>Note the deadline.</strong> Plan enough time to complete the form and gather documents.</span></div>
      <div class="portal-check-item"><i class="bi bi-check-circle-fill"></i><span><strong>Prepare the listed documents.</strong> Upload clear, readable files in the formats accepted by the portal.</span></div>
      <div class="portal-check-item"><i class="bi bi-check-circle-fill"></i><span><strong>Review before submitting.</strong> Check your answers and confirm each required item is included.</span></div>
    </div>
    <div class="portal-info-grid">
      <article class="portal-info-card"><span class="portal-info-icon"><i class="bi bi-save"></i></span><div><h3>Save and continue</h3><p>Save an application as a draft while you work. If the portal marks requirements incomplete, review the listed items and update your application.</p></div></article>
      <article class="portal-info-card"><span class="portal-info-icon"><i class="bi bi-shield-check"></i></span><div><h3>Know what happens next</h3><p>After submission, authorized scholarship staff review application information and supporting documents. Check your account for status changes or requests.</p></div></article>
    </div>
  </div>
</section>

<section class="portal-section bg-light">
  <div class="container">
    <span class="portal-section-kicker">One portal, clear responsibilities</span>
    <h2 class="portal-section-title">Built around the people managing scholarships</h2>
    <p class="portal-section-intro">Each role has tools for the work they need to do, while scholarship information and application progress stay in one place.</p>
    <div class="portal-role-grid">
      <article class="portal-role-card"><span class="portal-info-icon"><i class="bi bi-mortarboard"></i></span><h3>Student applicants</h3><p>Discover programs, complete applications, upload requirements, and follow updates from their account.</p></article>
      <article class="portal-role-card"><span class="portal-info-icon"><i class="bi bi-person-check"></i></span><h3>Scholarship staff</h3><p>Review assigned applications, verify submitted documents, and record progress or follow-up needs.</p></article>
      <article class="portal-role-card"><span class="portal-info-icon"><i class="bi bi-gear"></i></span><h3>Administrators</h3><p>Manage scholarship programs, user access, and the operational records that support the process.</p></article>
    </div>
    <div class="portal-team-band mt-4 reveal">
      <h2>Transparent review, managed by the scholarship team</h2>
      <p>ScholarLink helps keep applications, document checks, eligibility screening, and status updates organized. Final decisions follow the requirements and review process for each program.</p>
      <div class="portal-team-points"><span><i class="bi bi-check2-circle"></i>Program-specific criteria</span><span><i class="bi bi-file-earmark-check"></i>Document review</span><span><i class="bi bi-arrow-repeat"></i>Application status updates</span></div>
    </div>
  </div>
</section>

<section id="faq" class="portal-section portal-anchor bg-white">
  <div class="container">
    <span class="portal-section-kicker">Helpful information</span>
    <h2 class="portal-section-title">Frequently asked questions</h2>
    <div class="portal-faq-list">
      <details><summary>Who can apply for an ADFC scholarship?</summary><p>Eligibility depends on the scholarship program. Review its criteria and make sure you meet the stated requirements before applying.</p></details>
      <details><summary>Which documents do I need to upload?</summary><p>Required documents vary by program. The scholarship details and application form show the checklist that applies to your selected program.</p></details>
      <details><summary>Can I save an application and finish it later?</summary><p>You can save an application as a draft. Return to your account to complete the missing information and requirements, then submit while the program is open.</p></details>
      <details><summary>How do I know if my application has an update?</summary><p>Sign in and check your application record for its latest status and any instructions from the scholarship team.</p></details>
      <details><summary>Does submitting an application guarantee a scholarship?</summary><p>No. Submission begins the review process. Awards depend on the program's criteria, available slots, and authorized review.</p></details>
    </div>
    <div class="portal-cta reveal">
      <div><h2>Ready to explore your options?</h2><p>Start with the current scholarship listings and review the requirements that fit you.</p></div>
      <div class="d-flex gap-2 flex-wrap"><a href="{{ route('scholarships.public') }}" class="btn btn-dark"><i class="bi bi-search me-1"></i>Browse scholarships</a>@guest<a href="{{ route('register') }}" class="btn btn-outline-primary">Create an account</a>@endguest</div>
    </div>
  </div>
</section>

<footer class="py-4 text-center text-white" style="background:#0a1931">
  <div class="container">
    <div class="d-flex justify-content-center align-items-center gap-2 mb-2"><x-brand-logo :size="34" /><b>ScholarLink</b></div>
    <div class="small opacity-75">© {{ date('Y') }} Asian Development Foundation College · 1984 · Tacloban City · ScholarLink: Student Scholarship Management System</div>
  </div>
</footer>
<script src="{{ asset('vendor/bootstrap/5.3.3/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/adfc.js') }}"></script>
<script src="{{ asset('js/landing-navigation.js') }}?v={{ filemtime(public_path('js/landing-navigation.js')) }}"></script>
</body>
</html>
