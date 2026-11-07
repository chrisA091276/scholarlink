/* Keep the phone/tablet drawer usable with touch, keyboard, and screen readers. */
(function () {
  'use strict';

  const sidebar = document.getElementById('appSidebar');
  const main = document.getElementById('appMain');
  const openButton = document.getElementById('sidebarOpen');
  const closeButton = document.getElementById('sidebarClose');
  const backdrop = document.getElementById('sidebarBackdrop');
  if (!sidebar || !main || !openButton || !closeButton || !backdrop) return;

  const mobile = window.matchMedia('(max-width: 991.98px)');
  let isOpen = false;

  function closeNavigation(restoreFocus = true) {
    isOpen = false;
    document.body.classList.remove('sidebar-open');
    main.inert = false;
    openButton.setAttribute('aria-expanded', 'false');
    sidebar.removeAttribute('role');
    sidebar.removeAttribute('aria-modal');
    if (restoreFocus && mobile.matches) openButton.focus({ preventScroll: true });
    sidebar.inert = mobile.matches;
  }

  function openNavigation() {
    if (!mobile.matches) return;
    isOpen = true;
    sidebar.inert = false;
    sidebar.setAttribute('role', 'dialog');
    sidebar.setAttribute('aria-modal', 'true');
    document.body.classList.add('sidebar-open');
    openButton.setAttribute('aria-expanded', 'true');
    closeButton.focus({ preventScroll: true });
    main.inert = true;
  }

  openButton.addEventListener('click', openNavigation);
  closeButton.addEventListener('click', () => closeNavigation());
  backdrop.addEventListener('click', () => closeNavigation());
  sidebar.addEventListener('click', event => {
    if (isOpen && event.target.closest('a[href]')) closeNavigation();
  });

  document.addEventListener('keydown', event => {
    if (!isOpen) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      closeNavigation();
      return;
    }
    if (event.key !== 'Tab') return;
    const focusable = [...sidebar.querySelectorAll('a[href], button:not([disabled]), [tabindex="0"]')]
      .filter(element => element.getClientRects().length && !element.closest('[inert]'));
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last?.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first?.focus();
    }
  });

  mobile.addEventListener('change', () => {
    const focusWasInSidebar = sidebar.contains(document.activeElement);
    closeNavigation(focusWasInSidebar);
    if (!mobile.matches && document.activeElement === closeButton) {
      sidebar.querySelector('.nav-link.active, .nav-link')?.focus({ preventScroll: true });
    }
  });
  window.addEventListener('pageshow', () => closeNavigation(false));
  closeNavigation(false);
})();
