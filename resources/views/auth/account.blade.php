@extends('layouts.auth-split')
@php
  $mode = $mode ?? 'login';
  $loginRole = $loginRole ?? 'student';
  $loginStep = $loginStep ?? 1;
  $loginRoles = [
    'student' => ['label' => 'Student', 'icon' => 'person-fill', 'description' => 'Log in to continue your scholarship journey.', 'password' => 'Student123!'],
    'staff' => ['label' => 'Staff', 'icon' => 'clipboard2-check-fill', 'description' => 'Verify documents and evaluate scholarship applicants.', 'password' => 'Staff123!'],
    'admin' => ['label' => 'Administrator', 'icon' => 'shield-lock-fill', 'description' => 'Manage programs, users, decisions, and reports.', 'password' => 'Admin123!'],
  ];
  $portal = $loginRoles[$loginRole];
  $showDemo = app()->environment('local') && config('scholarlink.show_demo_credentials');
@endphp
@section('title', $mode === 'signup' ? 'Sign up — ScholarLink' : $portal['label'].' login — ScholarLink')
@section('content')

{{-- ============ LOG IN PANEL ============ --}}
@if($mode === 'login')
<div id="panelLogin">
  <div class="auth-portal-label"><i class="bi bi-{{ $portal['icon'] }}" aria-hidden="true"></i>{{ $portal['label'] }} portal</div>
  @if($loginRole === 'student')
    @include('auth.student-login')
  @else
  <div class="auth-heading">
    <h1>{{ $portal['label'] }} login</h1>
    <p id="loginSub">{{ $portal['description'] }}</p>
  </div>
  <form method="POST" action="{{ route('login.role.submit', ['role' => $loginRole]) }}">
  @csrf
  <input type="hidden" name="login_method" value="email">
  <div class="mb-3"><label for="loginEmail" class="form-label">Email address</label>
    <div class="input-group"><span class="input-group-text"><i class="bi bi-envelope"></i></span>
    <input type="email" name="email" id="loginEmail" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="Enter your email address" autocomplete="username" inputmode="email" enterkeyhint="next" autocapitalize="off" spellcheck="false" @error('email') aria-invalid="true" aria-describedby="loginEmail-error" @enderror required></div>
    @error('email')<div class="invalid-feedback d-block" id="loginEmail-error">{{ $message }}</div>@enderror</div>
  <div class="mb-3"><label for="loginPassword" class="form-label">Password</label>
    <div class="input-group"><span class="input-group-text"><i class="bi bi-key"></i></span>
    <input type="password" name="password" id="loginPassword" class="form-control @error('password') is-invalid @enderror" placeholder="Enter your password" autocomplete="current-password" enterkeyhint="go" @error('password') aria-invalid="true" aria-describedby="loginPassword-error" @enderror required>
    <button type="button" class="btn btn-outline-secondary" id="togglePw" title="Show password" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button></div>
    @error('password')<div class="invalid-feedback d-block" id="loginPassword-error">{{ $message }}</div>@enderror</div>
  <div class="auth-login-options mb-3">
  <div class="form-check"><input type="checkbox" name="remember" class="form-check-input" id="rm"><label for="rm" class="form-check-label small">Remember me</label></div>
  <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
  </div>
  <button type="submit" class="btn auth-submit w-100"><span id="loginBtnLabel">Log in as {{ $portal['label'] }}</span><i class="bi bi-arrow-right" aria-hidden="true"></i></button>
  </form>
  @endif
  @if($loginRole === 'student')
  @if($loginStep === 1)
  <p class="auth-account-prompt">New to ScholarLink? <a href="{{ route('register') }}">Create an account</a></p>
  @endif
  @else
  <p class="auth-account-prompt">Use your assigned {{ strtolower($portal['label']) }} account.<br>For account access, contact your administrator.</p>
  @endif
  @if($showDemo)<div class="demo-box small mt-3 p-2 text-center"><b>Fictional demo account:</b> <code id="demoCreds">{{ $loginRole }}@adfc.edu.ph / {{ $portal['password'] }}</code></div>@endif
</div>

{{-- ============ SIGN UP PANEL ============ --}}
@else
@include('auth.signup')
@endif

<script>
(function () {
  document.querySelectorAll('#loginSchoolId, #studentNumber').forEach(function (schoolId) {
  schoolId.addEventListener('input', function (event) {
    // Keep invalid pasted content visible for validation; only format digits.
    if (event.isComposing || !/^\d{0,2}-?\d{0,6}$/.test(this.value)) return;
    var digits = this.value.replace('-', '');
    var formatted = digits.length > 2 ? digits.slice(0, 2) + '-' + digits.slice(2) : digits;
    if (this.value === formatted) return;
    var beforeCaret = this.value.slice(0, this.selectionStart).replace('-', '').length;
    var caret = beforeCaret + (beforeCaret > 2 || (beforeCaret === 2 && event.inputType === 'deleteContentForward' && digits.length > 2) ? 1 : 0);
    this.value = formatted;
    this.setSelectionRange(caret, caret);
  });
  });
  var pw = document.getElementById('loginPassword');
  var toggle = document.getElementById('togglePw');
  if (pw && toggle) toggle.addEventListener('click', function () {
    var show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    this.setAttribute('aria-pressed', String(show));
    this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    this.title = show ? 'Hide password' : 'Show password';
    this.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
  });
  var showPw = document.getElementById('showPw');
  if (showPw) showPw.addEventListener('change', function () {
    var type = this.checked ? 'text' : 'password';
    document.getElementById('regPw').type = type;
    document.getElementById('regPw2').type = type;
  });
})();
</script>
@endsection
