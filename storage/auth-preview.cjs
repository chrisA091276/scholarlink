const { spawn } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const dir = path.resolve('storage/app/auth-students-preview');
fs.mkdirSync(dir, { recursive: true });
const browser = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', [
  '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
  '--remote-debugging-port=9232', '--remote-debugging-address=127.0.0.1',
  '--user-data-dir=' + path.join(dir, 'chrome-profile-' + Date.now()), 'about:blank'
], { windowsHide: true, stdio: 'ignore' });
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
let socket;
async function until(task) {
  for (let n = 0; n < 100; n++) { try { const value = await task(); if (value) return value; } catch {} await pause(150); }
  throw Error('Timed out waiting for browser/page');
}
(async () => {
  const tabs = await until(async () => (await fetch('http://127.0.0.1:9232/json')).json());
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
  const call = (method, params = {}) => new Promise((resolve, reject) => {
    const id = ++sequence; pending.set(id, { resolve, reject }); socket.send(JSON.stringify({ id, method, params }));
  });
  const evaluate = async expression => {
    const result = await call('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (result.exceptionDetails) throw Error(result.exceptionDetails.exception?.description || 'Page script failed');
    return result.result.value;
  };
  await call('Page.enable'); await call('Runtime.enable');
  const navigate = async route => {
    await call('Page.navigate', { url: 'http://127.0.0.1:8002' + route });
    await until(() => evaluate(`location.pathname === '${route}' && document.readyState === 'complete' && !!document.querySelector('.auth-shell')`));
    await evaluate('document.fonts.ready');
  };
  for (const width of (process.argv.includes('--visual') ? [1440, 390] : [1440, 768, 390, 320])) {
    await call('Emulation.setDeviceMetricsOverride', { width, height: 1000, deviceScaleFactor: 1, mobile: width < 768 });
    for (const route of ['/login', '/register']) {
      await navigate(route);
      await until(() => evaluate(`!document.querySelector('.auth-slideshow-controls').hidden`));
      assert.equal(await evaluate(`[...document.querySelectorAll('[data-slide]')].every(image => image.complete && image.naturalWidth > 0)`), true, 'All three student photos loaded');
      if (width >= 768) assert.equal(await evaluate(`document.querySelector('.auth-showcase').getBoundingClientRect().left >= document.querySelector('.auth-form-side').getBoundingClientRect().right - 1`), true, 'Photos are on the right');
      assert.equal(await evaluate('document.documentElement.scrollWidth <= innerWidth'), true, route + ' overflow at ' + width);
      assert.equal(await evaluate(`!![...document.styleSheets].find(sheet => sheet.href?.includes('bootstrap.min.css') && (() => { try { return sheet.cssRules.length > 0; } catch { return true; } })())`), true);
      assert.equal(await evaluate(`document.querySelector('.auth-form-side').getBoundingClientRect().width > 250`), true);
      const active = route === '/login' ? '#panelLogin' : '#panelSignup';
      assert.equal(await evaluate(`getComputedStyle(document.querySelector('${active}')).display !== 'none'`), true);
      assert.equal(await evaluate(`Array.from(document.querySelectorAll('${active} input:not([type=hidden])')).every(input => input.labels.length > 0)`), true, 'All fields have labels');
      if (route === '/login') {
        await evaluate(`document.querySelector('[data-role="staff"]').click()`);
        assert.equal(await evaluate(`document.querySelector('#loginRole').value`), 'staff');
        await evaluate(`document.querySelector('[data-role="admin"]').click()`);
        assert.equal(await evaluate(`document.querySelector('#loginRole').value`), 'admin');
        await evaluate(`document.querySelector('[data-role="student"]').click(); document.querySelector('#togglePw').click()`);
        assert.equal(await evaluate(`document.querySelector('#loginPassword').type`), 'text');
        await evaluate(`document.querySelector('#togglePw').click()`);
        assert.equal(await evaluate(`document.querySelector('#loginPassword').type`), 'password');
      } else {
        await evaluate(`document.querySelector('#showPw').click()`);
        assert.equal(await evaluate(`document.querySelector('#regPw').type === 'text' && document.querySelector('#regPw2').type === 'text'`), true);
        await evaluate(`document.querySelector('#showPw').click()`);
      }
      await evaluate(`document.querySelector('[data-slideshow-toggle]').click(); document.querySelector('[data-slide-to="0"]').click()`);
      await pause(750);
      const shot = await call('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true });
      fs.writeFileSync(path.join(dir, route.slice(1) + '-' + width + '.png'), Buffer.from(shot.data, 'base64'));
      console.log('PASS ' + route + ' at ' + width + 'px');
    }
  }
  if (process.argv.includes('--visual')) { await call('Browser.close'); return; }
  await evaluate(`document.querySelector('#modeLogin').click()`);
  await until(() => evaluate(`location.pathname === '/login' && document.readyState === 'complete'`));
  assert.equal(await evaluate(`document.querySelector('#modeLogin').getAttribute('aria-current')`), 'page');
  await evaluate(`document.querySelector('#modeSignup').click()`);
  await until(() => evaluate(`location.pathname === '/register' && document.readyState === 'complete'`));
  assert.equal(await evaluate(`document.querySelector('#modeSignup').getAttribute('aria-current')`), 'page');
  await until(() => evaluate(`!document.querySelector('.auth-slideshow-controls').hidden`));
  const transitions = await evaluate(`new Promise(resolve => {
    const photo = document.querySelector('[data-slide]');
    const stage = document.querySelector('.auth-photo-stage');
    document.querySelector('[data-slide-to="0"]').click();
    const start = performance.now(), changes = [];
    const observer = new MutationObserver(() => {
      const index = [...stage.children].findIndex(image => image.classList.contains('is-active'));
      changes.push({ index, time: performance.now() - start });
      if (changes.length === 3) { observer.disconnect(); resolve(changes); }
    });
    observer.observe(photo, { attributes: true, attributeFilter: ['aria-hidden'] });
    setTimeout(() => { observer.disconnect(); resolve(changes); }, 7000);
  })`);
  assert.deepEqual(transitions.map(change => change.index), [1, 2, 0], 'All three photos loop');
  transitions.forEach((change, index) => assert.ok(Math.abs(change.time - (index + 1) * 2000) < 350, 'Photos change every 2 seconds'));
  await evaluate(`document.querySelector('[data-slideshow-toggle]').click()`);
  const pausedIndex = await evaluate(`[...document.querySelectorAll('[data-slide]')].findIndex(image => image.classList.contains('is-active'))`);
  await pause(2200);
  assert.equal(await evaluate(`[...document.querySelectorAll('[data-slide]')].findIndex(image => image.classList.contains('is-active'))`), pausedIndex, 'Pause stops rotation');
  await evaluate(`document.querySelector('[data-slide-to="2"]').click()`);
  assert.equal(await evaluate(`document.querySelector('[data-slide-to="2"]').getAttribute('aria-pressed')`), 'true', 'Manual photo selection works');
  await call('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
  await navigate('/login');
  await until(() => evaluate(`!document.querySelector('.auth-slideshow-controls').hidden`));
  assert.equal(await evaluate(`document.querySelector('[data-slideshow-toggle]').getAttribute('aria-label')`), 'Play photo slideshow');
  await pause(2200);
  assert.equal(await evaluate(`document.querySelector('[data-slide-to="0"]').getAttribute('aria-pressed')`), 'true', 'Reduced motion starts paused');
  console.log('PASS 2-second transitions through all 3 photos, looping, pause, manual controls, reduced motion.');
  assert.deepEqual(errors, []);
  console.log('PASS account navigation, labels, roles, password controls, responsive layouts, no JavaScript errors.');
  await call('Browser.close');
})().catch(error => { console.error(error); process.exitCode = 1; }).finally(() => { socket?.close(); browser.kill(); });
