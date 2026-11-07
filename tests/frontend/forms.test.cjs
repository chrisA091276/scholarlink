const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');

function setup() {
  let submit;
  const attributes = {};
  const button = {
    disabled: false, name: 'action', value: 'draft',
    setAttribute(key, value) { attributes[key] = value; },
    insertAdjacentHTML() {},
  };
  const form = { dataset: {}, addEventListener(name, handler) { if (name === 'submit') submit = handler; } };
  const document = {
    querySelectorAll(selector) { return selector === 'form' ? [form] : []; },
    querySelector() { return null; },
  };
  vm.runInNewContext(fs.readFileSync('public/js/adfc.js', 'utf8'), {
    document, window: { addEventListener() {} }, queueMicrotask,
  });
  const event = { submitter: button, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
  return { form, button, event, submit, attributes };
}

test('draft submitter remains enabled so its action reaches the server', async () => {
  const { submit, event, button, attributes } = setup();
  submit(event);
  await Promise.resolve();
  assert.equal(button.disabled, false);
  assert.equal(button.name, 'action');
  assert.equal(button.value, 'draft');
  assert.equal(attributes['aria-busy'], 'true');
});

test('a cancelled validation or AJAX submit does not lock the form', async () => {
  const { submit, event, form } = setup();
  submit(event);
  event.preventDefault();
  await Promise.resolve();
  assert.equal(form.dataset.nativeSubmitting, undefined);
});

test('repeated native submissions are prevented after the first request', async () => {
  const { submit, event } = setup();
  submit(event);
  await Promise.resolve();
  const repeat = { defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
  submit(repeat);
  assert.equal(repeat.defaultPrevented, true);
});
