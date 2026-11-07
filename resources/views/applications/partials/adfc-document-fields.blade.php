<div class="adfc-document-note mb-3"><i class="bi bi-shield-lock" aria-hidden="true"></i><div><strong>Your documents stay private.</strong><p>Upload a clear ID photo, your COR / COE, and a Certificate of Indigency. If the latter cannot be secured, declare this below and provide a permitted alternative.</p></div></div>
<input type="hidden" name="adfc_form[indigency_unavailable]" value="0">
<div class="form-check mb-4"><input type="checkbox" class="form-check-input" name="adfc_form[indigency_unavailable]" value="1" id="indigencyUnavailable" @checked(!empty($adfcData['indigency_unavailable']))><label for="indigencyUnavailable" class="form-check-label">I cannot secure a Certificate of Indigency and will provide a Certificate of Tax Exemption or a Social Case Study issued by a registered Social Worker.</label></div>
@if($application)
<div class="adfc-upload-grid">
@foreach(config('adfc_form.documents') as $category => $label)
  @continue($category === 'other')
  @php($document = $application->documents->firstWhere('document_category', $category))
  <div class="adfc-upload-card" @if(in_array($category, ['tax_exemption', 'social_case_study'])) data-alternative-document @endif>
    <div class="d-flex align-items-start justify-content-between gap-2"><i class="bi {{ $category === 'id_photo' ? 'bi-person-bounding-box' : 'bi-file-earmark-text' }} adfc-upload-icon" aria-hidden="true"></i><span class="adfc-badge {{ $document?->verification_status === 'verified' ? 'is-complete' : '' }}">{{ $document ? str_replace('_', ' ', $document->verification_status) : 'Not uploaded' }}</span></div>
    <h3>{{ $label }}</h3>
    <p>{{ $category === 'id_photo' ? 'JPG or PNG image for the photo box on your printable form.' : 'PDF, JPG, or PNG. Maximum '.number_format(config('scholarlink.max_upload_kb') / 1024, 1).' MB.' }}</p>
    @if($document)<p class="text-break"><strong>{{ $document->original_filename ?: $document->document_name }}</strong></p>@if($document->verification_notes)<p class="text-danger">{{ $document->verification_notes }}</p>@endif @endif
    @if(!$document || $application->status === 'draft' || in_array($document->verification_status, ['rejected', 'requires_correction']))
      <label for="upload-standard-{{ $category }}" class="visually-hidden">Upload {{ $label }}</label>
      <input type="file" class="form-control form-control-sm" id="upload-standard-{{ $category }}" name="file" form="upload-form-standard-{{ $category }}" accept="{{ $category === 'id_photo' ? '.jpg,.jpeg,.png' : '.pdf,.jpg,.jpeg,.png' }}" required>
      <button type="submit" form="upload-form-standard-{{ $category }}" class="btn btn-sm btn-outline-primary mt-2">{{ $document ? 'Replace file' : 'Upload file' }}</button>
      <progress class="w-100 d-none mt-2" id="progress-standard-{{ $category }}" max="100" value="0" aria-label="Upload progress"></progress><p id="upload-status-standard-{{ $category }}" role="status" class="small mt-2"></p>
    @endif
  </div>
@endforeach
</div>
@endif
