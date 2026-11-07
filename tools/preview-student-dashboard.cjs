// Uses the isolated fictional accounts from prepare-professional-preview.php.
const { spawn, spawnSync } = require('node:child_process');
if (!globalThis.WebSocket) {
  const result = spawnSync(process.execPath, ['--experimental-websocket', __filename], { stdio: 'inherit', windowsHide: true });
  process.exit(result.status ?? 1);
}
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const loginScript = require('./preview-login.cjs');
const directory = path.resolve('storage/app/student-dashboard-preview');
fs.mkdirSync(directory, { recursive: true });
const origin = 'http://127.0.0.1:8017';
const browser = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', [
  '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
  '--disable-extensions', '--disable-background-networking', '--remote-debugging-port=9246',
  '--remote-debugging-address=127.0.0.1', '--user-data-dir=' + path.join(directory, 'chrome-' + Date.now()), 'about:blank',
], { windowsHide: true, stdio: 'ignore' });
let socket;
const errors = [], failed = [], external = [], screenshots = [];
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
async function until(fn, label) {
  for (let i = 0; i < 100; i++) {
    try { const value = await fn(); if (value) return value; } catch {}
    await delay(150);
  }
  throw Error('Timed out: ' + label);
}
(async () => {
  const tabs = await until(async () => (await fetch('http://127.0.0.1:9246/json')).json(), 'Chrome');
  socket = new WebSocket(tabs.find(tab => tab.type === 'page').webSocketDebuggerUrl);
  await new Promise((resolve, reject) => { socket.onopen = resolve; socket.onerror = reject; });
  let sequence = 0;
  const pending = new Map();
  socket.onmessage = ({ data }) => {
    const message = JSON.parse(data);
    if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.exception?.description || message.params.exceptionDetails.text);
    if (message.method === 'Network.responseReceived' && message.params.response.status >= 400) failed.push({ url: message.params.response.url, status: message.params.response.status });
    if (message.method === 'Network.requestWillBeSent') {
      const url = message.params.request.url;
      if (/^https?:/.test(url) && !url.startsWith(origin + '/')) external.push(url);
    }
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
  async function navigate(route, selector) {
    await call('Page.navigate', { url: origin + route });
    await until(() => evaluate('document.readyState === "complete" && Boolean(document.querySelector(' + JSON.stringify(selector) + '))'), route);
  }
  async function viewport(width) {
    await call('Emulation.setDeviceMetricsOverride', { width, height: 1000, deviceScaleFactor: 1, mobile: width < 600 });
    await delay(200);
    assert(await evaluate('document.documentElement.scrollWidth <= innerWidth + 2'), 'Page overflow at ' + width);
  }
  async function capture(name, full = false) {
    const size = await call('Page.getLayoutMetrics');
    const options = full ? { clip: { x: 0, y: 0, width: size.cssContentSize.width, height: size.cssContentSize.height, scale: 1 } } : {};
    const result = await call('Page.captureScreenshot', { format: 'png', captureBeyondViewport: full, ...options });
    fs.writeFileSync(path.join(directory, name + '.png'), Buffer.from(result.data, 'base64'));
    screenshots.push(name + '.png');
  }
  await call('Page.enable'); await call('Runtime.enable'); await call('Network.enable');
  await viewport(1440);
  await navigate('/login?method=email', 'input[name="email"]');
  await evaluate(loginScript('student@professional-preview.example.test', 'PreviewOnly123!', '26-000001'));
  await navigate('/dashboard', '.scholar-home');
  await until(() => evaluate('document.getElementById("scholarCampusVideo").readyState >= 2 && !document.getElementById("scholarCampusVideo").paused'), 'muted campus video');
  assert(await evaluate('document.getElementById("scholarCampusVideo").muted'));
  await evaluate('document.getElementById("scholarVideoToggle").click()');
  assert(await evaluate('document.getElementById("scholarCampusVideo").paused'));
  assert.equal(await evaluate('document.getElementById("scholarVideoToggle").textContent.trim()'), 'Play video');
  await evaluate('document.getElementById("scholarVideoToggle").click()');
  await until(() => evaluate('!document.getElementById("scholarCampusVideo").paused'), 'resume video');
  await evaluate('document.getElementById("scholarVideoToggle").click()');
  await viewport(1440); await capture('student-desktop');
  await evaluate('document.querySelector(".scholar-opportunities").scrollIntoView()');
  await until(() => evaluate('[...document.querySelectorAll(".scholar-opportunity-image img")].every(image => image.complete && image.naturalWidth > 0)'), 'photos');
  await evaluate('scrollTo(0,0)'); await capture('student-desktop-full', true);
  await evaluate('document.querySelector(".scholar-checklists summary").click()');
  assert(await evaluate('document.querySelector(".scholar-checklists").open'));
  assert.equal(await evaluate('document.querySelectorAll("a.form-section-progress").length'), 5);
  await capture('student-checklist-desktop', true);
  await evaluate('document.querySelector(".scholar-checklists summary").click()');
  for (const width of [1024, 820, 390, 360]) {
    await viewport(width); await evaluate('scrollTo(0,0)');
    await capture('student-' + width, width === 390);
  }
  await evaluate('document.getElementById("sidebarOpen").click()');
  assert(await evaluate('document.body.classList.contains("sidebar-open")'));
  await evaluate('document.getElementById("sidebarClose").click()');
  await call('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
  await navigate('/dashboard', '.scholar-home');
  assert(await evaluate('document.getElementById("scholarCampusVideo").paused && !document.getElementById("scholarCampusVideo").getAttribute("src")'), 'Reduced motion should not load video');
  assert(await evaluate('document.querySelector(".scholar-hero-media img").naturalWidth > 0'), 'Poster fallback');
  const links = await evaluate('[...new Set([...document.querySelectorAll(".scholar-home a")].map(a => a.href.split("#")[0]))]');
  const linkResults = await evaluate(`Promise.all(${JSON.stringify(links)}.map(async url => ({url, status:(await fetch(url)).status})))`);
  assert(linkResults.every(result => result.status === 200), JSON.stringify(linkResults));
  assert.deepEqual(errors, []); assert.deepEqual(failed, []); assert.deepEqual(external, []);
  fs.writeFileSync(path.join(directory, 'verification.json'), JSON.stringify({ screenshots, links_checked: links.length, muted_video_play_pause: true, reduced_motion: true, mobile_navigation: true, failed, errors, external }, null, 2));
  console.log(JSON.stringify({ screenshots: screenshots.length, links_checked: links.length, video: 'passed', reduced_motion: 'passed', layouts: '1440, 1024, 820, 390, 360', errors: errors.length }));
})().catch(error => { console.error(error.message); console.error(JSON.stringify({ errors, failed, external })); process.exitCode = 1; }).finally(() => { socket?.close(); browser.kill(); });
