@extends('layouts.app')
@php
  $isEdit = isset($application) && $application;
@endphp
@section('title', $isEdit ? 'Continue application' : 'New Application')
@section('page-title', $isEdit ? 'Continue Scholarship Application' : 'New Scholarship Application')
@section('content')
@php
  $formData = old('form_data', $application?->form_data ?? []);
  $adfcService = app(\App\Services\AdfcApplicationForm::class);
  $adfcData = old('adfc_form', array_replace($adfcService->defaults(auth()->user()), $application?->adfc_form ?? []));
  $profile = auth()->user()->studentProfile;
@endphp
<div class="student-page">
<div class="student-page-head">
  <div>
    <span class="student-eyebrow">YOUR SCHOLARSHIP JOURNEY</span>
    <h1>{{ $isEdit ? 'Continue your application' : 'Start a scholarship application' }}</h1>
    <p>Complete each step at your own pace. Use Save draft to keep your progress and return whenever you are ready.</p>
  </div>
  <span class="status-chip blue"><i class="bi bi-shield-check me-1"></i>Secure student record</span>
</div>
<div class="student-form-card">
<div class="student-form-section border-0 pb-0">
  <div class="student-section-title"><h2>{{ $isEdit ? 'Continue where you left off' : 'Build your application' }}</h2><span class="student-note">Save draft anytime</span></div>
  <p class="student-copy mb-0">Have your academic details and supporting documents nearby. You can upload the requirements after saving this application.</p>
</div>

<nav class="application-progress" aria-label="Application steps">
<div class="application-progress-heading">
  <div role="status" aria-live="polite" aria-atomic="true"><span class="application-progress-position" id="wizardPosition">Step 1 of 7</span><strong id="wizardCurrentLabel">Scholarship</strong></div>
  <p>Select a step to review or edit your details.</p>
</div>
<div class="wizard-steps">
@foreach(['Scholarship','Applicant','School','Family & assistance','Documents','Review','Consent & submit'] as $i => $label)
<button type="button" class="wizard-step {{ $i===0?'active':'' }}" data-step="{{ $i+1 }}" aria-controls="application-step-{{ $i+1 }}" @if($i === 0) aria-current="step" @endif><span class="wizard-step-number" aria-hidden="true">{{ $i+1 }}</span><span class="wizard-step-label">{{ $label }}</span></button>
@endforeach
</div>
</nav>

<form method="POST" action="{{ $isEdit ? route('my-applications.update', $application) : route('my-applications.store') }}" id="applicationWizard" novalidate>
@csrf @if($isEdit) @method('PUT') @endif
<div class="adfc-form-masthead">
  <img src="{{ asset('images/adfc-logo.jpg') }}" alt="ADFC logo">
  <div class="adfc-form-title"><span class="adfc-eyebrow">APPLICATION / STUDENT FINANCIAL ASSISTANCE</span><strong>ASIAN DEVELOPMENT FOUNDATION COLLEGE</strong><span>Tacloban City, Leyte</span><h2>Scholarship Application Form</h2><small>RELATED PROGRAMS: CHED &middot; UniFAST &middot; TDP / TES</small></div>
  <button type="button" class="adfc-photo-placeholder" data-open-step="5"><i class="bi bi-person-bounding-box" aria-hidden="true"></i><strong>2 &times; 2 ID PHOTO</strong><small>Attach in Documents</small></button>
</div>
<p class="adfc-form-instructions"><strong>Instructions:</strong> Complete all required fields clearly. Use “NA” when a text field does not apply. Attach the required documents before submission. <span>Fields marked <span class="text-danger">*</span> are required.</span></p>
<input type="hidden" name="last_saved_step" id="lastSavedStep" value="{{ old('last_saved_step', $application?->last_saved_step ?? 1) }}">

