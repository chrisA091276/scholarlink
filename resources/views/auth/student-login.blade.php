<ol class="auth-login-steps" aria-label="Student login progress">
  <li @class(['is-current' => $loginStep === 1, 'is-complete' => $loginStep === 2]) @if($loginStep === 1) aria-current="step" @endif>
    <span class="auth-step-number" aria-hidden="true">@if($loginStep === 2)<i class="bi bi-check-lg"></i>@else 1 @endif</span>
    <span><strong>Account</strong><small>Email &amp; password</small></span>
  </li>
  <li @class(['is-current' => $loginStep === 2]) @if($loginStep === 2) aria-current="step" @endif>
    <span class="auth-step-number" aria-hidden="true">2</span>
    <span><strong>School ID</strong><small>Confirm your ID</small></span>
  </li>
</ol>

<div class="auth-heading">
  <span class="auth-step-caption">Step {{ $loginStep }} of 2</span>
  <h1>{{ $loginStep === 1 ? 'Student login' : 'Verify your school ID' }}</h1>
  <p id="loginSub">{{ $loginStep === 1 ? 'Enter your email and password to continue.' : 'Enter the school ID registered to your account to finish signing in.' }}</p>
</div>

@if($loginStep === 1)
<form method="POST" action="{{ route('login.role.submit', ['role' => 'student']) }}" id="studentCredentialsForm">
  @csrf
  <input type="hidden" name="login_method" value="email">
  <div class="mb-3">
    <label for="loginEmail" class="form-label">Email address</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span>
      <input type="email" name="email" id="loginEmail" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $loginEmail ?? '') }}" placeholder="Enter your email address" autocomplete="username" inputmode="email" enterkeyhint="next" autocapitalize="off" spellcheck="false" @error('email') aria-invalid="true" aria-describedby="loginEmail-error" @enderror required>
    </div>
    @error('email')<div class="invalid-feedback d-block" id="loginEmail-error">{{ $message }}</div>@enderror
  </div>
  <div class="mb-3">
    <label for="loginPassword" class="form-label">Password</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-key" aria-hidden="true"></i></span>
      <input type="password" name="password" id="loginPassword" class="form-control @error('password') is-invalid @enderror" placeholder="Enter your password" autocomplete="current-password" enterkeyhint="go" @error('password') aria-invalid="true" aria-describedby="loginPassword-error" @enderror required>
      <button type="button" class="btn btn-outline-secondary" id="togglePw" title="Show password" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
    </div>
    @error('password')<div class="invalid-feedback d-block" id="loginPassword-error">{{ $message }}</div>@enderror
  </div>
  <div class="auth-login-options mb-3">
    <div class="form-check"><input type="checkbox" name="remember" class="form-check-input" id="rm"><label for="rm" class="form-check-label">Remember me</label></div>
    <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
  </div>
  <button type="submit" class="btn auth-submit w-100"><span id="loginBtnLabel">Next: verify school ID</span><i class="bi bi-arrow-right" aria-hidden="true"></i></button>
</form>
@else
<div class="auth-verified-account">
  <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
  <span><small>Account confirmed</small><strong>{{ $loginEmail }}</strong></span>
</div>
@if(! $hasSchoolId)
  <div class="auth-id-help" role="status"><i class="bi bi-info-circle" aria-hidden="true"></i><p>Your account has no registered School ID yet. Contact the scholarship office to update your record before signing in.</p></div>
@else
<form method="POST" action="{{ route('login.student.verify.submit') }}" id="studentSchoolIdForm">
  @csrf
  <div class="mb-3">
    <label for="loginSchoolId" class="form-label">School ID#</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-person-vcard" aria-hidden="true"></i></span>
      <input type="text" name="student_id_no" id="loginSchoolId" class="form-control @error('student_id_no') is-invalid @enderror" value="{{ old('student_id_no') }}" placeholder="00-000000" pattern="[0-9]{2}-[0-9]{6}" maxlength="9" inputmode="numeric" enterkeyhint="go" autocomplete="off" autocapitalize="off" spellcheck="false" title="School ID#: 00-000000 (two digits, a hyphen, then six digits)" aria-describedby="schoolIdHelp @error('student_id_no') loginSchoolId-error @enderror" @error('student_id_no') aria-invalid="true" @enderror required autofocus>
    </div>
    <div class="form-text" id="schoolIdHelp">Use the format <strong>00-000000</strong>, including any leading zeros.</div>
    @error('student_id_no')<div class="invalid-feedback d-block" id="loginSchoolId-error">{{ $message }}</div>@enderror
  </div>
  <button type="submit" class="btn auth-submit w-100"><span id="loginBtnLabel">Verify &amp; log in</span><i class="bi bi-arrow-right" aria-hidden="true"></i></button>
</form>
<p class="auth-id-note">Need help with your school ID? Contact the scholarship office.</p>
@endif
<a href="{{ route('login.role', ['role' => 'student']) }}" class="auth-step-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to email &amp; password</a>
@endif
