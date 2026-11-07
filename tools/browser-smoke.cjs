// Run with node --experimental-websocket. Uses an isolated Chrome profile and QA server.
const { spawn } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const dir = path.resolve('storage/app/browser-qa');
const fixture = JSON.parse(fs.readFileSync(path.join(dir, 'fixture.json'), 'utf8'));
const origin = 'http://127.0.0.1:8001';
const chrome = process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const browser = spawn(chrome, ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
  '--remote-debugging-port=9227', '--remote-debugging-address=127.0.0.1',
  '--user-data-dir=' + path.join(dir, 'chrome-profile-'+Date.now()), 'about:blank'], { windowsHide: true, stdio: 'ignore' });
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
let socket;
let diagnose = async () => {};
async function until(task, description) {
  for (let n = 0; n < 100; n++) {
    try { const result = await task(); if (result) return result; } catch {}
    await pause(150);
  }
  throw Error('Timed out: ' + description);
}
(async () => {
  const tabs = await until(async () => (await fetch('http://127.0.0.1:9227/json')).json(), 'browser startup');
  socket = new WebSocket(tabs.find(tab => tab.type === 'page').webSocketDebuggerUrl);
  await new Promise((resolve, reject) => { socket.onopen = resolve; socket.onerror = reject; });
  let sequence = 0;
  const pending = new Map(), errors = [];
  socket.onmessage = ({ data }) => {
    const message = JSON.parse(data);
    if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.text);
    if (pending.has(message.id)) {
      const { resolve, reject } = pending.get(message.id); pending.delete(message.id);
      message.error ? reject(Error(message.error.message)) : resolve(message.result);
    }
  };
  function call(method, params = {}) {
    return new Promise((resolve, reject) => {
      const id = ++sequence; pending.set(id, { resolve, reject });
      socket.send(JSON.stringify({ id, method, params }));
    });
  }
  async function evaluate(expression) {
    const result = await call('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (result.exceptionDetails) throw Error(result.exceptionDetails.exception?.description || 'Page script failed');
    return result.result.value;
  }
  async function ready(selector) {
    return until(() => evaluate(`document.readyState === 'complete' && Boolean(document.querySelector(${JSON.stringify(selector)}))`), selector);
  }
  async function navigate(url, selector) { await call('Page.navigate', { url: origin + url }); await ready(selector); }
  async function screenshot(name) {
    await pause(350); // Let responsive navigation finish its short layout transition.
    const result = await call('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
    fs.writeFileSync(path.join(dir, name + '.png'), Buffer.from(result.data, 'base64'));
  }
  diagnose = async () => {
    console.error(await evaluate(`JSON.stringify({ path: location.pathname, alerts: [...document.querySelectorAll('.alert')].map(el=>el.innerText), invalid: [...document.querySelectorAll(':invalid')].map(el=>el.name) })`));
    await screenshot('failure');
  };
  async function fill(selector, values) {
    await evaluate(`(() => { const form = document.querySelector(${JSON.stringify(selector)}); const values = ${JSON.stringify(values)};
      for (const [name, value] of Object.entries(values)) { const input = form.elements.namedItem(name); if (!input) throw Error('Missing field '+name); if (input.type === 'checkbox') input.checked = value; else input.value = value; input.dispatchEvent(new Event('change', {bubbles:true})); } })()`);
  }
  await call('Page.enable'); await call('Runtime.enable');
  await call('Emulation.setDeviceMetricsOverride', { width: 1365, height: 1000, deviceScaleFactor: 1, mobile: false });
  await navigate('/scholarships', '.student-page');
  await screenshot('directory-desktop');
  await navigate('/register', '#regPw');
  await screenshot('registration-desktop');
  const suffix = Date.now();
  const schoolId = '99-' + String(suffix).slice(-6);
  await fill('#panelSignup form', { first_name: 'Fictional', last_name: 'Browser Test', email: `browser-${suffix}@example.test`,
    contact_number: '09171234567', student_id_no: schoolId, password: 'BrowserTest123!', password_confirmation: 'BrowserTest123!', terms_accepted: true });
  await evaluate(`document.querySelector('#signupNext').click(); document.querySelector('#signupNext').click(); document.querySelector('#signupSubmit').click()`);
  await ready('a[href$="/profile"]');
  await navigate('/profile/edit', 'input[name="student_id_no"]');
  await fill('form.student-form-card', { student_id_no: schoolId, first_name: 'Fictional', last_name: 'Browser Test',
    date_of_birth: '2004-01-01', contact_number: '09171234567', address: 'Fictional test address', course: 'BSIT', year_level: '2nd', enrollment_status: 'enrolled' });
  await evaluate(`document.querySelector('form.student-form-card button').click()`);
  await until(() => evaluate(`location.pathname === '/profile' && document.readyState === 'complete'`), 'profile save');
  await navigate('/my-applications/create?scholarship_id='+fixture.program_id, '#applicationWizard');
  await fill('#applicationWizard', { 'form_data[study_goal]': 'Fictional study goal', monthly_household_income: '12000' });
  assert.equal(await evaluate(`document.querySelector('[name="monthly_household_income"]').disabled`), false);
  assert.equal(await evaluate(`document.querySelector('#requirementsList').innerText.includes('[TEST] Enrollment record')`), true);
  await evaluate(`document.querySelector('[data-step="5"]').click(); document.querySelector('#applicationWizard button[value="draft"]').click()`);
  await until(() => evaluate(`location.pathname.startsWith('/my-applications/') && location.pathname.endsWith('/edit') && document.readyState === 'complete'`), 'saved draft');
  assert.equal(await evaluate(`document.querySelector('#lastSavedStep').value`), '5');
  const draftUrl = await evaluate('location.pathname');
  const dom = await call('DOM.getDocument');
  const fileInput = await call('DOM.querySelector', { nodeId: dom.root.nodeId, selector: '#upload-'+fixture.requirement_id });
  await call('DOM.setFileInputFiles', { nodeId: fileInput.nodeId, files: [path.join(dir, 'fictional-enrollment.pdf')] });
  await evaluate(`document.querySelector('button[form="upload-form-${fixture.requirement_id}"]').click()`);
  await until(() => evaluate(`document.body.innerText.includes('fictional-enrollment.pdf') && document.readyState === 'complete'`), 'document upload');
  await screenshot('wizard-documents');
  await navigate(draftUrl, '#applicationWizard');
  assert.equal(await evaluate(`document.querySelector('#lastSavedStep').value`), '5');
  await evaluate(`document.querySelector('[data-step="6"]').click()`);
  await call('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
  await screenshot('wizard-review-mobile');
  assert.equal(await evaluate('document.documentElement.scrollWidth <= window.innerWidth'), true, 'Mobile page should not overflow');
  await evaluate(`document.querySelector('[data-step="7"]').click(); document.querySelector('#declaration').checked = true; document.querySelector('#submitApplication').click()`);
  await until(() => evaluate(`location.pathname.startsWith('/my-applications/') && !location.pathname.endsWith('/edit') && !location.pathname.endsWith('/create') && document.readyState === 'complete'`), 'application submission');
  assert.equal(await evaluate(`document.body.innerText.includes('submitted') && document.body.innerText.includes('APP-')`), true);
  await screenshot('submission-mobile');
  assert.deepEqual(errors, [], 'No unhandled page JavaScript exceptions');
  console.log('PASS: public directory, registration, profile, draft/resume, real file upload, mobile review and submission reference.');
  console.log('Screenshots: storage/app/browser-qa/');
  await call('Browser.close');
})().catch(async error => { console.error(error.message); await diagnose().catch(()=>{}); process.exitCode = 1; }).finally(() => { socket?.close(); browser.kill(); });
