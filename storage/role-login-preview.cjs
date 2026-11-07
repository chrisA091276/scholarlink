const { spawn } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const directory = path.resolve('storage/app/role-login-preview');
fs.mkdirSync(directory, { recursive: true });
const origin = 'http://127.0.0.1:8017';
const browser = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', [
  '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
  '--disable-extensions', '--disable-background-networking', '--remote-debugging-port=9248',
  '--remote-debugging-address=127.0.0.1', '--user-data-dir=' + path.join(directory, 'chrome-' + Date.now()), 'about:blank',
], { windowsHide: true, stdio: 'ignore' });
let socket;
const errors = [], failed = [], checks = [];
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
async function until(fn, label) {
  for (let i = 0; i < 100; i++) {
    try { const value = await fn(); if (value) return value; } catch {}
    await delay(100);
  }
  throw Error('Timed out: ' + label);
}
(async () => {
  const tabs = await until(async () => (await fetch('http://127.0.0.1:9248/json')).json(), 'Chrome');
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
  function call(method, params = {}) {
    return new Promise((resolve, reject) => { const id = ++sequence; pending.set(id, { resolve, reject }); socket.send(JSON.stringify({ id, method, params })); });
  }
  async function evaluate(expression) {
    const result = await call('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (result.exceptionDetails) throw Error(result.exceptionDetails.exception?.description || 'Evaluation failed');
    return result.result.value;
  }
  async function ready(route) {
    await until(() => evaluate(`location.pathname === ${JSON.stringify(route)} && document.readyState === 'complete' && !!document.querySelector('.auth-shell')`), route);
    await evaluate('document.fonts.ready');
    await delay(500);
  }
  async function navigate(route) {
    await call('Page.navigate', { url: origin + route });
    await ready(route);
  }
  async function capture(name) {
    const result = await call('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
    fs.writeFileSync(path.join(directory, name + '.png'), Buffer.from(result.data, 'base64'));
  }
  async function key(key, code, virtualKey) {
    await call('Input.dispatchKeyEvent', { type: 'keyDown', key, code, windowsVirtualKeyCode: virtualKey });
    await call('Input.dispatchKeyEvent', { type: 'keyUp', key, code, windowsVirtualKeyCode: virtualKey });
  }
  await call('Page.enable'); await call('Runtime.enable'); await call('Network.enable');
  for (const width of [1440, 768, 390, 320]) {
    await call('Emulation.setDeviceMetricsOverride', { width, height: 900, deviceScaleFactor: 1, mobile: width < 768 });
    await navigate('/login');
    assert.equal(await evaluate('document.querySelector("h1").textContent'), 'Student login');
    for (const [role, label] of Object.entries({ student: 'Student', staff: 'Staff', admin: 'Administrator' })) {
      await evaluate('document.querySelector("#roleSwitcher").click()');
      assert.equal(await evaluate('document.querySelector("#roleSwitcher").getAttribute("aria-expanded")'), 'true');
      assert(await evaluate('document.documentElement.scrollWidth <= innerWidth'), 'Open menu overflow at ' + width);
      await evaluate(`document.querySelector('#roleMenu a[href$="/login/${role}"]').click()`);
      await ready('/login/' + role);
      assert.equal(await evaluate('document.querySelector("h1").textContent'), label + ' login');
      assert.equal(await evaluate('new URL(document.querySelector("#panelLogin form").action).pathname'), '/login/' + role);
      assert.equal(await evaluate('new URL(document.querySelector("#roleMenu [aria-current=page]").href).pathname'), '/login/' + role);
      assert.equal(await evaluate('!!document.querySelector("#panelSignup")'), false);
      assert.equal(await evaluate('!!document.querySelector(".auth-account-prompt a")'), role === 'student');
      assert(await evaluate('document.documentElement.scrollWidth <= innerWidth'), 'Page overflow at ' + width);
      assert(await evaluate(`(() => { const button = document.querySelector('#roleSwitcher').getBoundingClientRect(); const shell = document.querySelector('.auth-shell').getBoundingClientRect(); return button.right >= innerWidth - 32 && button.top <= 25 && button.bottom <= shell.top; })()`), 'Switcher must be upper right without covering form');
      assert(await evaluate('[...document.querySelectorAll("#panelLogin input:not([type=hidden])")].every(input => input.labels.length > 0)'), 'Labeled login fields');
      await evaluate('document.querySelector("#togglePw").click()');
      assert.equal(await evaluate('document.querySelector("#loginPassword").type'), 'text');
      await evaluate('document.querySelector("#togglePw").click()');
      assert.equal(await evaluate('document.querySelector("#loginPassword").type'), 'password');
      if (width === 1440 || width === 390) await capture(role + '-' + width);
      checks.push(role + ' login, navigation, switcher position and password control at ' + width + 'px');
    }
    await navigate('/register');
    assert(await evaluate('!!document.querySelector("#panelSignup") && !document.querySelector("#panelLogin")'));
    assert(await evaluate('document.documentElement.scrollWidth <= innerWidth'));
    await evaluate('document.querySelector("#showPw").click()');
    assert(await evaluate('document.querySelector("#regPw").type === "text" && document.querySelector("#regPw2").type === "text"'));
    checks.push('Student registration at ' + width + 'px');
  }
  await call('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false });
  await navigate('/login/student');
  await evaluate('document.querySelector("#roleSwitcher").focus()');
  await key('ArrowDown', 'ArrowDown', 40);
  assert.equal(await evaluate('document.querySelector("#roleSwitcher").getAttribute("aria-expanded")'), 'true');
  assert.equal(await evaluate('document.activeElement.textContent.trim()'), 'Student');
  await key('ArrowDown', 'ArrowDown', 40);
  assert.equal(await evaluate('document.activeElement.textContent.trim()'), 'Staff');
  await key('Enter', 'Enter', 13);
  await ready('/login/staff');
  await evaluate('document.querySelector("#roleSwitcher").click()');
  await capture('staff-switcher-desktop');
  await key('Escape', 'Escape', 27);
  assert.equal(await evaluate('document.querySelector("#roleSwitcher").getAttribute("aria-expanded")'), 'false');
  assert.equal(await evaluate('document.activeElement.id'), 'roleSwitcher');
  checks.push('Keyboard navigation, Enter, Escape and focus restoration');
  await call('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
  await navigate('/login/admin');
  assert.equal(await evaluate('getComputedStyle(document.querySelector(".auth-shell")).animationName'), 'none');
  checks.push('Reduced motion');
  assert.deepEqual(errors, [], 'JavaScript errors');
  assert.deepEqual(failed, [], 'Failed asset/page responses');
  fs.writeFileSync(path.join(directory, 'report.json'), JSON.stringify({ checks, errors, failed }, null, 2));
  checks.forEach(check => console.log('PASS ' + check));
  await call('Browser.close');
})().catch(error => { console.error(error); process.exitCode = 1; }).finally(() => { socket?.close(); browser.kill(); });
