(() => {
  const video = document.getElementById('scholarCampusVideo');
  const toggle = document.getElementById('scholarVideoToggle');
  if (!video || !toggle) return;

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const saveData = navigator.connection?.saveData === true;
  let wantsPlayback = !reducedMotion.matches && !saveData;
  let inView = true;
  let failed = false;
  video.muted = true;
  toggle.hidden = false;

  function updateButton() {
    const playing = !video.paused;
    toggle.setAttribute('aria-label', playing ? 'Pause background video' : 'Play background video');
    toggle.querySelector('i').className = playing ? 'bi bi-pause-fill' : 'bi bi-play-fill';
    toggle.querySelector('span').textContent = playing ? 'Pause video' : 'Play video';
  }

  function syncPlayback() {
    if (failed) return;
    if (wantsPlayback && inView && !document.hidden) {
      if (!video.getAttribute('src')) video.src = video.dataset.src;
      video.play().catch(() => updateButton());
    } else {
      video.pause();
    }
  }

  toggle.addEventListener('click', () => {
    wantsPlayback = video.paused;
    syncPlayback();
  });
  video.addEventListener('playing', () => {
    video.classList.add('is-ready');
    updateButton();
  });
  video.addEventListener('pause', updateButton);
  video.addEventListener('error', () => {
    failed = true;
    video.classList.remove('is-ready');
    toggle.hidden = true;
  });
  reducedMotion.addEventListener('change', () => {
    wantsPlayback = !reducedMotion.matches && !saveData;
    syncPlayback();
  });
  document.addEventListener('visibilitychange', syncPlayback);
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(([entry]) => {
      inView = entry.isIntersecting;
      syncPlayback();
    }, { threshold: 0.05 }).observe(video);
  }
  syncPlayback();
})();
