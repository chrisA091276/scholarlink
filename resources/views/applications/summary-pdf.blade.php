@php
  $adfcLogoPath = public_path('images/adfc-logo.jpg');
  $adfcLogoData = is_file($adfcLogoPath) ? base64_encode(file_get_contents($adfcLogoPath)) : null;
  $profile = $application->student?->studentProfile;
  $template = $application->program?->application_template ?? [];
  $formData = $application->form_data ?? [];
  $display = fn ($value) => is_array($value) ? implode(', ', array_map('strval', $value)) : (is_bool($value) ? ($value ? 'Yes' : 'No') : (($value !== null && $value !== '') ? (string) $value : 'N/A'));
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ADFC Scholarship Application {{ $application->application_no }}</title>
<style>
@page{size:A4 portrait;margin:28px 32px 35px}
*{box-sizing:border-box}
body{position:relative;font-family:DejaVu Sans,Arial,sans-serif;color:#16243a;font-size:9px;line-height:1.4}
h1,h2,h3,p{margin:0}
.watermark{position:fixed;z-index:-1;top:38%;left:22%;width:56%;text-align:center;opacity:.12}
.watermark img{width:100%;height:auto}
.masthead{width:100%;border-collapse:collapse;border-bottom:3px double #132d58;margin-bottom:9px}
.masthead td{vertical-align:middle;padding:0 5px 9px}
.masthead .brand-cell,.masthead .photo-cell{width:78px;text-align:center}
.brand-logo{width:62px;height:62px;object-fit:contain}
.college-name{font-size:11px;font-weight:bold;letter-spacing:.45px;color:#142f60;text-transform:uppercase}
.college-sub{margin-top:3px;color:#6b7280;font-size:7px;letter-spacing:.8px;text-transform:uppercase}
.form-heading{text-align:center;color:#132d58}
.form-number{font-size:7px;font-weight:bold;letter-spacing:1px;color:#977318}
.form-heading h1{margin:4px 0 3px;font-family:DejaVu Serif,Georgia,serif;font-size:14px;letter-spacing:.5px}
.form-heading p{font-size:8px;color:#68758a}
.photo-box{width:70px;height:82px;border:1px solid #697386;text-align:center;vertical-align:middle;color:#536177;font-size:8px;line-height:1.6}
.photo-box span{font-size:6px;letter-spacing:.6px}
.instruction{margin:0 0 10px;padding:7px 9px;border:1px solid #c9ced7;background:#f5f6f8;color:#4b586b;font-family:DejaVu Serif,Georgia,serif;font-size:8px}
.reference-row{width:100%;border-collapse:collapse;margin-bottom:8px}
.reference-row td{padding:4px 6px;border:1px solid #b8c0cc}
.reference-row .ref-label{width:19%;background:#edf0f4;font-weight:bold;color:#46546a}
.reference-row .ref-value{width:31%;font-weight:bold}
.section{margin:0 0 9px;border:1px solid #929eaf;background:rgba(255,255,255,.82);page-break-inside:avoid}
.section-title{padding:5px 8px;background:#132d58;border-bottom:2px solid #c6a33a;color:#fff;font-family:DejaVu Serif,Georgia,serif;font-size:9px;font-weight:bold;letter-spacing:.65px;text-transform:uppercase}
.fields{width:100%;border-collapse:collapse}
.fields td{width:50%;padding:5px 7px 6px;border-right:1px solid #d0d5dd;border-bottom:1px solid #d0d5dd;vertical-align:top}
.fields tr:last-child td{border-bottom:0}
.fields td:last-child{border-right:0}
.label{display:block;margin-bottom:3px;color:#657187;font-size:6.6px;font-weight:bold;letter-spacing:.45px;text-transform:uppercase}
.value{display:block;min-height:12px;color:#172944;font-size:8.5px;overflow-wrap:anywhere}
.answer-table{width:100%;border-collapse:collapse}
.answer-table th,.answer-table td{padding:5px 7px;border-bottom:1px solid #d0d5dd;text-align:left;vertical-align:top}
.answer-table th{background:#edf0f4;color:#4b586b;font-size:7px;text-transform:uppercase;letter-spacing:.35px}
.answer-table tr:last-child td{border-bottom:0}
.answer-table td:first-child{width:39%;font-weight:bold;color:#46546a}
.checklist{width:100%;border-collapse:collapse}
.checklist td{width:50%;padding:5px 7px;border-bottom:1px solid #d0d5dd;vertical-align:top}
.checkbox{display:inline-block;width:9px;height:9px;margin-right:5px;border:1px solid #66748a;vertical-align:middle}
.note{padding:6px 8px;color:#59677b;font-size:7px}
.signature-table{width:100%;border-collapse:collapse;margin-top:16px;page-break-inside:avoid}
.signature-table td{width:50%;padding:15px 12px 2px;text-align:center;vertical-align:bottom}
.signature-line{border-top:1px solid #66748a;padding-top:4px;color:#536177;font-size:7px}
.office-use{margin-top:12px;border:1px dashed #66748a;page-break-inside:avoid}
.office-title{text-align:center;padding:5px;color:#4b586b;font-weight:bold;font-size:7px;letter-spacing:.6px}
.office-grid{width:100%;border-collapse:collapse}
.office-grid td{width:50%;height:27px;padding:5px 7px;border:1px solid #c2c9d3;color:#59677b;font-size:7px}
.footer{margin-top:10px;padding-top:5px;border-top:1px solid #d5dae1;text-align:center;color:#7a8596;font-size:6.5px}
</style>
</head>
<body>
@if($adfcLogoData)<div class="watermark"><img src="data:image/jpeg;base64,{{ $adfcLogoData }}" alt=""></div>@endif

<table class="masthead"><tr>
  <td class="brand-cell">@if($adfcLogoData)<img class="brand-logo" src="data:image/jpeg;base64,{{ $adfcLogoData }}" alt="ADFC logo">@endif</td>
  <td class="form-heading"><div class="college-name">Asian Development Foundation College</div><div class="college-sub">Tacloban City · Scholarship and Student Assistance</div><div class="form-number" style="margin-top:8px">FORM NO. ADFC-SCH-01</div><h1>SCHOLARSHIP APPLICATION FORM</h1><p>{{ $application->program?->name ?? 'Scholarship Program' }}</p></td>
  <td class="photo-cell"><table style="margin:0 auto"><tr><td class="photo-box">2 × 2<br><span>ID PICTURE</span></td></tr></table></td>
</tr></table>

<div class="instruction"><strong>Instructions:</strong> Review the scholarship details and supporting document checklist. This form summarizes the information saved in your ADFC ScholarLink application. Write “N/A” for items that do not apply. The form is subject to document validation and authorized review.</div>

<table class="reference-row"><tr><td class="ref-label">APPLICATION NO.</td><td class="ref-value">{{ $application->application_no }}</td><td class="ref-label">APPLICATION STATUS</td><td class="ref-value">{{ ucfirst(str_replace('_',' ',$application->status)) }}</td></tr><tr><td class="ref-label">ACADEMIC YEAR</td><td class="ref-value">{{ $application->academic_year ?: 'N/A' }}</td><td class="ref-label">DATE SUBMITTED</td><td class="ref-value">{{ $application->submitted_at?->format('M d, Y') ?? 'Not submitted' }}</td></tr></table>

<section class="section"><div class="section-title">I. PERSONAL INFORMATION</div><table class="fields">
  <tr><td><span class="label">Full name</span><span class="value">{{ $profile?->full_name ?: $application->student?->name ?: 'N/A' }}</span></td><td><span class="label">Student ID number</span><span class="value">{{ $profile?->student_id_no ?: 'N/A' }}</span></td></tr>
  <tr><td><span class="label">Date of birth</span><span class="value">{{ $profile?->date_of_birth?->format('F d, Y') ?: 'N/A' }}</span></td><td><span class="label">Email address</span><span class="value">{{ $application->student?->email ?: 'N/A' }}</span></td></tr>
  <tr><td><span class="label">Contact number</span><span class="value">{{ $profile?->contact_number ?: 'N/A' }}</span></td><td><span class="label">Permanent address</span><span class="value">{{ $profile?->address ?: 'N/A' }}</span></td></tr>
  <tr><td><span class="label">Emergency contact</span><span class="value">{{ $profile?->emergency_contact_name ?: 'N/A' }}{{ $profile?->emergency_contact_number ? ' · '.$profile->emergency_contact_number : '' }}</span></td><td><span class="label">Citizenship / residency</span><span class="value">{{ $display($formData['citizenship'] ?? $formData['residency'] ?? null) }}</span></td></tr>
</table></section>

<section class="section"><div class="section-title">II. ACADEMIC INFORMATION</div><table class="fields">
  <tr><td><span class="label">College / institution</span><span class="value">Asian Development Foundation College</span></td><td><span class="label">Course / degree program</span><span class="value">{{ $profile?->course ?: 'N/A' }}</span></td></tr>
  <tr><td><span class="label">Department</span><span class="value">{{ $profile?->department ?: 'N/A' }}</span></td><td><span class="label">Year level</span><span class="value">{{ $profile?->year_level ?: 'N/A' }}</span></td></tr>
  <tr><td><span class="label">Enrollment status</span><span class="value">{{ $application->enrollment_status ?: ($profile?->enrollment_status ?: 'N/A') }}</span></td><td><span class="label">Academic year</span><span class="value">{{ $application->academic_year }}</span></td></tr>
  <tr><td><span class="label">Academic semester</span><span class="value">{{ $profile?->semester ?: 'N/A' }}</span></td><td><span class="label">Student statement</span><span class="value">{{ $application->statement ?: 'N/A' }}</span></td></tr>
</table></section>

<section class="section"><div class="section-title">III. FAMILY AND FINANCIAL BACKGROUND</div><table class="fields">
  <tr><td><span class="label">Parent / guardian name</span><span class="value">{{ $application->guardian_name ?: ($profile?->parent_guardian_name ?: 'N/A') }}</span></td><td><span class="label">Parent / guardian occupation</span><span class="value">{{ $application->guardian_occupation ?: ($profile?->parent_guardian_occupation ?: 'N/A') }}</span></td></tr>
  <tr><td><span class="label">Household size</span><span class="value">{{ $application->household_size ?: ($profile?->household_size ?: 'N/A') }}</span></td><td><span class="label">Monthly household income</span><span class="value">{{ $application->monthly_household_income !== null ? 'PHP '.number_format($application->monthly_household_income, 2) : ($profile?->monthly_household_income !== null ? 'PHP '.number_format($profile->monthly_household_income, 2) : 'N/A') }}</span></td></tr>
</table></section>

<section class="section"><div class="section-title">IV. SCHOLARSHIP INFORMATION</div><table class="fields">
  <tr><td><span class="label">Scholarship / assistance program</span><span class="value">{{ $application->program?->name ?: 'N/A' }}</span></td><td><span class="label">Category</span><span class="value">{{ $application->program?->category ?: 'N/A' }}</span></td></tr>
  <tr><td><span class="label">Benefits / assistance</span><span class="value">{{ $application->program?->benefits ?: 'See program announcement' }}</span></td><td><span class="label">Application deadline</span><span class="value">{{ $application->program?->deadline?->format('M d, Y') ?: 'N/A' }}</span></td></tr>
</table>
@if($application->program?->eligibility_criteria)<div class="note"><strong>Published eligibility criteria:</strong> {{ $application->program->eligibility_criteria }}</div>@endif
</section>

@if($formData)
<section class="section"><div class="section-title">V. SCHOLARSHIP-SPECIFIC INFORMATION</div><table class="answer-table"><thead><tr><th>Application question</th><th>Applicant response</th></tr></thead><tbody>
  @foreach($template as $field)
    @if(array_key_exists($field['key'] ?? '', $formData))
      <tr><td>{{ $field['label'] ?? str_replace('_',' ',ucfirst($field['key'])) }}</td><td>{{ $display($formData[$field['key']]) }}</td></tr>
    @endif
  @endforeach
  @foreach($formData as $key => $value)
    @if(!collect($template)->contains(fn($field) => ($field['key'] ?? null) === $key))
      <tr><td>{{ str_replace('_',' ',ucfirst($key)) }}</td><td>{{ $display($value) }}</td></tr>
    @endif
  @endforeach
</tbody></table></section>
@endif

<section class="section"><div class="section-title">VI. DOCUMENTARY REQUIREMENTS CHECKLIST</div>
  @if($application->program?->requirements?->count())
    <table class="checklist"><tr>
    @foreach($application->program->requirements as $index => $requirement)
      @if($index > 0 && $index % 2 === 0)</tr><tr>@endif
      @php($uploaded = $application->documents->contains(fn($document) => (int) $document->scholarship_requirement_id === (int) $requirement->id))
      <td><span class="checkbox"></span>{{ $requirement->name }}{{ $requirement->is_required ? ' (Required)' : ' (Optional)' }}<br><span class="label">{{ $uploaded ? 'Uploaded' : 'Not uploaded' }}</span></td>
    @endforeach
    @if($application->program->requirements->count() % 2 === 1)<td></td>@endif
    </tr></table>
  @else
    <div class="note">No scholarship-specific requirements have been configured.</div>
  @endif
  @if($application->documents->count())<div class="note"><strong>Files received:</strong> {{ $application->documents->pluck('document_name')->join(', ') }}</div>@endif
</section>

<table class="signature-table"><tr><td><div class="signature-line">Signature over printed name of applicant</div></td><td><div class="signature-line">Date accomplished</div></td></tr><tr><td><div style="height:18px"></div><div class="signature-line">Parent / guardian signature, if applicable</div></td><td><div style="height:18px"></div><div class="signature-line">Contact number</div></td></tr></table>

<section class="office-use"><div class="office-title">FOR ADFC SCHOLARSHIP OFFICE USE ONLY</div><table class="office-grid"><tr><td>Documents verified by: ______________________________</td><td>Date received: __________________</td></tr><tr><td>Eligibility screening: □ Eligible &nbsp; □ Manual review &nbsp; □ Not eligible</td><td>Reviewer: _______________________</td></tr><tr><td>Recommendation: □ Approve &nbsp; □ Decline</td><td>Final decision: ___________________</td></tr></table></section>

<div class="footer">ADFC ScholarLink · This downloadable form reflects the application record at the time of download.</div>
</body>
</html>
