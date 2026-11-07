<div id="panelSignup">
  <div class="auth-heading">
    <h1>Create your account</h1>
    <p id="regSub">Start your scholarship journey with ScholarLink.</p>
  </div>

  <ol class="signup-progress" aria-label="Registration progress" hidden>
    <li data-signup-indicator="0"><span aria-hidden="true">1</span><strong>Personal</strong></li>
    <li data-signup-indicator="1"><span aria-hidden="true">2</span><strong>School</strong></li>
    <li data-signup-indicator="2"><span aria-hidden="true">3</span><strong>Password</strong></li>
  </ol>
  <p class="visually-hidden" id="signupStepStatus" aria-live="polite" hidden></p>

  <form method="POST" action="{{ route('register') }}" id="signupForm">
    @csrf
    <fieldset class="signup-section" data-signup-step="0">
      <legend id="signupPersonalTitle" tabindex="-1">Your personal details</legend>
      <p class="signup-section-intro">Use your name as it appears on your school records. Only fields marked optional can be left blank.</p>
      <div class="signup-name-grid">
        <x-auth-input name="first_name" id="firstName" label="First name" autocomplete="given-name" placeholder="First name" maxlength="100" enterkeyhint="next" required />
        <x-auth-input name="last_name" id="lastName" label="Last name" autocomplete="family-name" placeholder="Last name" maxlength="100" enterkeyhint="next" required />
        <x-auth-input name="middle_name" id="middleName" label="Middle name" :optional="true" autocomplete="additional-name" placeholder="Middle name" maxlength="100" enterkeyhint="next" />
        <x-auth-input name="suffix" id="nameSuffix" label="Suffix" :optional="true" autocomplete="honorific-suffix" placeholder="Jr., III" maxlength="30" enterkeyhint="next" />
      </div>
    </fieldset>

    <fieldset class="signup-section" data-signup-step="1">
      <legend id="signupSchoolTitle" tabindex="-1">Your school account</legend>
      <p class="signup-section-intro">Use an email you can access and the School ID assigned to you.</p>
      <x-auth-input name="email" id="registerEmail" type="email" label="Email address" icon="envelope" autocomplete="email" placeholder="you@example.com" maxlength="255" inputmode="email" autocapitalize="off" spellcheck="false" enterkeyhint="next" required />
      <x-auth-input name="student_id_no" id="studentNumber" label="School ID#" icon="person-vcard" placeholder="00-000000" pattern="[0-9]{2}-[0-9]{6}" maxlength="9" inputmode="numeric" autocomplete="off" autocapitalize="off" spellcheck="false" title="Use two digits, a hyphen, then six digits: 00-000000" help="Use the format 00-000000, including leading zeros. You will verify this ID when you log in." enterkeyhint="next" required />
      <x-auth-input name="contact_number" id="contactNumber" type="tel" label="Mobile number" icon="telephone" autocomplete="tel" placeholder="09171234567" inputmode="tel" maxlength="30" help="Philippine mobile number, for example 09171234567 or +639171234567." enterkeyhint="next" required />
    </fieldset>

    <fieldset class="signup-section" data-signup-step="2">
      <legend id="signupPasswordTitle" tabindex="-1">Protect your account</legend>
      <p class="signup-section-intro">Choose a password you will use to sign in.</p>
      <x-auth-input name="password" id="regPw" type="password" label="Password" icon="key" autocomplete="new-password" placeholder="Create a password" minlength="8" maxlength="72" :help="\App\Support\PasswordRules::HELP" enterkeyhint="next" required />
      <x-auth-input name="password_confirmation" id="regPw2" type="password" label="Confirm password" icon="key-fill" autocomplete="new-password" placeholder="Re-enter your password" minlength="8" maxlength="72" enterkeyhint="done" required />
      <div class="signup-check"><input type="checkbox" class="form-check-input" id="showPw"><label for="showPw" class="form-check-label">Show passwords</label></div>
      <div class="signup-consent">
        <div class="signup-check"><input type="checkbox" name="terms_accepted" value="1" class="form-check-input @error('terms_accepted') is-invalid @enderror" id="termsAccepted" @checked(old('terms_accepted')) @error('terms_accepted') aria-invalid="true" aria-describedby="termsAccepted-error" @enderror required><label for="termsAccepted" class="form-check-label">I accept the <a href="{{ route('privacy') }}" target="_blank" rel="noopener">privacy notice and terms<span class="visually-hidden"> (opens in a new tab)</span></a>.</label></div>
        @error('terms_accepted')<div class="invalid-feedback d-block" id="termsAccepted-error">{{ $message }}</div>@enderror
      </div>
    </fieldset>

    <div class="signup-actions">
      <button type="button" class="btn signup-back" id="signupBack" hidden><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</button>
      <button type="button" class="btn auth-submit" id="signupNext" hidden><span>Next: school details</span><i class="bi bi-arrow-right" aria-hidden="true"></i></button>
      <button type="submit" class="btn auth-submit" id="signupSubmit"><span id="regBtnLabel">Create account</span><i class="bi bi-arrow-right" aria-hidden="true"></i></button>
    </div>
  </form>
  <p class="auth-account-prompt">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
</div>
<script src="{{ asset('js/signup.js') }}?v={{ filemtime(public_path('js/signup.js')) }}" defer></script>