<section class="wizard-panel student-form-section" data-panel="1">
  <div class="mb-4"><label for="processingOffice" class="form-label fw-semibold">Regional / processing office</label><input class="form-control" name="adfc_form[processing_office]" id="processingOffice" value="{{ $adfcData['processing_office'] ?? '' }}" maxlength="200" required></div>
  <h2>1. Select a scholarship</h2><p>Only published programs within their application period can receive new applications.</p>
  <div class="mb-3"><label class="form-label fw-semibold">Scholarship program <span class="text-danger">*</span></label>
  <select name="scholarship_program_id" id="programSelect" class="form-select" required>
  <option value="">— Select a program —</option>
  @foreach($programs as $p)
    <option value="{{ $p->id }}"
      data-financial="{{ json_encode($p->financialFields()) }}"
      data-benefits="{{ $p->benefits ?? 'Benefits are defined by the program.' }}"
      data-criteria="{{ $p->eligibility_criteria ?? 'Review the configured eligibility criteria.' }}"
      data-template="{{ json_encode($p->application_template ?? []) }}"
      data-requirements="{{ json_encode($p->requirements->map(fn ($r) => ['name' => $r->name, 'description' => $r->description, 'required' => $r->is_required, 'types' => $r->allowed_types, 'max' => $r->max_size_kb])->values()) }}"
      @selected((string) old('scholarship_program_id', $application?->scholarship_program_id ?? $selected?->id) === (string) $p->id)
    >{{ $p->name }} · deadline {{ $p->deadline?->format('M d, Y') ?? 'not set' }}</option>
  @endforeach
  </select></div>
  <div id="programBrief" class="alert alert-primary small {{ $selected || $application ? '' : 'd-none' }}">
    <div><b>Benefits:</b> <span id="programBenefits">{{ ($application?->program?->benefits ?? $selected?->benefits) ?: 'Select a program to see benefits.' }}</span></div>
    <div class="mt-1"><b>Eligibility:</b> <span id="programCriteria">{{ ($application?->program?->eligibility_criteria ?? $selected?->eligibility_criteria) ?: 'Select a program to see criteria.' }}</span></div>
  </div>
</section>

<section class="wizard-panel student-form-section d-none" data-panel="2">
<h2>2. Applicant information</h2><p>Complete the fields as they appear on your official records. Use NA where a text field does not apply. These answers are saved with this application.</p>
@include('applications.partials.adfc-fields', ['section' => 'applicant'])
</section>

<section class="wizard-panel student-form-section d-none" data-panel="3">
  <h2>3. School information</h2><p>Provide your current enrollment details, then complete any scholarship-specific academic information.</p>
  @include('applications.partials.adfc-fields', ['section' => 'school'])
  <div class="row g-3">
    <div class="col-md-4"><label class="form-label small fw-semibold">Academic year <span class="text-danger">*</span></label><input name="academic_year" class="form-control" value="{{ old('academic_year', $application?->academic_year ?? now()->year.'-'.(now()->year+1)) }}" required></div>
    <div class="col-md-4"><label class="form-label small fw-semibold">Enrollment status <span class="text-danger">*</span></label><select name="enrollment_status" class="form-select" required>@foreach(['enrolled','continuing','new','freshman','transferee','returning'] as $value)<option value="{{ $value }}" {{ old('enrollment_status', $application?->enrollment_status ?? auth()->user()->studentProfile?->enrollment_status ?? 'enrolled') === $value ? 'selected' : '' }}>{{ ucfirst($value) }}</option>@endforeach</select></div>
    <div class="col-12"><label class="form-label small fw-semibold">Personal statement</label><textarea name="statement" class="form-control" rows="4" maxlength="5000" placeholder="Tell the review team why this scholarship will support your studies.">{{ old('statement', $application?->statement) }}</textarea></div>
  </div>
</section>

