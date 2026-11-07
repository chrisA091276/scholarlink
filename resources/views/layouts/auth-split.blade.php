<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'ScholarLink')</title>
  <link href="{{ asset('vendor/bootstrap/5.3.3/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css') }}" rel="stylesheet">
  <link href="{{ asset('css/adfc.css') }}" rel="stylesheet">
  <link href="{{ asset('css/academic.css') }}" rel="stylesheet">
  <link href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}" rel="stylesheet">
  <link href="{{ asset('css/responsive.css') }}?v={{ filemtime(public_path('css/responsive.css')) }}" rel="stylesheet">
  @php
    $campus = collect(['images/adfc-campus.jpg', 'images/adfc-campus.png', 'images/adfc-campus.jpeg'])->first(fn($path) => file_exists(public_path($path)), 'images/adfc-campus.jpg');
  @endphp
  <style>.auth-split-page { --campus-image: url("{{ asset($campus) }}"); }</style>
</head>
<body class="auth-split-page{{ ($mode ?? 'login') === 'signup' ? ' auth-signup-page' : ' auth-login-page' }}">
  <header class="auth-topbar">
  <a class="auth-home-link" href="{{ route('landing') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to home</a>
  <nav class="auth-role-switcher dropdown" aria-label="Login portals">
    <button class="auth-role-switch dropdown-toggle" type="button" id="roleSwitcher" data-bs-toggle="dropdown" aria-expanded="false" aria-controls="roleMenu">
      <i class="bi bi-arrow-left-right" aria-hidden="true"></i> Switch role
    </button>
    <ul class="dropdown-menu dropdown-menu-end auth-role-menu" id="roleMenu" aria-labelledby="roleSwitcher">
      <li><h2 class="dropdown-header">Choose your login</h2></li>
      @foreach($loginRoles as $role => $details)
        <li><a class="dropdown-item{{ $mode === 'login' && $loginRole === $role ? ' active' : '' }}" href="{{ route('login.role', ['role' => $role]) }}" @if($mode === 'login' && $loginRole === $role) aria-current="page" @endif>
          <i class="bi bi-{{ $details['icon'] }}" aria-hidden="true"></i><span>{{ $details['label'] }}</span>
          @if($mode === 'login' && $loginRole === $role)<i class="bi bi-check2 ms-auto" aria-hidden="true"></i>@endif
        </a></li>
      @endforeach
    </ul>
  </nav>
  </header>
  <main class="auth-shell">
    <section class="auth-form-side" aria-label="Your ScholarLink account">
      <a class="auth-brand" href="{{ route('landing') }}">
        <x-brand-logo :size="42" />
        <span><strong>ScholarLink</strong><small>Asian Development Foundation College</small></span>
      </a>
      <div class="auth-form-content">
        @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
      </div>
      <footer class="auth-form-footer"><a href="{{ route('privacy') }}">Privacy notice</a><span><i class="bi bi-shield-check" aria-hidden="true"></i> ADFC {{ strtolower($portal['label']) }} portal</span></footer>
    </section>
    <section class="auth-showcase" aria-label="Student photo slideshow" aria-roledescription="carousel" data-auth-slideshow>
      <div class="auth-showcase-copy">
        <span class="auth-eyebrow"><span></span> OPPORTUNITIES START WITH YOU</span>
        <h2>Your ambition.<br><span>Our shared future.</span></h2>
        <p>A little support. A world of possibilities.<br>Take your next step with ScholarLink.</p>
      </div>
      <div class="auth-photo-halo" aria-hidden="true"></div>
      <div class="auth-photo-orbit" aria-hidden="true"></div>
      <div class="auth-photo-stage">
        <img class="auth-student-photo is-active" src="{{ asset('images/auth/students-cheerful.png') }}" width="1106" height="1422" alt="Two smiling students in blue uniforms, one making a peace sign." fetchpriority="high" decoding="async" data-slide>
        <img class="auth-student-photo" src="{{ asset('images/auth/students-smiling.png') }}" width="1106" height="1422" alt="Two students in blue uniforms smiling together." aria-hidden="true" decoding="async" data-slide>
        <img class="auth-student-photo" src="{{ asset('images/auth/students-heart.png') }}" width="1106" height="1422" alt="Two students in blue uniforms making a heart with their hands." aria-hidden="true" decoding="async" data-slide>
      </div>
      <div class="auth-showcase-bottom">
        <div class="auth-showcase-caption"><span class="auth-caption-icon"><i class="bi bi-mortarboard" aria-hidden="true"></i></span><span><strong>Dream bigger. Go further.</strong><small>Your scholarship journey starts here.</small></span></div>
      </div>
    </section>
  </main>
  <script src="{{ asset('vendor/bootstrap/5.3.3/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('js/adfc.js') }}"></script>
  <script src="{{ asset('js/auth-slideshow.js') }}?v={{ filemtime(public_path('js/auth-slideshow.js')) }}" defer></script>
  <x-field-errors />
</body>
</html>
