/* Keep the mobile menu and anchor offsets in sync with the responsive header. */
(function () {
  'use strict';

  const menu = document.getElementById('landingNavigation');
  const toggle = document.querySelector('.landing-menu-toggle');
  if (!menu || !toggle || !window.bootstrap) return;

  const header = toggle.closest('.landing-header');
  const desktop = window.matchMedia('(min-width: 992px)');
  const collapse = bootstrap.Collapse.getOrCreateInstance(menu, { toggle: false });
  let selectedSection = null;

  function updateHeaderHeight() {
    // An expanded menu must not increase the offset for section destinations.
    if (!header || (!desktop.matches && menu.matches('.show, .collapsing'))) return;
    document.documentElement.style.setProperty('--landing-header-height', Math.ceil(header.getBoundingClientRect().height) + 'px');
  }

  updateHeaderHeight();
  if (header && 'ResizeObserver' in window) new ResizeObserver(updateHeaderHeight).observe(header);

  menu.addEventListener('shown.bs.collapse', () => {
    toggle.setAttribute('aria-label', 'Close navigation');
    if (desktop.matches || selectedSection) collapse.hide();
  });

  menu.addEventListener('click', event => {
    const link = event.target.closest('a[href^="#"]');
    if (!link || desktop.matches) return;
    const section = document.getElementById(link.hash.slice(1));
    if (!section) return;
    event.preventDefault();
    selectedSection = section;
    collapse.hide();
  });

  menu.addEventListener('hidden.bs.collapse', () => {
    toggle.setAttribute('aria-label', 'Open navigation');
    updateHeaderHeight();
    if (!selectedSection) return;
    const section = selectedSection;
    selectedSection = null;
    section.setAttribute('tabindex', '-1');
    section.addEventListener('blur', () => section.removeAttribute('tabindex'), { once: true });
    history.replaceState(null, '', '#' + section.id);
    section.focus({ preventScroll: true });
    section.scrollIntoView({ block: 'start' });
  });

  document.addEventListener('click', event => {
    if (desktop.matches || !menu.classList.contains('show') || header?.contains(event.target)) return;
    if (menu.contains(document.activeElement)) toggle.focus({ preventScroll: true });
    collapse.hide();
  });

  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape' || desktop.matches || !menu.classList.contains('show')) return;
    event.preventDefault();
    toggle.focus({ preventScroll: true });
    collapse.hide();
  });

  desktop.addEventListener('change', () => {
    selectedSection = null;
    if (!desktop.matches && menu.contains(document.activeElement)) toggle.focus({ preventScroll: true });
    if (desktop.matches && document.activeElement === toggle) menu.querySelector('a')?.focus({ preventScroll: true });
    collapse.hide();
    updateHeaderHeight();
  });
})();
