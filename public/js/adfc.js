/* ADFC micro-interactions: reveal on scroll, animated counters, navbar shadow, alerts */
(function () {
  'use strict';

  // Reveal on scroll
  var revealEls = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && revealEls.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('revealed'); io.unobserve(e.target); }
      });
    }, { threshold: 0.12 });
    revealEls.forEach(function (el) { io.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add('revealed'); });
  }

  // Animated counters: <h3 class="count-up" data-count="42">0</h3>
  function animateCount(el) {
    var target = parseInt(el.getAttribute('data-count') || '0', 10);
    var dur = 1100, t0 = null;
    function step(ts) {
      if (!t0) t0 = ts;
      var p = Math.min((ts - t0) / dur, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = Math.round(target * eased);
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  var counters = document.querySelectorAll('.count-up[data-count]');
  if ('IntersectionObserver' in window && counters.length) {
    var cio = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { animateCount(e.target); cio.unobserve(e.target); }
      });
    }, { threshold: 0.4 });
    counters.forEach(function (el) { cio.observe(el); });
  } else {
    counters.forEach(animateCount);
  }

  // Animate profile progress bars
  document.querySelectorAll('.progress-bar-anim[data-width]').forEach(function (bar) {
    requestAnimationFrame(function () {
      requestAnimationFrame(function () { bar.style.width = bar.getAttribute('data-width') + '%'; });
    });
  });

  // Topbar shadow on scroll
  var topbar = document.querySelector('.topbar');
  if (topbar) {
    window.addEventListener('scroll', function () {
      topbar.classList.toggle('scrolled', window.scrollY > 8);
    }, { passive: true });
  }

  // Keep the clicked button's name/value in the native form submission.
  // Disabling a submitter here would drop action=draft/action=submit.
  document.querySelectorAll('form').forEach(function (f) {
    f.addEventListener('submit', function (event) {
      if (f.dataset.nativeSubmitting) { event.preventDefault(); return; }
      queueMicrotask(function () {
        // Allow validation and AJAX upload handlers to cancel native submission.
        if (event.defaultPrevented) return;
        f.dataset.nativeSubmitting = '1';
        var btn = event.submitter;
        if (!btn) return;
        btn.setAttribute('aria-disabled', 'true');
        btn.setAttribute('aria-busy', 'true');
        btn.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>');
      });
    });
  });

  window.addEventListener('pageshow', function () {
    document.querySelectorAll('form[data-native-submitting]').forEach(function (f) {
      delete f.dataset.nativeSubmitting;
      f.querySelectorAll('[aria-busy="true"]').forEach(function (btn) {
        btn.removeAttribute('aria-disabled');
        btn.removeAttribute('aria-busy');
        btn.querySelector('.spinner-border')?.remove();
      });
    });
  });

  // Auto-dismiss alerts after 6s
  document.querySelectorAll('.alert-dismissible, .alert.alert-success').forEach(function (a) {
    setTimeout(function () {
      a.classList.add('fade-out');
      setTimeout(function () { a.remove(); }, 450);
    }, 6000);
  });
})();
