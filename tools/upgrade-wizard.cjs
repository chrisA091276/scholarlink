const fs=require('fs');const p='resources/views/applications/create.blade.php';let s=fs.readFileSync(p,'utf8');
s=s.replace("['Scholarship','Personal & academic','Family & financial','Requirements','Review']", "['Scholarship','Personal','Academic','Financial','Documents','Review','Submit']");
for(let n=5;n>=2;n--)s=s.replace(`data-panel="${n}"`, `data-panel="${n+1}"`);
s=s.replace('<input type="hidden" name="action" id="wizardAction" value="draft">', '<input type="hidden" name="last_saved_step" id="lastSavedStep" value="{{ old(\'last_saved_step\', $application?->last_saved_step ?? 1) }}">');
s=s.replace('data-benefits="', 'data-financial="{{ e(json_encode($p->financialFields())) }}" data-benefits="');
s=s.replace("'required' => $r->is_required", "'required' => $r->is_required, 'types' => $r->allowed_types, 'max' => $r->max_size_kb");
s=s.replace('<section class="wizard-panel student-form-section d-none" data-panel="3">', `<section class="wizard-panel student-form-section d-none" data-panel="2">
<h2>2. Personal information</h2><p>Your application uses the current student profile. Correct it in your profile before submission.</p>
@php($profile = auth()->user()->studentProfile)
<dl class="row"><dt class="col-sm-4">Legal name</dt><dd class="col-sm-8">{{ $profile?->full_name ?: auth()->user()->name }}</dd><dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ auth()->user()->email }}</dd><dt class="col-sm-4">Contact</dt><dd class="col-sm-8">{{ $profile?->contact_number ?: 'Not provided' }}</dd><dt class="col-sm-4">Student number</dt><dd class="col-sm-8">{{ $profile?->student_id_no ?: 'Not yet assigned' }}</dd><dt class="col-sm-4">Course / year</dt><dd class="col-sm-8">{{ $profile?->course }} / {{ $profile?->year_level }}</dd></dl>
<a href="{{ route('profile.edit') }}" target="_blank" rel="noopener" class="btn btn-outline-primary">Complete or correct profile (new tab)</a>
</section>
<section class="wizard-panel student-form-section d-none" data-panel="3">`);
s=s.replace('2. Personal and academic information', '3. Academic information').replace('3. Family and financial information', '4. Family and financial information').replace('4. Supporting requirements', '5. Supporting requirements').replace('5. Review and confirmation', '6. Review your application');
s=s.replace('<div class="row g-3">\n    <div class="col-md-6"><label class="form-label small fw-semibold">Parent', '<p id="financialEmpty" class="text-muted">This program does not request standard financial details.</p><div class="row g-3" id="financialFields">\n    <div class="col-md-6"><label class="form-label small fw-semibold">Parent');
s=s.replace('name="declaration"', 'name="declaration"');
const declarationStart=s.indexOf('  <div class="form-check mt-3"><input class="form-check-input" type="checkbox" id="declaration">');
if(declarationStart<0) throw Error('declaration');
const declarationEnd=s.indexOf('\n</section>',declarationStart);
s=s.slice(0,declarationStart)+`  @if($application)
  <div class="mt-3"><h3 class="h6">Uploaded documents</h3>@forelse($application->documents as $document)<p>{{ $document->document_name }} — {{ str_replace('_', ' ', $document->verification_status) }}</p>@empty<p>No documents uploaded yet.</p>@endforelse</div>
  @endif
</section>
<section class="wizard-panel student-form-section d-none" data-panel="7">
<h2>7. Submit your application</h2><p>Submission locks your answers for review. You can still save an incomplete draft without accepting the declaration.</p>
<div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="declaration" value="1" id="declaration"><label class="form-check-label" for="declaration">I certify that my information is true and complete and accept the <a href="{{ route('privacy') }}" target="_blank" rel="noopener">privacy notice and terms</a>. I understand that submitting does not guarantee approval.</label></div>`+s.slice(declarationEnd);
s=s.replace('<div id="requirementsList"', `<div id="requirementsList"`);
const insertAt=s.indexOf('  <div class="alert alert-warning small mt-3');
s=s.slice(0,insertAt)+`  @if($application)
  @foreach($application->program->requirements as $requirement)
  @php($uploaded = $application->documents->firstWhere('scholarship_requirement_id', $requirement->id))
  <div class="border rounded p-3 mt-3">
    <label for="upload-{{ $requirement->id }}" class="fw-semibold">{{ $requirement->name }} ({{ $requirement->is_required ? 'Required' : 'Optional' }})</label>
    <p class="small text-muted">{{ $requirement->description }} Accepted: {{ $requirement->allowed_types }}. Maximum {{ number_format($requirement->max_size_kb / 1024, 1) }} MB.</p>
    @if($uploaded)<p class="small">{{ $uploaded->document_name }} · {{ str_replace('_', ' ', $uploaded->verification_status) }}<br>{{ $uploaded->verification_notes }}</p>@endif
    @if(!$uploaded || $application->status === 'draft' || in_array($uploaded->verification_status, ['rejected','requires_correction']))
    <input class="form-control" type="file" id="upload-{{ $requirement->id }}" name="file" form="upload-form-{{ $requirement->id }}" required accept="{{ collect(explode(',', $requirement->allowed_types))->map(fn($type) => '.'.trim($type))->join(',') }}">
    <button type="submit" form="upload-form-{{ $requirement->id }}" class="btn btn-outline-primary mt-2">{{ $uploaded ? 'Replace document' : 'Upload document' }}</button>
    <progress class="w-100 d-none mt-2" id="progress-{{ $requirement->id }}" max="100" value="0" aria-label="Upload progress"></progress>
    <p id="upload-status-{{ $requirement->id }}" role="status" class="small mt-2"></p>
    @endif
  </div>
  @endforeach
  @else<p class="alert alert-info mt-3">Save your draft to unlock document uploads here. Your saved step will reopen automatically.</p>@endif
`+s.slice(insertAt);
s=s.replace('<button type="submit" class="btn btn-outline-secondary" name="action" value="draft">', '<button type="submit" class="btn btn-outline-secondary" name="action" value="draft" formnovalidate>');
s=s.replace('</form>\n</div>', `</form>
@if($application)
@foreach($application->program->requirements as $requirement)
<form id="upload-form-{{ $requirement->id }}" class="wizard-upload-form" data-requirement="{{ $requirement->id }}" action="{{ route('documents.store', $application) }}" method="POST" enctype="multipart/form-data">@csrf<input type="hidden" name="document_name" value="{{ $requirement->name }}"><input type="hidden" name="scholarship_requirement_id" value="{{ $requirement->id }}"></form>
@endforeach
@endif
</div>`);
s=s.replace('if(current===panels.length)review();', "document.getElementById('lastSavedStep').value=current;if(current===6)review();");
s=s.replace("${escapeHtml(requirement.description||'Upload an official, readable copy.')}", "${escapeHtml(requirement.description||'Upload a readable copy.')} · ${escapeHtml(requirement.types)} · ${escapeHtml(requirement.max)} KB");
s=s.replace("if(current===1&&!select.value)", "if(current===1&&!select.value)");
s=s.replace("if(Number(stepButton.dataset.step)<=current)setStep(Number(stepButton.dataset.step));", "setStep(Number(stepButton.dataset.step));");
s=s.replace("renderTemplate();renderRequirements();});", "renderTemplate();renderRequirements();renderFinancial();});");
let start=s.indexOf("  document.getElementById('applicationWizard').addEventListener('submit'");let end=s.indexOf('\n})();',start);
s=s.slice(0,start)+`
  function renderFinancial(){
    const keys=readJson(selectedOption()?.dataset.financial);
    document.querySelectorAll('#financialFields input').forEach(input=>{const shown=keys.includes(input.name);input.disabled=!shown;input.closest('[class^="col-"]').classList.toggle('d-none',!shown);});
    document.getElementById('financialEmpty').classList.toggle('d-none',keys.length>0);
  }
  const wizard=document.getElementById('applicationWizard');
  wizard.addEventListener('submit',event=>{
    const submitting=event.submitter?.value==='submit';
    if(!submitting)return;
    if(!document.getElementById('declaration').checked){event.preventDefault();setStep(7);document.getElementById('declaration').required=true;document.getElementById('declaration').reportValidity();return;}
    document.querySelectorAll('[data-template-required="1"]').forEach(field=>field.required=true);
    if(!wizard.checkValidity()){event.preventDefault();const invalid=wizard.querySelector(':invalid');if(invalid?.closest('.wizard-panel'))setStep(Number(invalid.closest('.wizard-panel').dataset.panel));wizard.reportValidity();}
  });
  document.querySelectorAll('.wizard-upload-form').forEach(form=>form.addEventListener('submit',event=>{
    event.preventDefault();const id=form.dataset.requirement,status=document.getElementById('upload-status-'+id),progress=document.getElementById('progress-'+id);
    const request=new XMLHttpRequest();request.open('POST',form.action);request.setRequestHeader('Accept','application/json');
    progress.classList.remove('d-none');status.textContent='Uploading…';
    request.upload.onprogress=e=>{if(e.lengthComputable)progress.value=Math.round(e.loaded/e.total*100);};
    request.onload=()=>{if(request.status>=200&&request.status<300){status.textContent='Upload saved. Reloading checklist…';window.location.hash='step-5';window.location.reload();}else{let data;try{data=JSON.parse(request.responseText)}catch{data={message:'Upload failed. Please retry.'}}status.textContent=Object.values(data.errors||{}).flat().join(' ')||data.message;}};
    request.onerror=()=>status.textContent='Connection failed. Your saved draft is unchanged.';
    request.send(new FormData(form));
  }));
  renderTemplate();renderRequirements();renderFinancial();
  setStep(window.location.hash==='#step-5'?5:Number(document.getElementById('lastSavedStep').value)||1);
`+s.slice(end);
fs.writeFileSync(p,s);
const dc='app/Http/Controllers/DocumentController.php';s=fs.readFileSync(dc,'utf8').replace("        return back()->with('success', 'Document uploaded.');", "        if ($request->expectsJson()) return response()->json(['message' => 'Document uploaded.', 'id' => $doc->id], 201);\n        return back()->with('success', 'Document uploaded.');");fs.writeFileSync(dc,s);