<section class="wizard-panel student-form-section d-none" data-panel="4">
  <h2>4. Family background and assistance</h2><p>Enter parent details and declare any other educational financial assistance. Use NA when information does not apply.</p>
  @include('applications.partials.adfc-fields', ['section' => 'family'])
  @include('applications.partials.adfc-fields', ['section' => 'assistance'])
  <h3 class="h6 mt-4">Additional program requirements</h3>
  <p id="financialEmpty" class="text-muted">This program does not request standard financial details.</p><div class="row g-3" id="financialFields">
    <div class="col-md-6"><label class="form-label small fw-semibold">Parent / guardian name</label><input name="guardian_name" class="form-control" value="{{ old('guardian_name', $application?->guardian_name) }}"></div>
    <div class="col-md-6"><label class="form-label small fw-semibold">Parent / guardian occupation</label><input name="guardian_occupation" class="form-control" value="{{ old('guardian_occupation', $application?->guardian_occupation) }}"></div>
    <div class="col-md-4"><label class="form-label small fw-semibold">Household size</label><input type="number" min="1" max="30" name="household_size" class="form-control" value="{{ old('household_size', $application?->household_size) }}"></div>
    <div class="col-md-8"><label class="form-label small fw-semibold">Approx. monthly household income (PHP)</label><div class="input-group"><span class="input-group-text">₱</span><input type="number" min="0" step="0.01" name="monthly_household_income" class="form-control" value="{{ old('monthly_household_income', $application?->monthly_household_income) }}"></div></div>
  </div>
  <div class="border-top mt-4 pt-3">
    <h6 class="fw-semibold mb-1">Scholarship-specific information</h6>
    <p class="text-muted small">These fields come from the selected scholarship's official application template.</p>
    <div id="templateFields" class="row g-3"></div>
    <div id="templateEmpty" class="text-muted small">Select a scholarship to load its application format.</div>
  </div>
</section>

<section class="wizard-panel student-form-section d-none" data-panel="5">
  <h2>5. Supporting requirements</h2><p>The checklist is generated for the selected program. Save this draft first, then upload files from the application page.</p>
  @include('applications.partials.adfc-document-fields')
  <h3 class="h6 mt-3">Scholarship-specific documents</h3>
  <div id="requirementsList" class="border rounded-3 p-3 bg-light">
    <div class="text-muted small">Select a program to view its configured requirements.</div>
  </div>
  @if($application)
  @foreach($application->program->requirements as $requirement)
  @php($uploaded = $application->documents->firstWhere('scholarship_requirement_id', $requirement->id))
  <div class="border rounded p-3 mt-3">
    <label for="upload-{{ $requirement->id }}" class="fw-semibold">{{ $requirement->name }} ({{ $requirement->is_required ? 'Required' : 'Optional' }})</label>
    <p class="small text-muted">{{ $requirement->description }} Accepted: {{ $requirement->allowed_types }}. Maximum {{ number_format($requirement->max_size_kb / 1024, 1) }} MB.</p>
    @if($uploaded?->original_filename)<p class="small text-break">File: {{ $uploaded->original_filename }}</p>@endif
    @if($uploaded)<p class="small">{{ $uploaded->document_name }} · {{ str_replace('_', ' ', $uploaded->verification_status) }}<br>{{ $uploaded->verification_notes }}</p>@endif
    @if(!$uploaded || $application->status === 'draft' || in_array($uploaded->verification_status, ['rejected','requires_correction']))
    <input class="form-control" type="file" id="upload-{{ $requirement->id }}" name="file" form="upload-form-{{ $requirement->id }}" required accept="{{ collect(explode(',', $requirement->allowed_types))->map(fn($type) => '.'.trim($type))->join(',') }}">
    <label for="category-{{ $requirement->id }}" class="form-label small mt-2">ADFC document category, if applicable</label><select id="category-{{ $requirement->id }}" class="form-select form-select-sm" name="document_category" form="upload-form-{{ $requirement->id }}"><option value="">Additional program requirement</option>@foreach(config('adfc_form.documents') as $category => $label)<option value="{{ $category }}" @selected($uploaded?->document_category === $category)>{{ $label }}</option>@endforeach</select>
    <button type="submit" form="upload-form-{{ $requirement->id }}" class="btn btn-outline-primary mt-2">{{ $uploaded ? 'Replace document' : 'Upload document' }}</button>
    <progress class="w-100 d-none mt-2" id="progress-{{ $requirement->id }}" max="100" value="0" aria-label="Upload progress"></progress>
    <p id="upload-status-{{ $requirement->id }}" role="status" class="small mt-2"></p>
    @endif
  </div>
  @endforeach
  @else<p class="alert alert-info mt-3">Save your draft to unlock document uploads here. Your saved step will reopen automatically.</p>@endif
  <div class="alert alert-warning small mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Accepted types and file-size limits are enforced on the server. Documents are stored privately.</div>
</section>

