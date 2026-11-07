/* Progressive enhancement: the same form remains usable without JavaScript. */
(function () {
  'use strict';
  const form = document.getElementById('signupForm');
  if (!form) return;

  const steps = [...form.querySelectorAll('[data-signup-step]')];
  const progress = document.querySelector('.signup-progress');
  const indicators = [...progress.querySelectorAll('[data-signup-indicator]')];
  const status = document.getElementById('signupStepStatus');
  const back = document.getElementById('signupBack');
  const next = document.getElementById('signupNext');
  const submit = document.getElementById('signupSubmit');
  const password = document.getElementById('regPw');
  const confirmation = document.getElementById('regPw2');
  const phone = document.getElementById('contactNumber');
  let current = 0;

  function updateValidity() {
    confirmation.setCustomValidity(confirmation.value && confirmation.value !== password.value
      ? 'Enter the same password in both fields.' : '');
    const normalizedPhone = phone.value.replace(/[\s()\-]/g, '');
    phone.setCustomValidity(phone.value && !/^(?:\+?63|0)9\d{9}$/.test(normalizedPhone)
      ? 'Enter a Philippine mobile number, such as 09171234567.' : '');
  }

  function showStep(index, focus = true) {
    current = index;
    steps.forEach((step, i) => { step.hidden = i !== current; });
    indicators.forEach((indicator, i) => {
      indicator.classList.toggle('is-current', i === current);
      indicator.classList.toggle('is-complete', i < current);
      if (i === current) indicator.setAttribute('aria-current', 'step');
      else indicator.removeAttribute('aria-current');
    });
    status.textContent = 'Step ' + (current + 1) + ' of ' + steps.length;
    back.hidden = current === 0;
    next.hidden = current === steps.length - 1;
    submit.hidden = current !== steps.length - 1;
    next.querySelector('span').textContent = current === 0 ? 'Next: school details' : 'Next: password';
    if (focus) steps[current].querySelector('legend').focus({ preventScroll: true });
    if (focus && (progress.getBoundingClientRect().top < 0 || progress.getBoundingClientRect().top > innerHeight / 2)) {
      progress.scrollIntoView({ block: 'start', behavior: 'auto' });
    }
  }

  function validateStep(index) {
    updateValidity();
    const invalid = [...steps[index].querySelectorAll('input')].find(field => !field.checkValidity());
    if (!invalid) return true;
    if (index !== current) showStep(index);
    invalid.reportValidity();
    return false;
  }

  function advance() {
    if (validateStep(current)) showStep(Math.min(current + 1, steps.length - 1));
  }

  next.addEventListener('click', advance);
  back.addEventListener('click', () => showStep(Math.max(current - 1, 0)));
  form.addEventListener('input', updateValidity);
  form.addEventListener('submit', event => {
    if (current < steps.length - 1) {
      event.preventDefault();
      advance();
      return;
    }
    for (let index = 0; index < steps.length; index++) {
      if (!validateStep(index)) {
        event.preventDefault();
        return;
      }
    }
  });

  // Validation remains on the server; client checks reveal the appropriate step.
  form.noValidate = true;
  progress.hidden = false;
  status.hidden = false;
  form.classList.add('signup-enhanced');
  const errorStep = steps.findIndex(step => step.querySelector('[aria-invalid="true"]'));
  showStep(errorStep === -1 ? 0 : errorStep, false);
})();
