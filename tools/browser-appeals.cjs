// Run after prepare-appeal-browser-tests.php with the isolated QA server on :8003.
const { spawn } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const dir = path.resolve('storage/app/appeal-browser-qa');
const fixture = JSON.parse(fs.readFileSync(path.join(dir, 'fixture.json'), 'utf8'));
const browser = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
  '--remote-debugging-port=9236', '--remote-debugging-address=127.0.0.1', '--user-data-dir=' + path.join(dir, 'chrome-' + Date.now()), 'about:blank'], { windowsHide: true, stdio: 'ignore' });
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
let socket;
let diagnose = async () => {};
async function until(task, label) {
  for (let i = 0; i < 160; i++) { try { const value = await task(); if (value) return value; } catch {} await pause(150); }
  throw Error('Timed out: ' + label);
}
(async () => {
  const tabs = await until(async () => (await fetch('http://127.0.0.1:9236/json')).json(), 'Chrome');
  socket = new WebSocket(tabs.find(tab => tab.type === 'page').webSocketDebuggerUrl);
  await new Promise((resolve, reject) => { socket.onopen = resolve; socket.onerror = reject; });
  let sequence = 0;
  const pending = new Map(), errors = [];
  socket.onmessage = ({data}) => {
    const message = JSON.parse(data);
    if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.text);
    if (pending.has(message.id)) { const {resolve,reject} = pending.get(message.id); pending.delete(message.id); message.error ? reject(Error(message.error.message)) : resolve(message.result); }
  };
  const call = (method, params={}) => new Promise((resolve,reject) => { const id=++sequence; pending.set(id,{resolve,reject}); socket.send(JSON.stringify({id,method,params})); });
  const evaluate = async expression => {
    const result = await call('Runtime.evaluate', {expression, returnByValue:true, awaitPromise:true});
    if (result.exceptionDetails) throw Error(result.exceptionDetails.exception?.description || 'Browser script failed');
    return result.result.value;
  };
  const ready = selector => until(() => evaluate(`document.readyState === 'complete' && !!document.querySelector(${JSON.stringify(selector)})`), selector);
  const navigate = async (route, selector) => { await call('Page.navigate',{url:'http://127.0.0.1:8003'+route}); await ready(selector); };
  const hasText = text => until(() => evaluate(`document.readyState === 'complete' && document.body.innerText.includes(${JSON.stringify(text)})`), text);
  const screenshot = async name => {
    await pause(250);
    assert.equal(await evaluate('document.documentElement.scrollWidth <= innerWidth'),true,'No horizontal overflow');
    const shot=await call('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});
    fs.writeFileSync(path.join(dir,name+'.png'),Buffer.from(shot.data,'base64'));
  };
  const fill = async (selector, values) => evaluate(`(() => { const form=document.querySelector(${JSON.stringify(selector)}); for(const [key,value] of Object.entries(${JSON.stringify(values)})){ const input=form.elements.namedItem(key); input.value=value; input.dispatchEvent(new Event('change',{bubbles:true})); } })()`);
  const submit = selector => evaluate(`document.querySelector(${JSON.stringify(selector)}).requestSubmit()`);
  const login = async role => {
    await call('Network.clearBrowserCookies');
    await navigate('/login/'+role+'?method=email','#loginEmail');
    await fill('#panelLogin form',{email:fixture[role+'_email'],password:fixture.password});
    await submit('#panelLogin form');
    if (role === 'student') {
      await until(() => evaluate(`location.pathname === '/login/student/verify' && document.readyState === 'complete'`), 'school ID step');
      await fill('#studentSchoolIdForm', {student_id_no:fixture.student_school_id});
      await submit('#studentSchoolIdForm');
    }
    await until(() => evaluate(`location.pathname === '/dashboard' && document.readyState === 'complete'`),'signed in');
  };
  diagnose=async()=>{ console.error(await evaluate(`JSON.stringify({path:location.pathname,alerts:[...document.querySelectorAll('.alert')].map(a=>a.innerText)})`)); await screenshot('failure'); };
  await call('Page.enable'); await call('Runtime.enable'); await call('Network.enable');
  await call('Emulation.setDeviceMetricsOverride',{width:1365,height:1000,deviceScaleFactor:1,mobile:false});
  await login('admin');
  await navigate('/awards/'+fixture.award_id,'#awardStatus');
  await fill('form[action$="/status"]',{recipient_status:'removed',status_reason:'Repeated absences from required scholarship activities.'});
  assert.equal(await evaluate(`document.querySelector('#awardReason').required`),true);
  await submit('form[action$="/status"]'); await hasText('Award updated.');
  await screenshot('administrator-removal');
  await login('student');
  await hasText('You are no longer included');
  await screenshot('student-dashboard-status');
  await navigate('/my-awards','a[href$="/appeal"]');
  await hasText('Repeated absences from required scholarship activities.');
  await screenshot('student-status-desktop');
  await call('Emulation.setDeviceMetricsOverride',{width:390,height:844,deviceScaleFactor:1,mobile:true});
  await screenshot('student-status-mobile');
  await navigate('/my-awards/'+fixture.award_id+'/appeal','#appealStatement');
  await fill('form[action$="/appeal"]',{statement:'My absences were due to illness. I have recovered and arranged to attend all upcoming activities.'});
  await screenshot('student-appeal-mobile');
  await submit('form[action$="/appeal"]'); await hasText('Your appeal is submitted');
  const appealNo=await evaluate(`document.querySelector('details summary strong').textContent`);
  await screenshot('student-pending-appeal');
  await call('Emulation.setDeviceMetricsOverride',{width:1365,height:1000,deviceScaleFactor:1,mobile:false});
  await login('admin');
  await navigate('/appeals','table');
  const reviewPath=await evaluate(`[...document.querySelectorAll('tbody tr')].find(row=>row.innerText.includes(${JSON.stringify(appealNo)})).querySelector('a').pathname`);
  await navigate(reviewPath,'#appealDecision');
  await fill('form[action$="/decide"]',{status:'approved',decision_reason:'The explanation was accepted. Please attend upcoming scholarship activities.'});
  await screenshot('administrator-appeal-review');
  await submit('form[action$="/decide"]'); await hasText('The student has been reinstated.');
  await login('student'); await hasText('You are currently included');
  await navigate('/my-awards','details'); await hasText('The explanation was accepted.');
  await screenshot('student-reinstated');
  // Membership information is persistent, unlike dismissible success notifications.
  await pause(6500);
  assert.equal(await evaluate(`document.body.innerText.includes('You are currently included in this scholarship.')`),true);
  assert.deepEqual(errors,[],'No unhandled JavaScript errors');
  console.log('PASS: admin removal, persistent student membership/reason, mobile appeal submission, admin approval, student reinstatement/history; no overflow or JavaScript errors.');
  console.log('Screenshots: storage/app/appeal-browser-qa/');
  await call('Browser.close');
})().catch(async error=>{console.error(error);await diagnose().catch(()=>{});process.exitCode=1;}).finally(()=>{socket?.close();browser.kill();});