<section class="wizard-panel student-form-section d-none" data-panel="6">
  <h2>6. Review your application</h2><p>Check your details before saving. You can return to any step while this application is a draft.</p>
  <div id="adfcReview" aria-live="polite"></div>
  @if(config('scholarlink.verify_email') && !auth()->user()->hasVerifiedEmail())<div class="alert alert-warning">Verify your email address before submission. <a href="{{ route('verification.notice') }}">Email verification</a></div>@endif
  <div class="review-box small">
    <div><span>Program</span><b id="reviewProgram">—</b></div><div><span>Academic year</span><b id="reviewYear">—</b></div><div><span>Enrollment</span><b id="reviewEnrollment">—</b></div>
  </div>
  <div id="reviewTemplate" class="mt-3"></div>
  <div id="reviewFinancial" class="mt-3"></div>
  <h3 class="h6 mt-3">Personal statement</h3><p id="reviewStatement" class="small text-break" style="white-space:pre-wrap"></p>
  @if($application)
  @php($missingDocuments = app(\App\Services\ApplicationSubmissionService::class)->missingRequirements($application))
  @if($missingDocuments)<div class="alert alert-warning"><strong>Missing requirements</strong><ul class="mb-0">@foreach($missingDocuments as $missing)<li>{{ $missing }}</li>@endforeach</ul></div>@endif
  <div class="mt-3"><h3 class="h6">Uploaded documents</h3>@forelse($application->documents as $document)<p>{{ $document->document_name }} — {{ str_replace('_', ' ', $document->verification_status) }}</p>@empty<p>No documents uploaded yet.</p>@endforelse</div>
  @endif
  <p class="small mt-3">Read the privacy notice and consent on the next step before confirming your application.</p>
</section>
<section class="wizard-panel student-form-section d-none" data-panel="7">
@include('applications.partials.adfc-fields', ['section' => 'consent'])
@include('applications.partials.adfc-notices')
<h2 class="mt-4">7. Submit your application</h2><p>Submission locks your answers for review. You can still save an incomplete draft without accepting the declaration.</p>
<p class="small text-muted">The printed name records your online certification. A signature line is also included on the downloadable form for signing after printing.</p><div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="declaration" value="1" id="declaration"><label class="form-check-label" for="declaration">I certify that my information is true and complete and accept the <a href="{{ route('privacy') }}" target="_blank" rel="noopener">privacy notice and terms</a>. I understand that submitting does not guarantee approval.</label></div>
</section>

<div class="d-flex flex-wrap justify-content-between gap-2 border-top pt-3 mt-4">
  <button type="button" class="btn btn-outline-secondary" id="previousStep" disabled><i class="bi bi-arrow-left me-1"></i>Previous</button>
  <div class="d-flex gap-2"><button type="submit" class="btn btn-outline-secondary" name="action" value="draft" formnovalidate><i class="bi bi-save me-1"></i>Save draft</button><button type="submit" name="action" value="draft" formnovalidate class="btn btn-outline-primary" id="saveContinue">Save &amp; continue</button><button type="button" class="btn btn-navy" id="nextStep">Next <i class="bi bi-arrow-right ms-1"></i></button><button type="submit" class="btn btn-gold d-none" id="submitApplication" name="action" value="submit">Save and submit <i class="bi bi-send ms-1"></i></button></div>
</div>
</form>
@if($application)
@foreach(config('adfc_form.documents') as $category => $label)
  @continue($category === 'other')
  <form id="upload-form-standard-{{ $category }}" class="wizard-upload-form" data-requirement="standard-{{ $category }}" action="{{ route('documents.store', $application) }}" method="POST" enctype="multipart/form-data">@csrf<input type="hidden" name="document_name" value="{{ $label }}"><input type="hidden" name="document_category" value="{{ $category }}"></form>
