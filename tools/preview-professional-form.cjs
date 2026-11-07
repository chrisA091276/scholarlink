const {spawn,spawnSync} = require('node:child_process');
if (!globalThis.WebSocket) {
  const child = spawnSync(process.execPath,['--experimental-websocket',__filename],{stdio:'inherit',windowsHide:true});
  process.exit(child.status ?? 1);
}
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const loginScript = require('./preview-login.cjs');
const dir = path.resolve('storage/app/professional-preview');
const fixture = JSON.parse(fs.readFileSync(path.join(dir,'fixture.json'),'utf8'));
const origin = 'http://127.0.0.1:8017';
const browser = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', [
  '--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check','--disable-extensions','--disable-background-networking',
  '--remote-debugging-port=9243','--remote-debugging-address=127.0.0.1','--user-data-dir='+path.join(dir,'chrome-'+Date.now()),'about:blank',
],{windowsHide:true,stdio:'ignore'});
let socket;
const errors=[], external=[], failed=[], screenshots=[];
const delay = ms => new Promise(resolve=>setTimeout(resolve,ms));
async function until(fn, label) {
  for(let attempt=0;attempt<100;attempt++){try{const result=await fn();if(result)return result;}catch{}await delay(150);}
  throw Error('Timed out: '+label);
}
(async()=>{
  const tabs=await until(async()=>await (await fetch('http://127.0.0.1:9243/json')).json(),'browser');
  socket=new WebSocket(tabs.find(tab=>tab.type==='page').webSocketDebuggerUrl);
  await new Promise((resolve,reject)=>{socket.onopen=resolve;socket.onerror=reject;});
  let sequence=0;const pending=new Map();
  socket.onmessage=({data})=>{
    const message=JSON.parse(data);
    if(message.method==='Runtime.exceptionThrown')errors.push(message.params.exceptionDetails.exception?.description||message.params.exceptionDetails.text);
    if(message.method==='Network.requestWillBeSent'){const url=message.params.request.url;if(/^https?:/.test(url)&&!url.startsWith(origin+'/'))external.push(url);}
    if(message.method==='Network.responseReceived'&&message.params.response.status>=400)failed.push({url:message.params.response.url,status:message.params.response.status});
    if(pending.has(message.id)){const task=pending.get(message.id);pending.delete(message.id);message.error?task.reject(Error(message.error.message)):task.resolve(message.result);}
  };
  function call(method,params={}){return new Promise((resolve,reject)=>{const id=++sequence;pending.set(id,{resolve,reject});socket.send(JSON.stringify({id,method,params}));});}
  async function evaluate(expression){const result=await call('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(result.exceptionDetails)throw Error(result.exceptionDetails.exception?.description||'Page evaluation failed');return result.result.value;}
  async function navigate(route,selector){await call('Page.navigate',{url:origin+route});await until(()=>evaluate('document.readyState === "complete" && Boolean(document.querySelector('+JSON.stringify(selector)+'))'),route);}
  async function capture(name){await delay(250);const result=await call('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});fs.writeFileSync(path.join(dir,name+'.png'),Buffer.from(result.data,'base64'));screenshots.push(name+'.png');}
  async function viewport(width){await call('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:width<600});}
  async function noOverflow(label){assert(await evaluate('document.documentElement.scrollWidth <= innerWidth+2'),label+' overflows viewport');}
  async function login(role){await call('Network.clearBrowserCookies');await navigate('/login/'+role,'input[name="email"]');await evaluate(loginScript(role+'@professional-preview.example.test','PreviewOnly123!','26-000001'));await navigate('/dashboard','.form-workspace');}
  await call('Page.enable');await call('Runtime.enable');await call('Network.enable');
  for(const role of ['student','staff','admin']){
    await viewport(1440);await login(role);await noOverflow(role+' desktop');await capture(role+'-dashboard-desktop');
    await viewport(390);await noOverflow(role+' mobile');await capture(role+'-dashboard-mobile');
  }
  await viewport(1440);await login('student');
  assert.equal(await evaluate('document.querySelectorAll("a.form-section-progress").length'),5);
  await navigate('/my-applications/'+fixture.draft_id+'/edit#step-3','#applicationWizard');
  assert.equal(await evaluate('document.querySelector(".wizard-step[aria-current=step]").dataset.step'),'3');
  await evaluate('document.querySelector("[data-open-step=\\"5\\"]").click()');
  assert.equal(await evaluate('document.querySelector(".wizard-step[aria-current=step]").dataset.step'),'5');
  assert.equal(await evaluate('document.getElementById("adfc-zip_code").maxLength'),4);
  assert.equal(await evaluate('document.querySelectorAll("[data-adfc-label]").length'),32);
  await evaluate('document.querySelectorAll(".wizard-step")[1].click()');await capture('application-applicant-desktop');
  await viewport(390);await noOverflow('application mobile');await capture('application-applicant-mobile');
  await evaluate('document.querySelectorAll(".wizard-step")[3].click()');
  await evaluate('document.getElementById("adfc-other_assistance").value="yes";document.getElementById("adfc-other_assistance").dispatchEvent(new Event("change",{bubbles:true}))');
  assert(await evaluate('document.getElementById("adfc-other_assistance_details").required'));
  await evaluate('document.querySelectorAll(".wizard-step")[5].click()');
  assert(await evaluate('document.getElementById("adfcReview").textContent.includes("Fictional local study allowance")'));
  assert(await evaluate('document.getElementById("adfcReview").textContent.includes("15/02/2004")'));
  await viewport(1440);await evaluate('document.querySelectorAll(".wizard-step")[6].click()');await capture('application-consent-desktop');
  await evaluate('document.querySelectorAll(".wizard-step")[4].click()');await capture('application-documents-desktop');
  await login('staff');await navigate('/applications/'+fixture.review_id,'#office-review');await noOverflow('office review desktop');await capture('office-review-desktop');
  await viewport(390);await noOverflow('office review mobile');await capture('office-review-mobile');
  const pdf = await evaluate(`(async()=>{const response=await fetch('/applications/'+${fixture.review_id}+'/download');if(!response.ok)throw Error('PDF download failed');const buffer=await response.arrayBuffer();return {bytes:buffer.byteLength,signature:new TextDecoder().decode(buffer.slice(0,4))};})()`);
  assert.equal(pdf.signature,'%PDF');assert(pdf.bytes>1000);
  assert.deepEqual(errors,[]);assert.deepEqual(external,[]);assert.deepEqual(failed,[]);
  fs.writeFileSync(path.join(dir,'verification.json'),JSON.stringify({screenshots,pdf,js_errors:errors,external_requests:external,failed_requests:failed},null,2));
  console.log(JSON.stringify({screenshots:screenshots.length,pdf_bytes:pdf.bytes,js_errors:errors.length,external_requests:external.length,failed_requests:failed.length}));
})().catch(error=>{console.error(error.message);console.error(JSON.stringify({errors,external,failed}));process.exitCode=1;}).finally(()=>{socket?.close();browser.kill();});
