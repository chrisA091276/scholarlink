@php
  $sectionSteps = ['applicant' => 2, 'school' => 3, 'family' => 4, 'assistance' => 4, 'consent' => 7];
  $uploadedCount = collect($formDocuments)->whereIn('status', ['pending', 'verified'])->count();
@endphp
<section class="adfc-paper">
  @if($activeApplication && !$editable && $activeApplication->form_version !== \App\Services\AdfcApplicationForm::VERSION)
  <div class="adfc-paper-heading"><div><span class="adfc-eyebrow">APPLICATION RECORD</span><h2>Your submitted form</h2><p>This application uses the earlier form format.</p></div></div>
  <a href="{{ $continueUrl }}" class="form-quick-link">View submitted details <i class="bi bi-arrow-right ms-auto"></i></a>
  @else
  <div class="adfc-paper-heading"><div><span class="adfc-eyebrow">FORM CHECKLIST</span><h2>Your application sections</h2><p>{{ $completedSections }} of {{ count($formSections) }} sections filled</p></div></div>
  @foreach($formSections as $key => $section)
    <a class="form-section-progress" href="{{ $continueUrl }}{{ $editable || !$activeApplication ? '#step-'.$sectionSteps[$key] : '#form-'.$key }}">
      <i class="bi {{ $section['icon'] }}" aria-hidden="true"></i>
      <div><strong>{{ $section['title'] }}</strong><small>{{ $section['filled'] }} of {{ $section['total'] }} required fields filled</small>
        <div class="progress" role="progressbar" aria-label="{{ $section['title'] }}" aria-valuenow="{{ $section['filled'] }}" aria-valuemin="0" aria-valuemax="{{ $section['total'] }}"><div class="progress-bar" style="width:{{ $section['total'] ? $section['filled']/$section['total']*100 : 0 }}%"></div></div>
      </div>
      <i class="bi {{ $section['complete'] ? 'bi-check-circle-fill text-success' : 'bi-arrow-up-right' }}" aria-hidden="true"></i>
    </a>
  @endforeach
  <p class="adfc-checklist-footnote">{{ $activeApplication?->declaration_accepted_at ? 'Consent recorded on '.$activeApplication->declaration_accepted_at->format('d/m/Y').'.' : 'Your printed name and date are saved with the form. Accept the declaration when submitting.' }}</p>
  @endif
</section>
<section class="adfc-paper">
  <div class="adfc-paper-heading"><div><span class="adfc-eyebrow">SUPPORTING EVIDENCE</span><h2>Your document checklist</h2><p>{{ $uploadedCount }} of {{ count($formDocuments) }} required uploads received</p></div></div>
  <div class="px-3"><ul class="adfc-checklist">
    @forelse($formDocuments as $document)
      <li><i class="bi {{ $document['icon'] }}" aria-hidden="true"></i><span>{{ $document['label'] }}<small class="{{ $document['status'] === 'verified' ? 'text-success' : '' }}">{{ $document['status_label'] }}</small></span></li>
    @empty
      <li>No required documents configured for this application.</li>
    @endforelse
  </ul></div>
  <p class="adfc-checklist-footnote">If you cannot secure a Certificate of Indigency, declare this on the form and upload a Certificate of Tax Exemption or Social Case Study.</p>
  <a href="{{ $editable ? $continueUrl.'#step-5' : route('documents.my') }}" class="form-quick-link">{{ $editable ? 'Manage uploads' : 'View my documents' }} <i class="bi bi-arrow-right ms-auto"></i></a>
</section>
