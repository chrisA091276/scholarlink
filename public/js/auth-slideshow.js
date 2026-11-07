(() => {
  const carousel = document.querySelector('[data-auth-slideshow]');
  if (!carousel) return;
  const slides = [...carousel.querySelectorAll('[data-slide]')];
  const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let current = 0;
  let timer;
  let ready = false;
  let paused = motion.matches;

  function show(index) {
    current = index;
    slides.forEach((slide, i) => {
      slide.classList.toggle('is-active', i === current);
      slide.setAttribute('aria-hidden', String(i !== current));
    });
  }

  function schedule() {
    window.clearInterval(timer);
    carousel.classList.toggle('is-paused', paused || document.hidden);
    if (ready && !paused && !document.hidden) {
      timer = window.setInterval(() => show((current + 1) % slides.length), 2000);
    }
  }

  document.addEventListener('visibilitychange', schedule);
  motion.addEventListener('change', () => { paused = motion.matches; schedule(); });
  window.addEventListener('pagehide', () => window.clearInterval(timer));
  window.addEventListener('pageshow', schedule);

  // Start only once all three photos can be displayed without an empty frame.
  Promise.all(slides.map(slide => slide.decode())).then(() => {
    ready = true;
    schedule();
  }).catch(() => {
    // Keep the first available image visible if an asset cannot load.
    const available = slides.findIndex(slide => slide.complete && slide.naturalWidth > 0);
    if (available >= 0) show(available);
    paused = true;
    schedule();
  });
  schedule();
})();
