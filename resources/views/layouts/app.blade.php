<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'ScholarLink')</title>
<link href="{{ asset('vendor/bootstrap/5.3.3/css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css') }}" rel="stylesheet">
<link href="{{ asset('css/adfc.css') }}" rel="stylesheet">
<style>
:root{--navy:#0a1931;--navy2:#12295e;--gold:#c9a227;--gold2:#e6c84a}
body{background:#f4f6fb;font-family:'Segoe UI',system-ui,sans-serif}
.sidebar{background:linear-gradient(180deg,var(--navy),var(--navy2));min-height:100vh;position:sticky;top:0;height:100vh;overflow-y:auto;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.22) transparent}
.sidebar::-webkit-scrollbar{width:5px}.sidebar::-webkit-scrollbar-thumb{background:rgba(255,255,255,.22);border-radius:99px}
.sidebar .nav-pills{gap:3px}.sidebar .nav-link{display:flex;align-items:center;color:#cdd6f4;border-radius:.7rem;margin:0;padding:.65rem .75rem;font-size:.82rem;line-height:1.2;transition:background .18s ease,color .18s ease,transform .18s ease}.sidebar .nav-link i{width:1.35rem;color:#9fb9dc;font-size:.98rem}.sidebar .nav-link:hover{background:rgba(255,255,255,.08);color:#fff;transform:translateX(2px)}.sidebar .nav-link:hover i{color:#f0d269}.sidebar .nav-link.active{background:linear-gradient(90deg,rgba(201,162,39,.28),rgba(201,162,39,.1));color:#fff;box-shadow:inset 3px 0 0 var(--gold)}.sidebar .nav-link.active i{color:#f0d269}
.sidebar .brand{color:#fff}
.gold{color:var(--gold)} .btn-gold{background:var(--gold);border-color:var(--gold);color:#0a1931;font-weight:600}
.btn-gold:hover{background:var(--gold2);border-color:var(--gold2);color:#0a1931}
.btn-navy{background:var(--navy);border-color:var(--navy);color:#fff} .btn-navy:hover{background:var(--navy2);color:#fff}
.card{border:none;border-radius:.9rem;box-shadow:0 2px 12px rgba(10,25,49,.08)}
.stat-card{border-left:5px solid var(--gold)}
.topbar{background:rgba(255,255,255,.92);border-bottom:1px solid #e5e9f2}
.badge-status{text-transform:capitalize}
.sidebar .brand{letter-spacing:.01em}.sidebar-close{display:none;color:#dce7fa;background:transparent;border:0;font-size:1.1rem;padding:.25rem}.sidebar-profile{display:flex;align-items:center;gap:10px;margin-top:20px;padding:12px 8px;border-top:1px solid rgba(255,255,255,.12);border-radius:0 0 12px 12px;color:#dbe6f6;text-decoration:none;transition:background-color .2s ease}.sidebar-profile:hover{background:rgba(255,255,255,.055);color:#fff}.sidebar-profile-copy{min-width:0;flex:1}.sidebar-profile strong,.sidebar-profile small{display:block;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.sidebar-profile strong{font-size:.75rem}.sidebar-profile small{color:#9fb1cc;font-size:.64rem;margin-top:2px}.role-avatar{position:relative;display:grid;place-items:center;flex:0 0 36px;width:36px;height:36px;border:1px solid rgba(255,255,255,.36);border-radius:12px;color:#fff;font-size:.7rem;font-weight:800;letter-spacing:.02em;line-height:1;box-shadow:0 3px 9px rgba(0,0,0,.15)}.role-avatar-admin{background:linear-gradient(140deg,#263e70,#b48d22)}.role-avatar-staff{background:linear-gradient(140deg,#124b72,#38a4a0)}.role-avatar-student{background:linear-gradient(140deg,#14548b,#56a6cf)}.role-avatar-image{position:absolute;inset:0;width:100%;height:100%;border-radius:inherit;object-fit:cover}.role-avatar i{position:absolute;right:-4px;bottom:-4px;display:grid;place-items:center;width:16px;height:16px;border:2px solid #fff;border-radius:50%;background:#fff;color:#173254;font-size:.52rem;box-shadow:0 2px 5px rgba(0,0,0,.16)}.role-avatar-admin i{color:#9b7611}.role-avatar-staff i{color:#178279}.role-avatar-student i{color:#236fa5}.sidebar-profile-arrow{color:#8ea1bd;font-size:.65rem;transition:transform .2s ease,color .2s ease}.sidebar-profile:hover .sidebar-profile-arrow{color:#f0d269;transform:translateX(2px)}
.sidebar-section-label{display:block;padding:14px .75rem 5px;color:#8295b4;font-size:.58rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
</style>
<style>
/* Shared dashboard polish: a calm, editorial workspace with small moments of motion. */
.dashboard-reveal{opacity:0;animation:dashboardReveal .72s cubic-bezier(.16,1,.3,1) both}
.dashboard-reveal.reveal-d1{animation-delay:.08s}.dashboard-reveal.reveal-d2{animation-delay:.16s}.dashboard-reveal.reveal-d3{animation-delay:.24s}.dashboard-reveal.reveal-d4{animation-delay:.32s}
@keyframes dashboardReveal{from{opacity:0;transform:translateY(17px) scale(.99)}to{opacity:1;transform:none}}
@keyframes dashboardDrift{0%,100%{transform:translate3d(0,0,0) rotate(0)}50%{transform:translate3d(-10px,8px,0) rotate(3deg)}}
.ops-dashboard{max-width:1640px;margin-inline:auto}
.ops-dashboard .ops-page-head{position:relative;padding:4px 2px 2px}
.ops-dashboard .ops-page-head h1{font-size:clamp(1.75rem,2.8vw,2.65rem);letter-spacing:-.06em}
.ops-dashboard .ops-eyebrow{display:inline-flex;align-items:center;gap:8px;color:#6f819b}
.ops-dashboard .ops-eyebrow:before{content:'';width:22px;height:3px;border-radius:10px;background:linear-gradient(90deg,#c9a227,#6aa1d7)}
.ops-dashboard .ops-page-head p{max-width:720px;font-size:.88rem}
.ops-dashboard .ops-hero{min-height:205px;padding:30px 32px;border-radius:24px;background-color:#0b2041;background-size:cover;background-position:center;box-shadow:0 18px 38px rgba(10,25,49,.16);isolation:isolate}
.ops-dashboard-admin .ops-hero{background-image:linear-gradient(105deg,rgba(7,25,52,.97) 10%,rgba(12,45,87,.88) 58%,rgba(12,42,75,.58)),url('{{ asset('images/dashboard/admin-hero.jpg') }}')}
.ops-dashboard-staff .ops-hero{background-image:linear-gradient(105deg,rgba(7,25,52,.97) 10%,rgba(18,59,104,.88) 58%,rgba(18,62,77,.58)),url('{{ asset('images/dashboard/staff-hero.jpg') }}')}
.ops-dashboard .ops-hero:before{content:'';position:absolute;right:7%;top:-54px;width:205px;height:205px;border:1px solid rgba(255,255,255,.14);border-radius:50%;box-shadow:0 0 0 22px rgba(255,255,255,.025),0 0 0 44px rgba(255,255,255,.018);animation:dashboardDrift 9s ease-in-out infinite;pointer-events:none}
.ops-dashboard .ops-hero:after{background:radial-gradient(circle at 82% 15%,rgba(230,200,74,.3),transparent 24%),linear-gradient(90deg,transparent 35%,rgba(255,255,255,.06));z-index:-1}
.ops-dashboard .ops-hero-copy h2{font-size:clamp(1.45rem,2.5vw,2.05rem);letter-spacing:-.05em}
.ops-dashboard .ops-hero-copy p{max-width:620px;font-size:.8rem}
.ops-dashboard .ops-hero-metric{min-width:112px;padding:14px 15px;border-radius:15px;background:rgba(255,255,255,.11);box-shadow:inset 0 1px 0 rgba(255,255,255,.12);transition:transform .25s ease,background-color .25s ease}
.ops-dashboard .ops-hero-metric:hover{transform:translateY(-4px);background:rgba(255,255,255,.17)}
.ops-dashboard .ops-hero-metric strong{font-size:1.45rem;letter-spacing:-.04em}
.ops-dashboard .ops-stat-grid{gap:14px}
.ops-dashboard .ops-stat{position:relative;overflow:hidden;min-height:104px;border-radius:18px;box-shadow:0 8px 24px rgba(16,35,63,.055);transition:transform .25s cubic-bezier(.16,1,.3,1),box-shadow .25s ease,border-color .25s ease}
.ops-dashboard .ops-stat:before{content:'';position:absolute;left:0;top:18px;bottom:18px;width:3px;border-radius:0 5px 5px 0;background:#6fa7dc;opacity:.75}
.ops-dashboard .ops-stat.gold:before{background:#c9a227}.ops-dashboard .ops-stat.green:before{background:#2e9e5b}.ops-dashboard .ops-stat.red:before{background:#d64545}.ops-dashboard .ops-stat.warning:before{background:#e07b39}
.ops-dashboard .ops-stat:hover{transform:translateY(-5px);box-shadow:0 16px 32px rgba(16,35,63,.11);border-color:#c5d8eb}
.ops-dashboard .ops-stat.warning .ops-stat-icon{color:#a05c20;background:#fff0df}
.ops-dashboard .ops-stat-value{font-size:1.7rem;letter-spacing:-.06em}
.ops-dashboard .ops-panel{border-radius:20px;box-shadow:0 8px 24px rgba(16,35,63,.05);transition:transform .25s ease,box-shadow .25s ease,border-color .25s ease}
.ops-dashboard .ops-panel:hover{transform:translateY(-2px);border-color:#d5e2ef;box-shadow:0 14px 32px rgba(16,35,63,.08)}
.ops-dashboard .ops-panel-head h2{font-size:1rem;letter-spacing:-.035em}
.ops-dashboard .ops-panel-head h2 i{display:inline-grid;place-items:center;width:28px;height:28px;margin-right:7px!important;border-radius:9px;color:#2863a2;background:#eaf2fc;font-size:.78rem;vertical-align:-3px}
.ops-dashboard .ops-quick{min-height:48px;border-radius:13px;transition:transform .2s ease,background-color .2s ease,border-color .2s ease}
.ops-dashboard .ops-quick:hover{transform:translateY(-2px)}
.student-dashboard .dashboard-reveal{animation-duration:.78s}
.student-dashboard .student-hero{box-shadow:0 18px 38px rgba(16,35,63,.16)}
.student-dashboard .student-stat-card,.student-dashboard .dashboard-panel{transition:transform .25s cubic-bezier(.16,1,.3,1),box-shadow .25s ease,border-color .25s ease}
.student-dashboard .student-stat-card:hover,.student-dashboard .dashboard-panel:hover{transform:translateY(-3px);border-color:#cbdced;box-shadow:0 14px 30px rgba(16,35,63,.09)}
.student-dashboard .opportunity-photo img,.student-dashboard .student-hero-visual img{transition:transform .6s cubic-bezier(.16,1,.3,1)}
.student-dashboard .scholarship-opportunity:hover .opportunity-photo img{transform:scale(1.06)}
@media(max-width:767px){.ops-dashboard .ops-hero{padding:24px 21px}.ops-dashboard .ops-page-head h1{font-size:1.8rem}.ops-dashboard .ops-hero:before{right:-50px}.ops-dashboard .ops-stat{min-height:90px}.ops-dashboard .ops-stat-value{font-size:1.35rem}}
@media(prefers-reduced-motion:reduce){.dashboard-reveal{opacity:1;animation:none}.ops-dashboard .ops-hero:before{animation:none}.ops-dashboard .ops-stat,.ops-dashboard .ops-panel,.student-dashboard .student-stat-card,.student-dashboard .dashboard-panel{transition:none}.student-dashboard .opportunity-photo img,.student-dashboard .student-hero-visual img{transition:none}}
</style>
<link href="{{ asset('css/academic.css') }}" rel="stylesheet">
<link href="{{ asset('css/dashboard-metrics.css') }}?v={{ filemtime(public_path('css/dashboard-metrics.css')) }}" rel="stylesheet">
<link href="{{ asset('css/workspace.css') }}?v={{ filemtime(public_path('css/workspace.css')) }}" rel="stylesheet">
<link href="{{ asset('css/adfc-form.css') }}?v={{ filemtime(public_path('css/adfc-form.css')) }}" rel="stylesheet">
@stack('styles')
<link href="{{ asset('css/responsive.css') }}?v={{ filemtime(public_path('css/responsive.css')) }}" rel="stylesheet">
</head>
<body class="app-shell">
<div class="container-fluid">
<div class="row app-layout">
<nav id="appSidebar" class="col-lg-2 col-md-3 sidebar p-3" aria-label="Main navigation" tabindex="-1">
  <div class="d-flex align-items-center mb-4">
    <x-brand-logo :size="46" class="me-2" />
    <div class="brand ms-2"><div class="fw-bold small">ScholarLink</div><div class="small opacity-75">Scholarship System</div></div>
    <button type="button" class="sidebar-close ms-auto" id="sidebarClose" aria-label="Close navigation"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
  </div>
  @auth
  @php
    $currentUser = auth()->user();
    $r = $currentUser->role;
    $roleDisplay = ['admin' => 'Administrator', 'staff' => 'Scholarship staff', 'student' => 'Student'][$r] ?? ucfirst($r);
    $roleIcon = ['admin' => 'bi-shield-check', 'staff' => 'bi-clipboard-check', 'student' => 'bi-mortarboard'][$r] ?? 'bi-person';
    $avatarInitials = collect(preg_split('/\s+/', trim($currentUser->name)) ?: [])->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: 'U';
    $profileUrl = route('account.edit');
    $avatarUrl = $currentUser->avatar_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($currentUser->avatar_path)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($currentUser->avatar_path)
        : null;
  @endphp
  <div class="nav flex-column nav-pills">
    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
    @if($r==='admin')
      <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="bi bi-people me-2"></i>User Management</a>
      <a class="nav-link {{ request()->routeIs('scholarships.index','scholarships.create','scholarships.show','scholarships.edit') ? 'active' : '' }}" href="{{ route('scholarships.index') }}"><i class="bi bi-award me-2"></i>Scholarship Programs</a>
      <a class="nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}" href="{{ route('students.index') }}"><i class="bi bi-person-lines-fill me-2"></i>Student Records</a>
      <a class="nav-link {{ request()->routeIs('applications.*') ? 'active' : '' }}" href="{{ route('applications.index') }}"><i class="bi bi-inboxes me-2"></i>Applications</a>
      <a class="nav-link {{ request()->routeIs('requirements.manage') ? 'active' : '' }}" href="{{ route('requirements.manage') }}"><i class="bi bi-file-check me-2"></i>Requirements</a>
      <a class="nav-link {{ request()->routeIs('eligibility.manage','eligibility.show') ? 'active' : '' }}" href="{{ route('eligibility.manage') }}"><i class="bi bi-list-check me-2"></i>Eligibility Criteria</a>
      <a class="nav-link {{ request()->routeIs('evaluations.*','screening.*') ? 'active' : '' }}" href="{{ route('evaluations.index') }}"><i class="bi bi-clipboard-check me-2"></i>Evaluations & Decisions</a>
      <a class="nav-link {{ request()->routeIs('awards.*') ? 'active' : '' }}" href="{{ route('awards.index') }}"><i class="bi bi-trophy me-2"></i>Scholarship Awards</a>
      <a class="nav-link {{ request()->routeIs('appeals.*') ? 'active' : '' }}" href="{{ route('appeals.index') }}"><i class="bi bi-chat-square-text me-2"></i>Scholarship Appeals</a>
      <a class="nav-link {{ request()->routeIs('renewals.*') ? 'active' : '' }}" href="{{ route('renewals.index') }}"><i class="bi bi-arrow-repeat me-2"></i>Renewals</a>
      <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="bi bi-bar-chart me-2"></i>Reports & Analytics</a>
      <a class="nav-link {{ request()->routeIs('announcements.index') ? 'active' : '' }}" href="{{ route('announcements.index') }}"><i class="bi bi-megaphone me-2"></i>Announcements</a>
      <a class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}"><i class="bi bi-journal-text me-2"></i>Audit Logs</a>
      <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}"><i class="bi bi-sliders me-2"></i>System Settings</a>
      <a class="nav-link {{ request()->routeIs('account.*') ? 'active' : '' }}" href="{{ route('account.edit') }}"><i class="bi bi-person-circle me-2"></i>My Profile</a>
    @elseif($r==='staff')
      <span class="sidebar-section-label">Application review</span>
      <a class="nav-link {{ request()->routeIs('applications.index') && request('mine') ? 'active' : '' }}" href="{{ route('applications.index',['mine'=>1]) }}"><i class="bi bi-inbox me-2"></i>Assigned Applications</a>
      <a class="nav-link {{ request()->routeIs('verifications.*') || request()->routeIs('documents.verify') ? 'active' : '' }}" href="{{ route('verifications.index') }}"><i class="bi bi-file-check me-2"></i>Document Verification</a>
      <a class="nav-link {{ request()->routeIs('screening.*','eligibility.show') ? 'active' : '' }}" href="{{ route('screening.index') }}"><i class="bi bi-list-check me-2"></i>Eligibility Screening</a>
      <a class="nav-link {{ request()->routeIs('applications.index') && !request('mine') ? 'active' : '' }}" href="{{ route('applications.index') }}"><i class="bi bi-clipboard-check me-2"></i>Application Evaluation</a>
      <a class="nav-link {{ request()->routeIs('recommendations.*') ? 'active' : '' }}" href="{{ route('recommendations.index',['mine'=>1]) }}"><i class="bi bi-send me-2"></i>Recommendations</a>
      <span class="sidebar-section-label">Students & awards</span>
      <a class="nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}" href="{{ route('students.index') }}"><i class="bi bi-person-lines-fill me-2"></i>Student Profiles</a>
      <a class="nav-link {{ request()->routeIs('awards.*') ? 'active' : '' }}" href="{{ route('awards.index') }}"><i class="bi bi-trophy me-2"></i>Scholarship Awards</a>
      <a class="nav-link {{ request()->routeIs('renewals.*') ? 'active' : '' }}" href="{{ route('renewals.index') }}"><i class="bi bi-arrow-repeat me-2"></i>Renewal Review</a>
      <span class="sidebar-section-label">Insights</span>
      <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="bi bi-bar-chart me-2"></i>Reports</a>
      <span class="sidebar-section-label">Account</span>
      <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><i class="bi bi-bell me-2"></i>Notifications</a>
      <a class="nav-link {{ request()->routeIs('account.*') ? 'active' : '' }}" href="{{ route('account.edit') }}"><i class="bi bi-person-circle me-2"></i>My Profile</a>
    @else
      <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}"><i class="bi bi-person-circle me-2"></i>My Profile</a>
      <a class="nav-link {{ request()->routeIs('scholarships.public') ? 'active' : '' }}" href="{{ route('scholarships.public') }}"><i class="bi bi-award me-2"></i>Available Scholarships</a>
      <a class="nav-link {{ request()->routeIs('my-applications.create') ? 'active' : '' }}" href="{{ route('my-applications.create') }}"><i class="bi bi-pencil-square me-2"></i>Apply for Scholarship</a>
      <a class="nav-link {{ request()->routeIs('my-applications.index','my-applications.show') ? 'active' : '' }}" href="{{ route('my-applications.index') }}"><i class="bi bi-file-earmark-text me-2"></i>My Applications</a>
      <a class="nav-link {{ request()->routeIs('documents.my') ? 'active' : '' }}" href="{{ route('documents.my') }}"><i class="bi bi-folder2-open me-2"></i>My Documents</a>
      <a class="nav-link {{ request()->routeIs('eligibility.checklist') ? 'active' : '' }}" href="{{ route('eligibility.checklist') }}"><i class="bi bi-list-check me-2"></i>Eligibility Checklist</a>
      <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><i class="bi bi-bell me-2"></i>Notifications</a>
      <a class="nav-link {{ request()->routeIs('my-awards','my-appeals.*') ? 'active' : '' }}" href="{{ route('my-awards') }}"><i class="bi bi-trophy me-2"></i>Scholarship Status</a>
      <a class="nav-link {{ request()->routeIs('my-renewals.*','renewals.create') ? 'active' : '' }}" href="{{ route('my-renewals.index') }}"><i class="bi bi-arrow-repeat me-2"></i>Renewal Applications</a>
      <a class="nav-link {{ request()->routeIs('announcements.view') ? 'active' : '' }}" href="{{ route('announcements.view') }}"><i class="bi bi-question-circle me-2"></i>Help & Announcements</a>
      <a class="nav-link {{ request()->routeIs('account.*') ? 'active' : '' }}" href="{{ route('account.edit') }}"><i class="bi bi-gear me-2"></i>Account Settings</a>
    @endif
    <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf<button class="nav-link w-100 text-start border-0"><i class="bi bi-box-arrow-right me-2"></i>Logout</button></form>
  </div>
  @endauth
  @auth
  <a class="sidebar-profile" href="{{ $profileUrl }}" aria-label="Manage {{ $roleDisplay }} profile for {{ $currentUser->name }}">
    <span class="role-avatar role-avatar-{{ $r }}">
      @if($avatarUrl)<img class="role-avatar-image" src="{{ $avatarUrl }}" alt="">@else<span>{{ $avatarInitials }}</span>@endif
      <i class="bi {{ $roleIcon }}"></i>
    </span>
    <span class="sidebar-profile-copy"><strong>{{ $currentUser->name }}</strong><small>Manage {{ $roleDisplay }} profile</small></span>
    <i class="bi bi-chevron-right sidebar-profile-arrow"></i>
  </a>
  @endauth
</nav>
<div id="sidebarBackdrop" aria-hidden="true"></div>
<main id="appMain" class="col-lg-10 col-md-9 p-0 app-main">
  <header class="workspace-header sticky-top" aria-label="Workspace header">
    <div class="workspace-context">
      <button type="button" class="workspace-menu" id="sidebarOpen" aria-label="Open navigation" aria-controls="appSidebar" aria-expanded="false"><i class="bi bi-list" aria-hidden="true"></i></button>
      <div class="workspace-heading">
        <span class="workspace-eyebrow">ADFC ScholarLink <span aria-hidden="true">/</span> {{ ['admin' => 'Administration', 'staff' => 'Staff workspace', 'student' => 'Student portal'][$r ?? ''] ?? 'Scholarship portal' }}</span>
        <div class="workspace-title">@yield('page-title', 'Scholarship Management')</div>
      </div>
    </div>
    <div class="workspace-actions">
      @auth
        @php $unread = auth()->user()->notifications()->where('is_read', false)->count(); @endphp
        <time class="workspace-date" datetime="{{ now()->toDateString() }}"><i class="bi bi-calendar3" aria-hidden="true"></i>{{ now()->format('M j, Y') }}</time>
        <a href="{{ route('notifications.index') }}" class="workspace-icon-button" aria-label="Notifications{{ $unread ? ', '.$unread.' unread' : '' }}" title="Notifications">
          <i class="bi bi-bell" aria-hidden="true"></i>
          @if($unread)
            <span class="workspace-notification-count" aria-hidden="true">{{ $unread > 99 ? '99+' : $unread }}</span>
          @endif
        </a>
        <a href="{{ $profileUrl }}" class="workspace-profile" aria-label="Manage {{ $roleDisplay }} profile for {{ $currentUser->name }}">
          <span class="role-avatar role-avatar-{{ $r }}">
            @if($avatarUrl)<img class="role-avatar-image" src="{{ $avatarUrl }}" alt="">@else<span>{{ $avatarInitials }}</span>@endif
            <i class="bi {{ $roleIcon }}"></i>
          </span>
          <span class="workspace-profile-copy">
            <strong>{{ $currentUser->name }}</strong>
            <small>{{ $roleDisplay }}</small>
          </span>
        </a>
        <form method="POST" action="{{ route('logout') }}" class="workspace-logout-form">
          @csrf
          <button class="workspace-icon-button workspace-logout" aria-label="Log out" title="Log out"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></button>
        </form>
      @else
        <a href="{{ route('login') }}" class="btn btn-sm btn-navy">Log in</a>
        <a href="{{ route('register') }}" class="btn btn-sm btn-gold">Sign up</a>
      @endauth
    </div>
  </header>
  <div class="p-4 page-enter app-content">
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    @if(config('scholarlink.verify_email') && auth()->user()?->isStudent() && !auth()->user()->hasVerifiedEmail())
<div class="alert alert-warning">Verify your email before submitting an application. <a href="{{ route('verification.notice') }}">Verify email</a></div>
@endif
@yield('content')
  </div>
</main>
</div>
</div>
<script src="{{ asset('vendor/bootstrap/5.3.3/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/adfc.js') }}"></script>
<script src="{{ asset('js/navigation.js') }}?v={{ filemtime(public_path('js/navigation.js')) }}"></script>
@stack('scripts')
<x-field-errors />
</body>
</html>