@endforeach
@foreach($application->program->requirements as $requirement)
<form id="upload-form-{{ $requirement->id }}" class="wizard-upload-form" data-requirement="{{ $requirement->id }}" action="{{ route('documents.store', $application) }}" method="POST" enctype="multipart/form-data">@csrf<input type="hidden" name="document_name" value="{{ $requirement->name }}"><input type="hidden" name="scholarship_requirement_id" value="{{ $requirement->id }}"></form>
@endforeach
@endif
</div>
</div>
@push('scripts')
<script>
(function(){
  const panels=[...document.querySelectorAll('.wizard-panel')], steps=[...document.querySelectorAll('.wizard-step')]; let current=1;
  panels.forEach(panel=>{panel.id='application-step-'+panel.dataset.panel;});
  const select=document.getElementById('programSelect'), brief=document.getElementById('programBrief'), templateFields=document.getElementById('templateFields'), templateEmpty=document.getElementById('templateEmpty'), requirementsList=document.getElementById('requirementsList'), existingFormData=@json($formData);
  const escapeHtml=value=>String(value??'').replace(/[&<>'"]/g, character=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','\"':'&quot;'}[character]));
  function selectedOption(){return select.options[select.selectedIndex];}
  function readJson(value){try{return JSON.parse(value||'[]')}catch{return[]}}
  function renderTemplate(){
    const fields=readJson(selectedOption()?.dataset.template); templateFields.innerHTML=''; templateEmpty.classList.toggle('d-none',fields.length>0);
    fields.forEach(field=>{
      const key=escapeHtml(field.key), value=escapeHtml(existingFormData[field.key]??''), required=field.required?' data-template-required="1"':'';
      const label=escapeHtml(field.label||field.key), help=field.help?`<div class="form-text">${escapeHtml(field.help)}</div>`:'';
      let control;
      if(field.type==='textarea') control=`<textarea name="form_data[${key}]" class="form-control" rows="4"${required}>${value}</textarea>`;
      else if(field.type==='select') control=`<select name="form_data[${key}]" class="form-select"${required}><option value="">— Select —</option>${(field.options||[]).map(option=>`<option value="${escapeHtml(option)}" ${String(existingFormData[field.key]??'')===String(option)?'selected':''}>${escapeHtml(option)}</option>`).join('')}</select>`;
      else if(field.type==='multi_select') control=`<select multiple name="form_data[${key}][]" class="form-select"${required}>${(field.options||[]).map(option=>`<option value="${escapeHtml(option)}" ${(Array.isArray(existingFormData[field.key])?existingFormData[field.key]:[]).map(String).includes(String(option))?'selected':''}>${escapeHtml(option)}</option>`).join('')}</select>`;
      else if(field.type==='checkbox') control=`<div class="form-check mt-2"><input type="checkbox" name="form_data[${key}]" value="1" class="form-check-input" ${existingFormData[field.key]?'checked':''}${required}><label class="form-check-label">Yes</label></div>`;
      else control=`<input type="${['text','number','date','email','url'].includes(field.type)?field.type:'text'}" name="form_data[${key}]" class="form-control" value="${value}"${required}>`;
      templateFields.insertAdjacentHTML('beforeend',`<div class="col-md-6"><label class="form-label small fw-semibold">${label} ${field.required?'<span class="text-danger">*</span>':''}</label>${control}${help}</div>`);
    });
  }
  function renderRequirements(){
    const requirements=readJson(selectedOption()?.dataset.requirements); requirementsList.innerHTML='';
    if(!requirements.length){requirementsList.innerHTML='<div class="text-muted small">Select a program to view its configured requirements.</div>';return;}
    requirements.forEach(requirement=>requirementsList.insertAdjacentHTML('beforeend',`<div class="d-flex justify-content-between gap-2 py-2 border-bottom"><span><i class="bi bi-file-earmark-text me-2 text-primary"></i>${escapeHtml(requirement.name)} ${requirement.required?'<span class="text-danger">*</span>':''}<div class="small text-muted">${escapeHtml(requirement.description||'Upload a readable copy.')} · ${escapeHtml(requirement.types)} · ${escapeHtml(requirement.max)} KB</div></span><span class="badge ${requirement.required?'bg-danger-subtle text-danger':'bg-secondary-subtle text-secondary'}">${requirement.required?'Required':'Optional'}</span></div>`));
  }
  function setStep(step){
    current=Number.isFinite(step)?Math.min(Math.max(Math.trunc(step),1),panels.length):1;
    panels.forEach(panel=>panel.classList.toggle('d-none',Number(panel.dataset.panel)!==current));
    steps.forEach(button=>{
      const number=Number(button.dataset.step);
      button.classList.toggle('active',number===current);
      button.classList.toggle('is-previous',number<current);
      if(number===current)button.setAttribute('aria-current','step');else button.removeAttribute('aria-current');
    });
    document.getElementById('wizardPosition').textContent=`Step ${current} of ${panels.length}`;
    document.getElementById('wizardCurrentLabel').textContent=steps[current-1].querySelector('.wizard-step-label').textContent;
    document.getElementById('previousStep').disabled=current===1;
    document.getElementById('nextStep').classList.toggle('d-none',current===panels.length);
    document.getElementById('submitApplication').classList.toggle('d-none',current!==panels.length);
    document.getElementById('lastSavedStep').value=current;
    if(current===6){review();reviewAll();}
  }
  function review(){document.getElementById('reviewProgram').textContent=selectedOption()?.text||'—';document.getElementById('reviewYear').textContent=document.querySelector('[name=academic_year]').value||'—';document.getElementById('reviewEnrollment').textContent=document.querySelector('[name=enrollment_status]').value||'—';const values=[...templateFields.querySelectorAll('[name^="form_data"]')].filter(field=>field.value).map(field=>`<div class="d-flex justify-content-between gap-2 py-2 border-bottom"><span>${escapeHtml(field.closest('.col-md-6')?.querySelector('label')?.textContent||field.name)}</span><b class="text-end">${escapeHtml(field.value)}</b></div>`).join('');document.getElementById('reviewTemplate').innerHTML=values?`<h6 class="fw-semibold mt-3">Scholarship-specific information</h6><div class="review-box small">${values}</div>`:'';}
  document.getElementById('nextStep').addEventListener('click',()=>{if(current===1&&!select.value){select.reportValidity();return;}setStep(current+1);});
  document.getElementById('previousStep').addEventListener('click',()=>setStep(current-1)); steps.forEach(stepButton=>stepButton.addEventListener('click',()=>{setStep(Number(stepButton.dataset.step));}));
  document.querySelectorAll('[data-open-step]').forEach(button=>button.addEventListener('click',()=>setStep(Number(button.dataset.openStep))));
  select.addEventListener('change',()=>{const option=selectedOption();if(!option.value){brief.classList.add('d-none');}else{brief.classList.remove('d-none');document.getElementById('programBenefits').textContent=option.dataset.benefits||'—';document.getElementById('programCriteria').textContent=option.dataset.criteria||'—';}renderTemplate();renderRequirements();renderFinancial();});

  function renderFinancial(){
    const keys=readJson(selectedOption()?.dataset.financial);
    document.querySelectorAll('#financialFields input').forEach(input=>{const shown=keys.includes(input.name);input.disabled=!shown;input.required=shown;input.closest('[class^="col-"]').classList.toggle('d-none',!shown);});
    document.getElementById('financialEmpty').classList.toggle('d-none',keys.length>0);
  }
  const wizard=document.getElementById('applicationWizard');
  const serverErrors=@json($errors->messages());
  function fieldKey(field){return field.name.replace(/\[([^\]]*)\]/g,'.$1').replace(/\.$/,'');}
  function attachErrors(){
    wizard.querySelectorAll('input,select,textarea').forEach((field,index)=>{
      if(!field.id)field.id='application-field-'+index;
      const label=field.closest('.col-md-6,.col-md-4,.col-md-8,.col-12,.mb-3')?.querySelector('label');if(label&&!label.htmlFor)label.htmlFor=field.id;
      const messages=serverErrors[fieldKey(field)];
      if(messages){field.classList.add('is-invalid');field.setAttribute('aria-invalid','true');const error=document.createElement('div');error.className='invalid-feedback d-block';error.id=field.id+'-error';error.textContent=messages.join(' ');field.setAttribute('aria-describedby',error.id);field.after(error);}
    });
  }
  function reviewAll(){
    document.getElementById('reviewStatement').textContent=wizard.elements.statement.value||'Not provided';
    const financial=[...document.querySelectorAll('#financialFields input')].filter(input=>!input.disabled);
    document.getElementById('reviewFinancial').innerHTML=financial.length?'<h3 class="h6">Family and financial information</h3>'+financial.map(input=>'<div class="d-flex justify-content-between gap-3 border-bottom py-2 small"><span>'+escapeHtml(input.closest('[class^="col-"]').querySelector('label').textContent)+'</span><strong>'+escapeHtml(input.value||'Missing required information')+'</strong></div>').join(''):'';
    document.getElementById('reviewTemplate').innerHTML=[...templateFields.querySelectorAll('[name]')].map(input=>{
      let value=input.type==='checkbox'?(input.checked?'Yes':'No'):input.multiple?[...input.selectedOptions].map(o=>o.textContent).join(', '):input.value;
      return '<div class="d-flex justify-content-between gap-3 border-bottom py-2 small"><span>'+escapeHtml(input.closest('.col-md-6').querySelector('label').textContent)+'</span><strong>'+escapeHtml(value||'Not provided')+'</strong></div>';
    }).join('');
  }
  document.getElementById('saveContinue').addEventListener('click',()=>document.getElementById('lastSavedStep').value=Math.min(current+1,7));
  wizard.addEventListener('submit',event=>{
    const submitting=event.submitter?.value==='submit';
    if(wizard.dataset.submitting){event.preventDefault();return;}
    if(!submitting)return;
    if(!document.getElementById('declaration').checked){event.preventDefault();setStep(7);document.getElementById('declaration').required=true;document.getElementById('declaration').reportValidity();return;}
    document.querySelectorAll('[data-template-required="1"]').forEach(field=>field.required=true);
    if(!wizard.checkValidity()){event.preventDefault();const invalid=wizard.querySelector(':invalid');if(invalid?.closest('.wizard-panel'))setStep(Number(invalid.closest('.wizard-panel').dataset.panel));wizard.reportValidity();return;}
    wizard.dataset.submitting='1';
    document.getElementById('submitApplication').textContent='Submitting…';
    document.getElementById('submitApplication').setAttribute('aria-disabled','true');
  });
  document.querySelectorAll('.wizard-upload-form').forEach(form=>form.addEventListener('submit',async event=>{
    event.preventDefault();const id=form.dataset.requirement,status=document.getElementById('upload-status-'+id),progress=document.getElementById('progress-'+id);
    if(form.dataset.uploading)return;
    form.dataset.uploading='1';status.textContent='Saving draft before upload…';
    try {
      const draft=new FormData(wizard);draft.set('action','draft');draft.set('last_saved_step','5');
      const saved=await fetch(wizard.getAttribute('action'),{method:'POST',body:draft,headers:{'Accept':'application/json'}});
      if(!saved.ok){const data=await saved.json();status.textContent=Object.values(data.errors||{}).flat().join(' ')||data.message||'Could not save your draft. Review the form and try again.';delete form.dataset.uploading;return;}
    }catch{status.textContent='Could not save your draft. Check your connection.';delete form.dataset.uploading;return;}
    const request=new XMLHttpRequest();request.open('POST',form.action);request.setRequestHeader('Accept','application/json');
    progress.classList.remove('d-none');status.textContent='Uploading…';
    request.upload.onprogress=e=>{if(e.lengthComputable)progress.value=Math.round(e.loaded/e.total*100);};
    request.onload=()=>{delete form.dataset.uploading;if(request.status>=200&&request.status<300){status.textContent='Upload saved. Reloading checklist…';window.location.hash='step-5';window.location.reload();}else{let data;try{data=JSON.parse(request.responseText)}catch{data={message:'Upload failed. Please retry.'}}status.textContent=Object.values(data.errors||{}).flat().join(' ')||data.message;}};
    request.onerror=()=>{delete form.dataset.uploading;status.textContent='Connection failed. Your saved draft is unchanged.';};
    request.send(new FormData(form));
  }));
  renderTemplate();renderRequirements();renderFinancial();attachErrors();
  function linkedStep(){const match=window.location.hash.match(/^#step-([1-7])$/);return match ? Number(match[1]) : null;}
  setStep(linkedStep() ?? (Number(document.getElementById('lastSavedStep').value)||1));
  window.addEventListener('hashchange',()=>{const step=linkedStep();if(step)setStep(step);});
  const firstError=wizard.querySelector('.is-invalid');if(firstError?.closest('.wizard-panel'))setStep(Number(firstError.closest('.wizard-panel').dataset.panel));

})();
</script>
<script src="{{ asset('js/adfc-form.js') }}?v={{ filemtime(public_path('js/adfc-form.js')) }}" defer></script>
@endpush
@endsection
