// Read-only page checks against the isolated professional preview database.
// Start tools/professional-preview-router.php on port 8017 before running.
const { spawn, spawnSync } = require('node:child_process');
if (!globalThis.WebSocket) {
  const result = spawnSync(process.execPath, ['--experimental-websocket', __filename, ...process.argv.slice(2)], { stdio: 'inherit', windowsHide: true });
  process.exit(result.status ?? 1);
}
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const loginScript = require('./preview-login.cjs');
const baseline = process.argv.includes('--baseline');
const interactions = process.argv.includes('--interactions');
const signupOnly = process.argv.includes('--signup');
const authOnly = process.argv.includes('--auth') || signupOnly;
const landingOnly = process.argv.includes('--landing');
const dashboardsOnly = process.argv.includes('--dashboards') || landingOnly;
const roleFilter = process.argv.find(value => value.startsWith('--role='))?.slice(7);
if (roleFilter) assert(['guest', 'student', 'staff', 'admin'].includes(roleFilter), 'Unknown preview role');
const height = authOnly ? 844 : 900;
const directory = path.resolve('storage/app/responsive-preview');
fs.mkdirSync(directory, { recursive: true });
const origin = 'http://127.0.0.1:8017';
const browser = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', [
  '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--disable-extensions',
  '--remote-debugging-port=9250', '--remote-debugging-address=127.0.0.1',
  '--user-data-dir=' + path.join(directory, 'chrome-' + Date.now()), 'about:blank',
], { windowsHide: true, stdio: 'ignore' });
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
const pages = [], errors = [], failed = [];
let socket;
async function until(fn, label) {
  for (let i = 0; i < 120; i++) {
    try { const result = await fn(); if (result) return result; } catch {}
    await delay(100);
  }
  throw Error('Timed out: ' + label);
}
(async () => {
  const tabs = await until(async () => (await fetch('http://127.0.0.1:9250/json')).json(), 'Chrome');
  socket = new WebSocket(tabs.find(tab => tab.type === 'page').webSocketDebuggerUrl);
  await new Promise((resolve, reject) => { socket.onopen = resolve; socket.onerror = reject; });
  let sequence = 0;
  const pending = new Map();
  socket.onmessage = ({ data }) => {
    const message = JSON.parse(data);
    if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.exception?.description || message.params.exceptionDetails.text);
    if (message.method === 'Network.responseReceived' && message.params.response.status >= 400) failed.push({ url: message.params.response.url, status: message.params.response.status });
    if (pending.has(message.id)) {
      const task = pending.get(message.id); pending.delete(message.id);
      message.error ? task.reject(Error(message.error.message)) : task.resolve(message.result);
    }
  };
  const call = (method, params = {}) => new Promise((resolve, reject) => {
    const id = ++sequence; pending.set(id, { resolve, reject }); socket.send(JSON.stringify({ id, method, params }));
  });
  async function evaluate(expression) {
    const result = await call('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (result.exceptionDetails) throw Error(result.exceptionDetails.exception?.description || 'Evaluation failed');
    return result.result.value;
  }
  async function navigate(route) {
    await call('Page.navigate', { url: origin + route });
    await until(() => evaluate(`location.pathname + location.search === ${JSON.stringify(route)} && document.readyState === 'complete'`), route);
    await evaluate('document.fonts.ready');
  }
  async function capture(name, fullPage = false) {
    const clip = fullPage ? (await call('Page.getLayoutMetrics')).cssContentSize : undefined;
    const result = await call('Page.captureScreenshot', { format: 'png', captureBeyondViewport: fullPage, ...(clip ? { clip: { ...clip, scale: 1 } } : {}) });
    fs.writeFileSync(path.join(directory, name + '.png'), Buffer.from(result.data, 'base64'));
  }
  async function key(key, code, windowsVirtualKeyCode, modifiers = 0) {
    for (const type of ['keyDown', 'keyUp']) await call('Input.dispatchKeyEvent', { type, key, code, windowsVirtualKeyCode, modifiers });
  }
  await call('Page.enable'); await call('Runtime.enable'); await call('Network.enable');
  await call('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
  const routes = {
    guest: ['/', '/login/student', '/login/staff', '/login/admin', '/register', '/forgot-password', '/scholarships', '/scholarships/1', '/privacy'],
    student: ['/dashboard', '/profile', '/profile/edit', '/account', '/my-applications', '/my-applications/1', '/my-applications/1/edit', '/my-applications/create', '/my-documents', '/eligibility-checklist', '/my-awards', '/my-renewals', '/notifications', '/announcements-view'],
    staff: ['/dashboard', '/applications?mine=1', '/applications', '/applications/2', '/applications/2/eligibility', '/verifications', '/screening', '/recommendations?mine=1', '/student-records', '/awards', '/renewals', '/reports', '/reports/recipients', '/account'],
    admin: ['/dashboard', '/users', '/users/create', '/manage-scholarships', '/manage-scholarships/create', '/manage-scholarships/1/manage', '/manage-scholarships/1/edit', '/student-records', '/applications', '/applications/2', '/requirements-manage', '/eligibility-criteria', '/evaluations', '/awards', '/appeals', '/renewals', '/reports', '/reports?tab=students', '/reports?tab=programs', '/reports?tab=eligibility', '/reports/recipients', '/announcements', '/announcements/create', '/audit-logs', '/settings', '/account'],
  };
  const interactionRoutes = {
    guest: ['/', '/login/student', '/register'],
    student: ['/dashboard', '/my-applications/1/edit'],
    admin: ['/dashboard', '/users', '/applications/2', '/settings', '/manage-scholarships/1/manage', '/requirements-manage', '/verifications'],
  };
  const authRoutes = { guest: signupOnly ? ['/register'] : ['/login/student', '/login/student/verify', '/login/staff', '/login/admin', '/register'] };
  const dashboardRoutes = { guest: ['/'], student: ['/dashboard'], staff: ['/dashboard'], admin: ['/dashboard'] };
  const landingRoutes = { guest: ['/'], student: ['/'] };
  for (const [role, paths] of Object.entries(landingOnly ? landingRoutes : dashboardsOnly ? dashboardRoutes : authOnly ? authRoutes : interactions ? interactionRoutes : routes)) {
    if (roleFilter && role !== roleFilter) continue;
    await call('Network.clearBrowserCookies');
    if (role !== 'guest') {
      await navigate('/login/' + role + '?method=email');
      await evaluate(loginScript(role + '@professional-preview.example.test', 'PreviewOnly123!', '26-000001'));
    }
    for (const width of (dashboardsOnly ? [320, 375, 390, 576, 768, 844, 991, 992, 1024, 1280, 1399, 1400, 1440] : authOnly ? [320, 375, 390, 768, 1024, 1440] : interactions ? [320, 390, 768, 991, 992, 1024, 1440] : baseline ? [320, 768, 1024] : [320, 390, 768, 1024, 1440])) {
      const viewportHeight = dashboardsOnly && width === 844 ? 390 : height;
      await call('Emulation.setDeviceMetricsOverride', { width, height: viewportHeight, deviceScaleFactor: 1, mobile: width < 768 || viewportHeight === 390 });
      for (const route of paths) {
        if (route === '/login/student/verify') {
          await navigate('/login/student');
          await evaluate(`(() => { const form = document.querySelector('#studentCredentialsForm'); form.elements.email.value = 'student@professional-preview.example.test'; form.elements.password.value = 'PreviewOnly123!'; form.requestSubmit(); })()`);
          await until(() => evaluate('location.pathname === "/login/student/verify" && document.readyState === "complete" && Boolean(document.querySelector("#studentSchoolIdForm"))'), 'Student verification step');
        } else {
          await navigate(route);
        }
        const result = await evaluate(`(() => {
          const overflowing = [...document.querySelectorAll('body *')].filter(el => {
            const box = el.getBoundingClientRect();
            if (!box.width || !box.height || el.closest('#appSidebar, .table-responsive, .table-scroll-region, .ticker, .hero-bg, .visually-hidden, [hidden]')) return false;
            return box.right > innerWidth + 2 || box.left < -2;
          }).slice(0, 12).map(el => ({ tag: el.tagName, class: el.className, text: el.textContent.trim().slice(0, 65), width: Math.round(el.getBoundingClientRect().width) }));
          return { scrollWidth: document.documentElement.scrollWidth, viewport: innerWidth, overflowing };
        })()`);
        pages.push({ role, route, width, height: viewportHeight, ...result });
        if (dashboardsOnly) {
          if (result.overflowing.length) await capture('layout-problem-' + role + '-' + width);
          assert.deepEqual(result.overflowing, [], 'Visible content stays inside the viewport: ' + role + ' ' + width + ' ' + JSON.stringify(result.overflowing));
          if ([390, 1440].includes(width)) await capture((landingOnly ? 'landing-' : 'dashboard-') + role + '-' + width);
          if (landingOnly && [390, 1440].includes(width)) await capture('landing-full-' + role + '-' + width, true);
          if (route === '/') {
            assert.equal(await evaluate('getComputedStyle(document.querySelector(".landing-menu-toggle")).display !== "none"'), width < 992, 'Navigation switches at the laptop breakpoint');
            assert(await evaluate('(() => { const brand = document.querySelector(".landing-brand").getBoundingClientRect(); const control = document.querySelector(innerWidth < 992 ? ".landing-menu-toggle" : "#landingNavigation").getBoundingClientRect(); return brand.right + 8 <= control.left && brand.top >= 0; })()'), 'Brand and navigation do not overlap');
          }
          if (route === '/' && width < 992) {
            await evaluate('document.querySelector(".landing-menu-toggle").click()');
            await until(() => evaluate('document.querySelector("#landingNavigation").classList.contains("show")'), 'Landing menu opens');
            assert(await evaluate('[...document.querySelectorAll(".landing-nav-links a")].every(el => { const r = el.getBoundingClientRect(); return r.height >= 44 && r.left >= 0 && r.right <= innerWidth; })'), 'All section links are visible and touch-sized');
            if (width === 390) await capture('landing-menu-' + width);
            await key('Escape', 'Escape', 27);
            await until(() => evaluate('!document.querySelector("#landingNavigation").matches(".show, .collapsing")'), 'Escape closes landing menu');
            assert(await evaluate('document.activeElement.matches(".landing-menu-toggle")'), 'Escape restores menu focus');
            await evaluate('document.querySelector(".landing-menu-toggle").click()');
            await until(() => evaluate('document.querySelector("#landingNavigation").classList.contains("show")'), 'Landing menu opens for outside click');
            await evaluate('document.querySelector(".hero-copy h1").click()');
            await until(() => evaluate('!document.querySelector("#landingNavigation").matches(".show, .collapsing")'), 'Outside click closes landing menu');
            await evaluate('document.querySelector(".landing-menu-toggle").click()');
            await until(() => evaluate('document.querySelector("#landingNavigation").classList.contains("show")'), 'Landing menu reopens');
            await evaluate('document.querySelectorAll(".landing-nav-links a")[3].click()');
            await until(() => evaluate('document.activeElement.id === "faq"'), 'Section link focuses the destination');
            assert(await evaluate('document.querySelector("#faq").getBoundingClientRect().top >= document.querySelector(".navbar").getBoundingClientRect().bottom'), 'Section heading clears sticky navigation');
            await evaluate('document.querySelector(".landing-menu-toggle").click()');
            await until(() => evaluate('document.querySelector("#landingNavigation").classList.contains("show")'), 'Landing menu opens before resize');
            await call('Emulation.setDeviceMetricsOverride', { width: 1440, height, deviceScaleFactor: 1, mobile: false });
            await until(() => evaluate('!document.querySelector("#landingNavigation").matches(".show, .collapsing")'), 'Desktop resize resets landing menu');
            await call('Emulation.setDeviceMetricsOverride', { width, height: viewportHeight, deviceScaleFactor: 1, mobile: width < 768 || viewportHeight === 390 });
            await evaluate('scrollTo(0, 0)');
          }
          if (route === '/dashboard' && role !== 'guest' && width < 768) {
            assert(await evaluate('(() => { const table = document.querySelector(".dashboard-records"); return table.scrollWidth <= table.parentElement.clientWidth + 1; })()'), 'Dashboard records do not require horizontal scrolling');
            assert(await evaluate('[...document.querySelectorAll(".dashboard-record-action > a")].every(el => el.getBoundingClientRect().height >= 44)'), 'Application actions are touch-sized');
            if (role === 'student') assert(await evaluate('getComputedStyle(document.querySelector(".scholar-table-date")).display !== "none"'), 'Application dates remain visible on phones');
            await evaluate('document.querySelector(".dashboard-records").closest("section").scrollIntoView(); document.querySelector(".form-evidence-details summary, .scholar-checklists summary")?.click()');
            assert(await evaluate('document.querySelector(".dashboard-records").scrollWidth <= document.querySelector(".dashboard-records").parentElement.clientWidth + 1'), 'Expanded checklists fit the card');
            if (width === 390) await capture('dashboard-records-' + role + '-' + width);
            await evaluate('(() => { const title = document.querySelector(".dashboard-record-title strong"); if (title) title.textContent = "LongScholarshipOrApplicantName".repeat(6); })()');
            assert(await evaluate('document.querySelector(".dashboard-records").scrollWidth <= document.querySelector(".dashboard-records").parentElement.clientWidth + 1'), 'Long record names wrap within cards');
            await evaluate('scrollTo(0, 0)');
          }
        }
        if (authOnly) {
          assert.equal(await evaluate('Boolean(document.querySelector("#loginSchoolId"))'), route === '/login/student/verify');
          if (width < 992) {
            assert.equal(await evaluate('getComputedStyle(document.querySelector(".auth-showcase")).display'), 'none');
            assert(await evaluate('[...document.querySelectorAll(".form-control")].filter(el => el.getClientRects().length).every(el => el.getBoundingClientRect().height >= 49)'), 'Touch-sized form controls');
          }
          if (route === '/login/student/verify') {
            assert(await evaluate('(() => { const field = document.querySelector("#loginSchoolId"); field.value = "12-123"; const invalid = !field.checkValidity(); field.value = "00-000042"; const valid = field.checkValidity(); field.value = ""; return invalid && valid; })()'), 'School ID native format validation');
          }
          if (route.startsWith('/login/') && route !== '/login/student/verify') {
            await evaluate('document.querySelector("#togglePw").click()');
            assert.equal(await evaluate('document.querySelector("#loginPassword").type'), 'text');
            await evaluate('document.querySelector("#togglePw").click()');
            assert.equal(await evaluate('document.querySelector("#loginPassword").type'), 'password');
          }
          await evaluate('document.querySelector("#roleSwitcher").click()');
          assert(await evaluate('document.querySelector("#roleMenu").classList.contains("show")'), 'Role switch opens');
          assert(await evaluate('(() => { const box = document.querySelector("#roleMenu").getBoundingClientRect(); return box.left >= 0 && box.right <= innerWidth + 1; })()'), 'Role menu fits the screen');
          await key('Escape', 'Escape', 27);
          if ([320, 390, 1440].includes(width)) await capture('auth-' + route.replace(/\W+/g, '-') + '-' + width);
          if (route === '/register') {
            await evaluate('document.querySelector("#signupNext").click()');
            assert.equal(await evaluate('document.querySelector("[data-signup-step]:not([hidden])").dataset.signupStep'), '0', 'Empty personal details cannot advance');
            await evaluate('document.querySelector("#firstName").value = "Fictional"; document.querySelector("#lastName").value = "Applicant"; document.querySelector("#signupNext").click()');
            assert.equal(await evaluate('document.querySelector("[data-signup-step]:not([hidden])").dataset.signupStep'), '1');
            assert(await evaluate('document.documentElement.scrollWidth <= innerWidth + 2'), 'School details fit at ' + width);
            if ([320, 390, 1440].includes(width)) await capture('signup-school-' + width);
            await evaluate('document.querySelector("#studentNumber").focus()');
            await call('Input.insertText', { text: '26000001' });
            assert.equal(await evaluate('document.querySelector("#studentNumber").value'), '26-000001', 'School ID is formatted while typing');
            await evaluate('document.querySelector("#registerEmail").value = "student@professional-preview.example.test"; document.querySelector("#contactNumber").value = "09171234567"; document.querySelector("#signupNext").click()');
            assert.equal(await evaluate('document.querySelector("[data-signup-step]:not([hidden])").dataset.signupStep'), '2');
            assert(await evaluate('document.documentElement.scrollWidth <= innerWidth + 2'), 'Password and consent fit at ' + width);
            await evaluate('document.querySelector("#showPw").click()');
            assert(await evaluate('document.querySelector("#regPw").type === "text" && document.querySelector("#regPw2").type === "text"'), 'Both passwords can be revealed');
            await evaluate('document.querySelector("#showPw").click(); document.querySelector("#signupBack").click()');
            assert.equal(await evaluate('document.querySelector("#studentNumber").value'), '26-000001', 'Back preserves school details');
            await evaluate('document.querySelector("#signupNext").click()');
            if ([320, 390, 1440].includes(width)) await capture('signup-password-' + width);
          }
        }
        if (result.scrollWidth > width + 2) console.log('OVERFLOW', role, width, route, JSON.stringify(result.overflowing.slice(0, 3)));
        if (width === 320 && ['/', '/dashboard', '/users', '/applications/2', '/my-applications/1/edit', '/settings'].includes(route)) await capture((baseline ? 'before-' : 'after-') + role + '-' + route.replace(/\W+/g, '-') + '-' + width);
        if (!baseline && route === '/dashboard' && width < 992) {
          assert(await evaluate('document.querySelector("#appSidebar").inert'), 'Closed navigation cannot receive focus');
          await evaluate('document.querySelector("#sidebarOpen").click()');
          assert.equal(await evaluate('document.querySelector("#sidebarOpen").getAttribute("aria-expanded")'), 'true');
          assert(await evaluate('document.querySelector("#appSidebar").contains(document.activeElement)'), 'Focus enters mobile navigation');
          assert.equal(await evaluate('getComputedStyle(document.body).overflowY'), 'hidden', 'Open drawer locks background scrolling');
          assert(await evaluate('document.querySelector("#appMain").inert'), 'Background cannot receive focus while navigation is open');
          await evaluate('document.querySelector(".sidebar-profile").focus()');
          await key('Tab', 'Tab', 9);
          assert.equal(await evaluate('document.activeElement.id'), 'sidebarClose', 'Tab stays inside navigation');
          await key('Tab', 'Tab', 9, 8);
          assert(await evaluate('document.activeElement.classList.contains("sidebar-profile")'), 'Shift-Tab wraps to last navigation link');
          await key('Escape', 'Escape', 27);
          assert.equal(await evaluate('document.querySelector("#sidebarOpen").getAttribute("aria-expanded")'), 'false');
          assert.equal(await evaluate('document.activeElement.id'), 'sidebarOpen');
          await evaluate('document.querySelector("#sidebarOpen").click(); document.querySelector("#sidebarBackdrop").click()');
          assert.equal(await evaluate('document.querySelector("#sidebarOpen").getAttribute("aria-expanded")'), 'false');
          if (interactions || dashboardsOnly) {
            await evaluate('document.querySelector("#sidebarOpen").click()');
            await capture('drawer-' + role + '-' + width);
            await call('Emulation.setDeviceMetricsOverride', { width: 1024, height: 900, deviceScaleFactor: 1, mobile: false });
            await until(() => evaluate('!document.body.classList.contains("sidebar-open")'), 'desktop navigation reset');
            assert(await evaluate('!document.querySelector("#appMain").inert && !document.querySelector("#appSidebar").inert'), 'Desktop content and navigation are available');
            await call('Emulation.setDeviceMetricsOverride', { width, height: viewportHeight, deviceScaleFactor: 1, mobile: width < 768 || viewportHeight === 390 });
            await until(() => evaluate('document.querySelector("#appSidebar").inert'), 'mobile navigation reset');
          }
        }
        if (interactions && route.endsWith('/edit') && role === 'student') {
          for (let step = 1; step <= 7; step++) {
            await evaluate(`document.querySelector('[data-step="${step}"]').click()`);
            assert(await evaluate(`!document.querySelector('[data-panel="${step}"]').classList.contains('d-none')`), 'Application step ' + step + ' opens');
            assert(await evaluate('document.documentElement.scrollWidth <= innerWidth + 2'), 'Application step ' + step + ' fits at ' + width);
            assert(await evaluate(`[...document.querySelectorAll('[data-panel="${step}"] .form-control, [data-panel="${step}"] .form-select')].filter(el => el.getClientRects().length).every(el => el.getBoundingClientRect().right <= innerWidth + 2)`), 'Application inputs stay in view');
            if (width === 320 && [2, 5, 7].includes(step)) await capture('wizard-step-' + step + '-' + width);
          }
        }
      }
      console.log('CHECKED', role, width, paths.length + ' pages');
    }
  }
  if (authOnly && !signupOnly) {
    await call('Emulation.setDeviceMetricsOverride', { width: 390, height, deviceScaleFactor: 1, mobile: true });
    await navigate('/login/student');
    await evaluate(`(() => { const form = document.querySelector('#studentCredentialsForm'); form.elements.email.value = 'student@professional-preview.example.test'; form.elements.password.value = 'PreviewOnly123!'; form.requestSubmit(); })()`);
    await until(() => evaluate('location.pathname === "/login/student/verify" && document.readyState === "complete" && Boolean(document.querySelector("#studentSchoolIdForm"))'), 'Credentials advance to school ID');
    assert(await evaluate('!document.querySelector("#loginPassword") && document.querySelector(".auth-login-steps [aria-current=step]").textContent.includes("School ID")'), 'Only step 2 is active');
    await evaluate('document.querySelector("#loginSchoolId").focus()');
    for (const text of '00000042') await call('Input.insertText', { text });
    assert.equal(await evaluate('document.querySelector("#loginSchoolId").value'), '00-000042', 'Numeric typing inserts the hyphen and preserves leading zeroes');
    await evaluate('document.querySelector("#loginSchoolId").setSelectionRange(3, 3)');
    await key('Backspace', 'Backspace', 8);
    assert.equal(await evaluate('document.querySelector("#loginSchoolId").selectionStart'), 2, 'Backspace can cross the hyphen');
    await key('Backspace', 'Backspace', 8);
    assert.equal(await evaluate('document.querySelector("#loginSchoolId").value'), '00-00042', 'Backspace removes a digit');
    await evaluate('document.querySelector("#loginSchoolId").select()');
    await call('Input.insertText', { text: '00-000042' });
    assert.equal(await evaluate('document.querySelector("#loginSchoolId").value'), '00-000042', 'Formatted paste is preserved');
    await evaluate('document.querySelector("#loginSchoolId").select()');
    await call('Input.insertText', { text: '00000042' });
    assert.equal(await evaluate('document.querySelector("#loginSchoolId").value'), '00-000042', 'Numeric paste is formatted');
    await evaluate('document.querySelector("#loginSchoolId").setSelectionRange(2, 2)');
    await key('Delete', 'Delete', 46);
    await key('Delete', 'Delete', 46);
    assert.equal(await evaluate('document.querySelector("#loginSchoolId").value'), '00-00042', 'Forward delete can cross the hyphen');
    await evaluate('document.querySelector("#loginSchoolId").select()');
    await call('Input.insertText', { text: 'AA-000042' });
    assert.equal(await evaluate('document.querySelector("#loginSchoolId").value'), 'AA-000042', 'Invalid paste remains visible for correction');
    assert.equal(await evaluate('document.querySelector("#loginSchoolId").checkValidity()'), false);
    // Bypass native validation to verify the server-rendered error layout too.
    await evaluate('document.querySelector("#panelLogin form").submit()');
    await until(() => evaluate('document.readyState === "complete" && Boolean(document.querySelector("#loginSchoolId-error"))'), 'School ID validation error');
    for (const width of [320, 390]) {
      await call('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: true });
      assert(await evaluate('document.documentElement.scrollWidth <= innerWidth + 2'), 'Validation errors fit at ' + width);
      assert(await evaluate('(() => { const field = document.querySelector("#loginSchoolId"); return field.getAttribute("aria-describedby").includes("schoolIdHelp") && field.getAttribute("aria-describedby").includes("loginSchoolId-error"); })()'), 'Help and error are both associated with School ID');
      assert.equal(await evaluate('document.querySelectorAll("#loginSchoolId-error").length'), 1, 'School ID error is rendered once');
      await evaluate('document.activeElement.blur(); scrollTo(0, 0)');
      await capture('auth-errors-' + width);
    }
    await evaluate('document.querySelector(".auth-step-back").click()');
    await until(() => evaluate('location.pathname === "/login/student" && document.readyState === "complete" && Boolean(document.querySelector("#loginEmail"))'), 'Back returns to credentials');
    assert.equal(await evaluate('document.querySelector("#loginEmail").value'), 'student@professional-preview.example.test', 'Back retains the account email');
    assert.equal(await evaluate('document.querySelector("#loginPassword").value'), '', 'Back does not expose the password');
    for (const route of ['/login/student', '/login']) {
      await call('Network.clearBrowserCookies');
      await navigate(route);
      await evaluate(`(() => { const form = document.querySelector('#studentCredentialsForm'); form.elements.email.value = 'student@professional-preview.example.test'; form.elements.password.value = 'PreviewOnly123!'; form.requestSubmit(); })()`);
      await until(() => evaluate('location.pathname === "/login/student/verify" && document.readyState === "complete" && Boolean(document.querySelector("#studentSchoolIdForm"))'), route + ' step 2');
      await evaluate(`(() => { const form = document.querySelector('#studentSchoolIdForm'); form.elements.student_id_no.value = '26-999999'; form.requestSubmit(); })()`);
      await until(() => evaluate('document.readyState === "complete" && Boolean(document.querySelector("#loginSchoolId-error"))'), 'Wrong ID is rejected');
      assert.equal(await evaluate('location.pathname'), '/login/student/verify');
      await evaluate(`(() => { const form = document.querySelector('#studentSchoolIdForm'); form.elements.student_id_no.value = '26-000001'; form.requestSubmit(); })()`);
      await until(() => evaluate('location.pathname === "/dashboard" && document.readyState === "complete"'), route + ' completes both steps');
      assert(await evaluate('Boolean(document.querySelector(".scholar-home"))'), 'Student dashboard after school ID verification');
    }
  }
  if (signupOnly) {
    await call('Emulation.setDeviceMetricsOverride', { width: 390, height, deviceScaleFactor: 1, mobile: true });
    // The last layout check leaves a complete account step using existing fixture details.
    await evaluate('document.querySelector("#regPw").value = "SignupPreview123!"; document.querySelector("#regPw2").value = "Different123!"; document.querySelector("#termsAccepted").checked = true; document.querySelector("#signupForm").requestSubmit()');
    assert(await evaluate('!document.querySelector("#regPw2").checkValidity()'), 'Mismatched passwords cannot submit');
    assert(await evaluate('!document.querySelector("#signupForm").dataset.nativeSubmitting'), 'Validation does not lock the form');
    await evaluate('document.querySelector("#regPw2").value = "SignupPreview123!"; document.querySelector("#signupForm").requestSubmit()');
    await until(() => evaluate('document.readyState === "complete" && Boolean(document.querySelector("#studentNumber-error"))'), 'Server rejects duplicate school ID and email');
    assert.equal(await evaluate('document.querySelector("[data-signup-step]:not([hidden])").dataset.signupStep'), '1', 'Server errors return to the school step');
    assert.equal(await evaluate('document.querySelector("#firstName").value'), 'Fictional', 'Personal details survive validation');
    assert(await evaluate('document.querySelector("#regPw").value === "" && document.querySelector("#regPw2").value === ""'), 'Passwords are never returned in HTML');
    assert(await evaluate('document.querySelector("#termsAccepted").checked'), 'Consent stays checked after validation');
    assert(await evaluate('document.querySelector("#studentNumber").getAttribute("aria-describedby").includes("studentNumber-help") && document.querySelector("#studentNumber").getAttribute("aria-describedby").includes("studentNumber-error")'), 'Help and error stay associated with School ID');
    for (const width of [320, 390, 1440]) {
      await call('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 768 });
      assert(await evaluate('document.documentElement.scrollWidth <= innerWidth + 2'), 'Registration errors fit at ' + width);
      await evaluate('document.activeElement.blur(); scrollTo(0, 0)');
      await capture('signup-errors-' + width, true);
    }
    const signupEmail = 'signup-' + Date.now() + '@professional-preview.example.test';
    const signupId = '98-' + String(Date.now()).slice(-6);
    await evaluate(`document.querySelector('#registerEmail').value = ${JSON.stringify(signupEmail)}; document.querySelector('#studentNumber').value = ${JSON.stringify(signupId)}; document.querySelector('#signupNext').click(); document.querySelector('#regPw').value = 'SignupPreview123!'; document.querySelector('#regPw2').value = 'SignupPreview123!'; document.querySelector('#signupForm').requestSubmit()`);
    await until(() => evaluate('location.pathname === "/dashboard" && document.readyState === "complete"'), 'Account creation completes');
    assert(await evaluate('Boolean(document.querySelector(".scholar-home"))'), 'New student reaches their dashboard');
    await call('Network.clearBrowserCookies');
    await navigate('/login/student');
    await evaluate(loginScript(signupEmail, 'SignupPreview123!', signupId));
    await navigate('/dashboard');
    assert(await evaluate('Boolean(document.querySelector(".scholar-home"))'), 'Registered ID works for subsequent two-step login');
    await call('Network.clearBrowserCookies');
    await call('Emulation.setScriptExecutionDisabled', { value: true });
    await navigate('/register');
    assert(await evaluate('[...document.querySelectorAll("[data-signup-step]")].every(el => !el.hidden && el.getBoundingClientRect().height > 0)'), 'All registration fields remain available without JavaScript');
    assert(await evaluate('document.querySelector("#signupSubmit").getBoundingClientRect().height >= 44'), 'Native submit remains available without JavaScript');
    await call('Emulation.setScriptExecutionDisabled', { value: false });
  }
  const overflow = pages.filter(page => page.scrollWidth > page.width + 2);
  fs.writeFileSync(path.join(directory, signupOnly ? 'signup-report.json' : landingOnly ? 'landing-report.json' : dashboardsOnly ? (roleFilter ? 'dashboard-' + roleFilter + '-report.json' : 'dashboard-report.json') : authOnly ? 'auth-report.json' : interactions ? 'interaction-report.json' : baseline ? 'baseline.json' : 'report.json'), JSON.stringify({ pages, overflow, errors, failed }, null, 2));
  console.log(JSON.stringify({ pages: pages.length, overflow: overflow.length, errors, failed }));
  if (!baseline) { assert.deepEqual(overflow, []); assert.deepEqual(errors, []); assert.deepEqual(failed, []); }
  await call('Browser.close');
})().catch(error => { console.error(error); process.exitCode = 1; }).finally(() => { socket?.close(); browser.kill(); });
