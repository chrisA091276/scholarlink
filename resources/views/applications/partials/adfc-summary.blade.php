@if($application->form_version === \App\Services\AdfcApplicationForm::VERSION)
@php($adfcService = app(\App\Services\AdfcApplicationForm::class))
<section class="adfc-paper mb-4">
  <div class="adfc-paper-heading"><div><span class="adfc-eyebrow">ADFC APPLICATION / STUDENT FINANCIAL ASSISTANCE</span><h2>Scholarship application form</h2><p>Regional / processing office: {{ $application->adfc_form['processing_office'] ?? 'Not provided' }}</p></div><span class="adfc-badge">{{ $application->application_no }}</span></div>
  @foreach($adfcService->sections() as $key => $definition)
    <div class="adfc-summary-section" id="form-{{ $key }}"><h3><i class="bi {{ $definition['icon'] }} me-2" aria-hidden="true"></i>{{ $definition['title'] }}</h3><dl class="adfc-answer-grid">
      @foreach($definition['fields'] as $fieldKey => $field)<div><dt>{{ $field['label'] }}</dt><dd>{{ $adfcService->display($fieldKey, $application->adfc_form[$fieldKey] ?? null) }}</dd></div>@endforeach
    </dl></div>
  @endforeach
  <div class="adfc-summary-section"><p class="mb-0 small"><strong>Declaration accepted:</strong> {{ $application->declaration_accepted_at?->format('d/m/Y h:i A') ?? 'Not yet submitted' }}. {{ !empty($application->adfc_form['indigency_unavailable']) ? 'Applicant declared that a Certificate of Indigency cannot be secured.' : '' }}</p></div>
</section>
@endif
