@php
  $service = app(\App\Services\AdfcApplicationForm::class);
  $answers = $service->pdfAnswers($application);
  $notices = json_decode(file_get_contents(resource_path('data/adfc-notices.json')), true);
  $image = fn ($name) => 'data:image/jpeg;base64,'.base64_encode(file_get_contents(resource_path('images/adfc-form/'.$name.'.jpg')));
  $photoData = $service->photoData($application);
  $attached = $application->documents->whereIn('verification_status', ['pending', 'verified']);
  $otherDocuments = $attached->whereNotIn('document_category', ['id_photo', 'enrollment', 'indigency']);
  $legacy = $application->form_version !== \App\Services\AdfcApplicationForm::VERSION;
  $extraAnswers = $application->form_data ?? [];
  $extraLabels = collect($application->program?->application_template ?? [])->pluck('label', 'key');
  $profile = $legacy ? $application->student?->studentProfile : null;
  $displayExtra = fn ($value) => is_array($value) ? implode(', ', $value) : (is_bool($value) ? ($value ? 'Yes' : 'No') : (filled($value) ? (string) $value : 'Not provided'));
  $academicDetails = array_filter([
      'Academic year' => $application->academic_year, 'Enrollment status' => $application->enrollment_status,
      'Parent / guardian name' => $application->guardian_name ?? $profile?->parent_guardian_name,
      'Parent / guardian occupation' => $application->guardian_occupation ?? $profile?->parent_guardian_occupation, 'Household size' => $application->household_size ?? $profile?->household_size,
      'Monthly household income (PHP)' => $application->monthly_household_income ?? $profile?->monthly_household_income,
  ], fn ($value) => filled($value));
  $hasSupplement = $extraAnswers || filled($application->statement) || $application->form_review
      || filled($application->guardian_name) || filled($application->guardian_occupation)
      || $application->household_size !== null || $application->monthly_household_income !== null
      || ($legacy && ($profile?->address || $profile?->parent_guardian_name || $profile?->parent_guardian_occupation || $profile?->household_size !== null || $profile?->monthly_household_income !== null));
