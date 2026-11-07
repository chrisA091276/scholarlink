@if($application->form_version === \App\Services\AdfcApplicationForm::VERSION)
<section class="adfc-paper mb-3" id="office-review">
  <div class="adfc-paper-heading"><div><span class="adfc-eyebrow">FOR OFFICE USE ONLY</span><h2>ADFC coordinator review</h2><p>Check the saved form against the submitted evidence.</p></div><i class="bi bi-clipboard2-check fs-3 text-primary" aria-hidden="true"></i></div>
  <div class="p-3">
    <h3 class="h6">Documents attached</h3><ul class="adfc-checklist">
      @foreach($application->documents as $document)<li><i class="bi {{ $document->verification_status === 'verified' ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }}" aria-hidden="true"></i><span>{{ config('adfc_form.documents.'.$document->document_category) ?: $document->document_name }}<small>{{ str_replace('_', ' ', $document->verification_status) }}</small></span></li>@endforeach
    </ul>
    @if(in_array($application->status, ['submitted','under_review','incomplete_requirements','for_evaluation']))
    <form method="POST" action="{{ route('applications.form-review', $application) }}">@csrf
      <fieldset><legend class="h6 mt-3">Sections reviewed</legend>
      @foreach(config('adfc_form.sections') as $key => $section)<div class="form-check mb-2"><input type="hidden" name="{{ $key }}" value="0"><input type="checkbox" class="form-check-input" id="review-{{ $key }}" name="{{ $key }}" value="1" @checked(old($key, $application->form_review[$key] ?? false))><label for="review-{{ $key }}" class="form-check-label">{{ $section['title'] }}</label></div>@endforeach
      </fieldset>
      <label class="form-label mt-3" for="qualificationReview">Qualification assessment</label><select class="form-select" id="qualificationReview" name="qualification" required><option value="">Choose assessment</option>@foreach(['needs_clarification'=>'Needs clarification','meets'=>'Meets applicable qualifications','not_qualified'=>'Does not meet applicable qualifications'] as $value => $label)<option value="{{ $value }}" @selected(old('qualification', $application->form_review['qualification'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
      <label class="form-label mt-3" for="formReviewNotes">Office notes / explanation</label><textarea class="form-control" id="formReviewNotes" name="notes" rows="3" maxlength="2000" required>{{ old('notes', $application->form_review['notes'] ?? '') }}</textarea>
      <button class="btn btn-navy mt-3">Save office review</button>
    </form>
    @else<p class="small text-muted">Office review is read-only at this stage.</p>@endif
    @if($application->form_review)<div class="adfc-review-record mt-3"><strong>Evaluated / processed by {{ $application->form_review['reviewer_name'] ?? 'ADFC officer' }}</strong><span>{{ str_replace('_',' ',$application->form_review['qualification'] ?? '') }} · {{ $application->form_review['reviewed_at'] ?? '' }}</span><p>{{ $application->form_review['notes'] ?? '' }}</p></div>@endif
  </div>
</section>
@endif
