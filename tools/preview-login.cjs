// Browser expression shared by local preview scripts. Completes both student steps.
module.exports = function loginScript(email, password, schoolId) {
  return `(async () => {
    const form = document.querySelector('#panelLogin form');
    const data = new FormData(form);
    data.set('email', ${JSON.stringify(email)});
    data.set('password', ${JSON.stringify(password)});
    let response = await fetch(form.action, { method: 'POST', body: data });
    if (new URL(response.url).pathname === '/login/student/verify') {
      const page = new DOMParser().parseFromString(await response.text(), 'text/html');
      const verification = page.querySelector('#studentSchoolIdForm');
      if (!verification) throw Error('School ID verification is unavailable');
      const fields = new FormData(verification);
      fields.set('student_id_no', ${JSON.stringify(schoolId)});
      response = await fetch(verification.getAttribute('action'), { method: 'POST', body: fields });
    }
    if (!response.ok || new URL(response.url).pathname !== '/dashboard') throw Error('Login failed');
  })()`;
};