@endphp
<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><title>ADFC Scholarship Application {{ $application->application_no }}</title>
<style>
@page{size:A4 portrait;margin:30.25pt 39.6pt 32.4pt}
body{font-family:DejaVu Sans,sans-serif;font-size:8pt;line-height:1.15;color:#2d343c;margin:0}
table{width:100%;border-collapse:collapse;table-layout:fixed}p,h1,h2{margin:0}
.eyebrow{text-align:right;font-size:7pt;color:#5f666e;margin-bottom:4pt}
.masthead{margin-bottom:6pt}.masthead td{vertical-align:middle}.brand{width:77.4%;padding-right:10pt}.brand-logo{width:53.3pt;height:51.3pt;margin-bottom:5pt}.college-name{font-size:10.5pt;font-weight:bold;color:#23344e}.location{font-size:8pt;color:#6a7480;margin-top:1pt}.form-title{font-size:11pt;color:#23344e;margin-top:5pt}
.photo-cell{width:22.6%;border:1.25pt solid #8a99a8;background:#f3f6f9;text-align:center;height:105pt}.photo-cell img{max-width:104pt;max-height:102pt}.photo-caption{font-size:8pt;font-weight:bold;line-height:1.5}.photo-caption small{display:block;font-size:6pt;font-weight:normal}
.processing-office{font-size:8pt;margin:5pt 0}.processing-office strong{color:#922d2d}.instructions{font-size:7.5pt;line-height:1.3;margin-bottom:6pt;color:#60676e}
.programs{border:.4pt solid #dce4ed;text-align:center;font-size:8pt;color:#4b5b6b;padding:4pt;margin-bottom:7pt}.programs strong{font-size:7.5pt}.programs img{height:20.8pt;vertical-align:middle;margin:0 5pt 0 10pt}
.form-section{margin-bottom:8pt}.section-title{background:#24384e;color:#fff;font-size:9pt;font-weight:bold;padding:5pt 6pt;margin-bottom:3pt;page-break-after:avoid}.section-title span{font-size:7.5pt;font-weight:normal;color:#dee6ed}.section-title .long-subtitle{font-size:6.6pt}
.field-row{margin-bottom:3pt;page-break-inside:avoid}.field-row td{border:.5pt solid #d7dee6;background:#f8fafc;padding:3.5pt 4.5pt;vertical-align:top;word-wrap:break-word}.field-label{display:block;font-size:6.6pt;color:#556477;font-weight:bold;text-transform:uppercase;line-height:1.2}.field-answer{font-size:8pt;line-height:1.2;margin-top:3pt;min-height:10pt;word-wrap:break-word}.choice{display:inline-block;white-space:nowrap;margin-right:8pt}.check-box{display:inline-block;font-family:DejaVu Sans,sans-serif;width:8pt;height:8pt;line-height:8pt;text-align:center;border:.6pt solid #657587;font-size:8pt;vertical-align:baseline}.unrecorded{display:block;font-size:6.5pt;color:#778390;margin-top:3pt}
.page-two{page-break-before:always}.legal-section{margin-bottom:7pt}.legal{font-size:7.8pt;line-height:1.25;margin:4pt 0 6pt;text-align:justify}.legal strong{font-weight:bold}.consent-item{margin:4pt 0 5pt;padding-left:10pt;text-indent:-10pt}.certification{font-size:8pt;margin:7pt 0}
.signature-row td{height:40pt;vertical-align:top}.signature-space{height:19pt;border-bottom:.5pt solid #8a99a8;margin-bottom:3pt}.signature-name{font-size:8pt}.signature-help{font-size:6pt;color:#6a7480}.date-answer{padding-top:18pt;font-size:9pt}.submission-note{text-align:center;font-size:7pt;color:#60676e;margin:6pt 0 9pt}
.office-section{page-break-inside:avoid}.office-list{margin-top:5pt;font-size:7pt;line-height:1.5}.office-list .check-box{margin-right:4pt}.reviewer{margin-top:17pt;border-bottom:.5pt solid #8a99a8;padding-bottom:4pt;min-height:10pt;font-size:8pt}.officer-label{font-size:7pt;color:#60676e;margin-top:3pt}
.footer{position:fixed;bottom:-19pt;left:0;right:0;text-align:center;font-size:6pt;color:#6a7480}.footer .reference{margin-left:8pt}
.supplement{page-break-before:always}.supplement h1{font-size:12pt;color:#23344e;margin:8pt 0}.supplement-intro{font-size:8pt;line-height:1.4;margin-bottom:10pt}.record-table td,.record-table th{border:.5pt solid #d7dee6;padding:6pt;vertical-align:top;word-wrap:break-word}.record-table th{background:#f8fafc;text-align:left;font-size:7pt;width:35%}.record-table td{font-size:8pt}.record-table tr{page-break-inside:avoid}.record-table{margin-bottom:12pt}.supplement-text{font-size:8pt;line-height:1.5;white-space:pre-wrap;word-wrap:break-word}.supplement-heading{margin-top:12pt}.record-note{font-size:7pt;color:#60676e;margin-bottom:8pt}
</style></head><body>
<p class="eyebrow">APPLICATION / STUDENT FINANCIAL ASSISTANCE</p>
<table class="masthead"><tr>
  <td class="brand"><img class="brand-logo" src="{{ $image('adfc') }}" alt="ADFC"><div class="college-name">ASIAN DEVELOPMENT FOUNDATION COLLEGE</div><div class="location">Tacloban City, Leyte</div><h1 class="form-title">SCHOLARSHIP APPLICATION FORM</h1></td>
  <td class="photo-cell">@if($photoData)<img src="{{ $photoData }}" alt="Applicant ID photo">@else<div class="photo-caption">2 × 2<br>ID PHOTO</div>@endif</td>
</tr></table>
<p class="processing-office"><strong>Regional / Processing Office:</strong> {{ $answers['processing_office'] ?? '____________________________' }}</p>
<p class="instructions"><strong>Instructions:</strong> Complete all required fields clearly. Use “NA” when a field does not apply. Attach the required documents before submission.</p>
<div class="programs"><strong>RELATED PROGRAMS</strong><img src="{{ $image('ched') }}" alt="CHED"> CHED <img src="{{ $image('unifast') }}" alt="UniFAST"> UniFAST &nbsp; • &nbsp; TDP / TES</div>

<section class="form-section">
  <h2 class="section-title">APPLICANT INFORMATION <span>· Personal details</span></h2>
  <table class="field-row"><tr>
    @include('applications.partials.pdf-field',['key'=>'last_name','width'=>25.1])
    @include('applications.partials.pdf-field',['key'=>'first_name','width'=>25.1])
    @include('applications.partials.pdf-field',['key'=>'middle_name','width'=>25.1])
    @include('applications.partials.pdf-field',['key'=>'maiden_name','width'=>24.7])
  </tr></table>
  <table class="field-row"><tr>
    @include('applications.partials.pdf-field',['key'=>'date_of_birth','width'=>21.6,'label'=>'Date of birth (dd/mm/yyyy)'])
    @include('applications.partials.pdf-field',['key'=>'place_of_birth','width'=>29.3])
    @include('applications.partials.pdf-field',['key'=>'sex','width'=>24.4])
    @include('applications.partials.pdf-field',['key'=>'citizenship','width'=>24.7])
  </tr></table>
  <table class="field-row"><tr>
    @include('applications.partials.pdf-field',['key'=>'street_barangay','width'=>36.3])
    @include('applications.partials.pdf-field',['key'=>'municipality','width'=>30])
    @include('applications.partials.pdf-field',['key'=>'province','width'=>21.6])
    @include('applications.partials.pdf-field',['key'=>'zip_code','width'=>12.1])
  </tr></table>
  <table class="field-row"><tr>
    @include('applications.partials.pdf-field',['key'=>'mobile_number','width'=>34.2])
    @include('applications.partials.pdf-field',['key'=>'email','width'=>65.8,'label'=>'E-mail address'])
  </tr></table>
</section>
<section class="form-section">
  <h2 class="section-title">SCHOOL INFORMATION <span>· Current enrollment details</span></h2>
  <table class="field-row"><tr>
    @include('applications.partials.pdf-field',['key'=>'school_name','width'=>63.5])
    @include('applications.partials.pdf-field',['key'=>'campus','width'=>36.5])
  </tr></table>
  <table class="field-row"><tr>
    @include('applications.partials.pdf-field',['key'=>'course','width'=>48.1])
    @include('applications.partials.pdf-field',['key'=>'year_level','width'=>18.1])
    @include('applications.partials.pdf-field',['key'=>'school_id_number','width'=>33.8])
  </tr></table>
  <table class="field-row"><tr>
    @include('applications.partials.pdf-field',['key'=>'school_address','width'=>63.5])
    @include('applications.partials.pdf-field',['key'=>'disability','width'=>36.5])
  </tr></table>
  <table class="field-row"><tr>@include('applications.partials.pdf-field',['key'=>'school_sector','width'=>100])</tr></table>
</section>
<section class="form-section">
  <h2 class="section-title">FAMILY BACKGROUND <span>· Parent / guardian details</span></h2>
  <table class="field-row"><tr>@include('applications.partials.pdf-field',['key'=>'father_status','width'=>50]) @include('applications.partials.pdf-field',['key'=>'mother_status','width'=>50])</tr></table>
  <table class="field-row"><tr>@include('applications.partials.pdf-field',['key'=>'father_name','width'=>50]) @include('applications.partials.pdf-field',['key'=>'mother_name','width'=>50])</tr></table>
  <table class="field-row"><tr>@include('applications.partials.pdf-field',['key'=>'father_address','width'=>50]) @include('applications.partials.pdf-field',['key'=>'mother_address','width'=>50])</tr></table>
</section>

<div class="page-two"></div>
<section class="form-section">
  <h2 class="section-title">OTHER EDUCATIONAL FINANCIAL ASSISTANCE <span class="long-subtitle">· Funding assistance declaration</span></h2>
  <table class="field-row"><tr>
    @include('applications.partials.pdf-field',['key'=>'other_assistance','width'=>50.9])
    @include('applications.partials.pdf-field',['key'=>'other_assistance_details','width'=>49.1,'label'=>'If yes, please specify'])
  </tr></table>
</section>
@foreach(['qualification'=>'QUALIFICATION REQUIREMENTS','documents'=>'DOCUMENTARY REQUIREMENTS','privacy'=>'PRIVACY NOTICE'] as $key => $heading)
  <section class="legal-section"><h2 class="section-title">{{ $heading }}</h2><p class="legal">{{ $notices[$key] }}</p></section>
@endforeach
<section class="legal-section"><h2 class="section-title">CONSENT</h2>
  @foreach(array_slice($notices['consent'], 0, 2) as $text)<p class="legal consent-item">{{ $text }}</p>@endforeach
</section>
<p class="certification">{{ $notices['consent'][2] }}</p>
<table class="field-row signature-row"><tr>
  <td style="width:62.8%"><span class="field-label">SIGNATURE OVER PRINTED NAME OF APPLICANT</span><div class="signature-space"></div><div class="signature-name">{{ $answers['printed_name'] ?? '' }}</div><div class="signature-help">Sign after printing</div></td>
  <td style="width:37.2%"><span class="field-label">DATE ACCOMPLISHED</span><div class="date-answer">{{ $service->display('date_accomplished', $answers['date_accomplished'] ?? null) }}</div></td>
</tr></table>
<p class="submission-note">Note: Submit the fully accomplished form together with the required supporting documents.</p>
<section class="office-section">
  <h2 class="section-title">FOR OFFICE USE ONLY <span>· Do not fill out this portion</span></h2>
  <table class="field-row"><tr>
    <td style="width:58.6%"><span class="field-label">DOCUMENTS ATTACHED</span><div class="office-list">
      <div><span class="check-box">{{ $attached->contains('document_category','enrollment') ? '✓' : '' }}</span> Certificate of Registration/Enrollment (COR/COE)</div>
      <div><span class="check-box">{{ $attached->contains('document_category','indigency') ? '✓' : '' }}</span> Certificate of Indigency</div>
      <div><span class="check-box">{{ $otherDocuments->isNotEmpty() ? '✓' : '' }}</span> Other: {{ $otherDocuments->isNotEmpty() ? $otherDocuments->map(fn ($document) => config('adfc_form.documents.'.$document->document_category) ?: $document->document_name)->join('; ') : '________________________' }}</div>
    </div></td>
    <td style="width:41.4%"><span class="field-label">EVALUATED / PROCESSED BY</span><div class="reviewer">{{ $application->form_review['reviewer_name'] ?? '' }}</div><div class="officer-label">ADFC Coordinator / Officer</div></td>
  </tr></table>
</section>

@if($hasSupplement)
<section class="supplement">
  <p class="eyebrow">APPLICATION RECORD / SUPPORTING DETAILS</p>
  <h1>Scholarship application record</h1>
  <p class="supplement-intro">{{ $application->program?->name }}<br>Application: {{ $application->application_no }} &nbsp; · &nbsp; Status: {{ str_replace('_',' ',$application->status) }}</p>
  @if($legacy)<p class="record-note">This application was saved using the earlier form. Available profile details are shown in the corresponding fields; unrecorded answers remain marked “Not provided”.</p>@endif
  <h2 class="section-title">ADDITIONAL APPLICATION DETAILS</h2>
  <table class="record-table">
    @foreach($academicDetails as $label => $value)<tr><th>{{ $label }}</th><td>{{ $value }}</td></tr>@endforeach
    @if($legacy && $application->student?->studentProfile?->address)<tr><th>Address recorded in student profile</th><td>{{ $application->student->studentProfile->address }}</td></tr>@endif
    <tr><th>Online declaration accepted</th><td>{{ $application->declaration_accepted_at?->format('d/m/Y h:i A') ?? 'Not yet submitted' }}</td></tr>
    @if(!$legacy)<tr><th>Certificate of Indigency unavailable</th><td>{{ !empty($answers['indigency_unavailable']) ? 'Yes; applicant declared that the certificate cannot be secured.' : 'No' }}</td></tr>@endif
    @foreach($extraAnswers as $key => $value)<tr><th>{{ $extraLabels[$key] ?? ucfirst(str_replace('_',' ',$key)) }}</th><td>{{ $displayExtra($value) }}</td></tr>@endforeach
  </table>
  @if($application->statement)<h2 class="section-title supplement-heading">APPLICANT STATEMENT</h2><p class="supplement-text">{{ $application->statement }}</p>@endif
  @if($application->form_review)<h2 class="section-title supplement-heading">OFFICE ASSESSMENT</h2><table class="record-table">
    <tr><th>Evaluated / processed by</th><td>{{ $application->form_review['reviewer_name'] ?? 'Not recorded' }}</td></tr>
    <tr><th>Review recorded</th><td>{{ $application->form_review['reviewed_at'] ?? 'Not recorded' }}</td></tr>
    <tr><th>Qualification assessment</th><td>{{ str_replace('_',' ',$application->form_review['qualification'] ?? 'Not recorded') }}</td></tr>
  </table><p class="supplement-text">{{ $application->form_review['notes'] ?? '' }}</p>@endif
  @if($application->documents->isNotEmpty())<h2 class="section-title supplement-heading">DOCUMENT RECORD</h2><table class="record-table">
    @foreach($application->documents as $document)<tr><th>{{ config('adfc_form.documents.'.$document->document_category) ?: $document->document_name }}</th><td>{{ str_replace('_',' ',$document->verification_status) }}@if($document->verification_notes)<br>{{ $document->verification_notes }}@endif</td></tr>@endforeach
  </table>@endif
</section>
@endif
</body></html>
