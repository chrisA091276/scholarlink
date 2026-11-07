@extends('layouts.app')
@section('title', 'Account Settings')
@section('page-title', 'Account Settings')

@section('content')
@php
  $roleDisplay = ['admin' => 'Administrator', 'staff' => 'Scholarship staff', 'student' => 'Student'][$user->role] ?? ucfirst($user->role);
  $roleIcon = ['admin' => 'bi-shield-check', 'staff' => 'bi-clipboard-check', 'student' => 'bi-mortarboard'][$user->role] ?? 'bi-person';
  $avatarInitials = collect(preg_split('/\s+/', trim($user->name)) ?: [])->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: 'U';
  $avatarUrl = $user->avatar_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar_path)
      ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path)
      : null;
@endphp

<div class="student-page account-settings-page">
  <div class="student-page-head">
    <div>
      <span class="student-eyebrow">SECURE YOUR ACCOUNT</span>
      <h1>Account settings</h1>
      <p>Update your sign-in details and profile photo.</p>
    </div>
    <a href="{{ auth()->user()->isStudent() ? route('profile.show') : route('dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Back</a>
  </div>

  <div class="row g-4">
    <div class="col-xl-6">
      <div class="student-form-card h-100">
        <div class="student-section-title">
          <h2><i class="bi bi-person-circle me-2 text-primary"></i>Profile and account</h2>
          <span class="status-chip {{ $user->status === 'active' ? 'green' : 'red' }}">{{ $user->status }}</span>
        </div>

        <form method="POST" action="{{ route('account.update') }}" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="account-photo-field mb-4">
            <div class="account-avatar-preview" id="avatarPreviewWrap">
              @if($avatarUrl)
                <img id="avatarPreview" src="{{ $avatarUrl }}" data-saved-src="{{ $avatarUrl }}" alt="{{ $user->name }} profile photo">
                <span id="avatarFallback" class="account-avatar-fallback role-avatar role-avatar-{{ $user->role }} d-none"><span>{{ $avatarInitials }}</span><i class="bi {{ $roleIcon }}"></i></span>
              @else
                <img id="avatarPreview" src="" data-saved-src="" alt="{{ $user->name }} profile photo" class="d-none">
                <span id="avatarFallback" class="account-avatar-fallback role-avatar role-avatar-{{ $user->role }}"><span>{{ $avatarInitials }}</span><i class="bi {{ $roleIcon }}"></i></span>
              @endif
            </div>
            <div class="account-photo-copy">
              <label for="avatar" class="form-label mb-1">Profile photo</label>
              <p class="student-note mb-2">Shown in your workspace avatar. JPG, PNG, or WebP, up to 2 MB.</p>
              <input id="avatar" name="avatar" type="file" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
              @error('avatar')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
              @if($avatarUrl)
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" value="1" id="removeAvatar" name="remove_avatar" {{ old('remove_avatar') ? 'checked' : '' }}>
                  <label class="form-check-label small text-muted" for="removeAvatar">Remove current photo</label>
                </div>
              @endif
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="accountName">Full name <span class="text-danger">*</span></label>
            <input id="accountName" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>
          <div class="mb-3">
            <label class="form-label" for="accountEmail">Email address <span class="text-danger">*</span></label>
            <input id="accountEmail" type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>

          <div class="student-alert" style="background:#f5f8fc;border-color:#e1e8f1;color:#51647e">
            <i class="bi bi-shield-lock"></i>
            <span>Your role is <strong>{{ $roleDisplay }}</strong>. Role permissions are managed securely by ADFC administrators.</span>
          </div>
          <button class="btn btn-gold mt-4"><i class="bi bi-check2 me-1"></i>Save profile</button>
        </form>
      </div>
    </div>

    <div class="col-xl-6">
      <div class="student-form-card h-100">
        <div class="student-section-title"><h2><i class="bi bi-key me-2 text-primary"></i>Change password</h2></div>
        <p class="student-note mb-3">{{ \App\Support\PasswordRules::HELP }} Choose one you do not use elsewhere.</p>
        <form method="POST" action="{{ route('account.password') }}">
          @csrf
          @method('PUT')
          <div class="mb-3"><label class="form-label" for="currentPassword">Current password <span class="text-danger">*</span></label><input id="currentPassword" type="password" name="current_password" class="form-control" required></div>
          <div class="mb-3"><label class="form-label" for="newPassword">New password <span class="text-danger">*</span></label><input id="newPassword" type="password" name="password" class="form-control" minlength="8" required></div>
          <div class="mb-3"><label class="form-label" for="confirmPassword">Confirm new password <span class="text-danger">*</span></label><input id="confirmPassword" type="password" name="password_confirmation" class="form-control" required></div>
          <button class="btn btn-navy"><i class="bi bi-shield-check me-1"></i>Update password</button>
        </form>
      </div>
    </div>
  </div>
</div>

@push('styles')
<style>
  .account-photo-field{display:flex;align-items:center;gap:16px;padding:15px;border:1px solid #e6edf5;border-radius:14px;background:#f9fbfd}
  .account-avatar-preview{position:relative;display:grid;place-items:center;flex:0 0 78px;width:78px;height:78px;border-radius:20px}
  .account-avatar-preview img,.account-avatar-fallback{width:78px!important;height:78px!important;border-radius:20px!important}
  .account-avatar-preview img{object-fit:cover;border:1px solid #d9e2ee;box-shadow:0 3px 10px rgba(16,35,63,.1)}
  .account-avatar-fallback{font-size:1.15rem}
  .account-avatar-fallback i{right:-3px;bottom:-3px;width:22px;height:22px;font-size:.68rem}
  .account-photo-copy{min-width:0;flex:1}
  .account-photo-copy .form-control{max-width:360px;font-size:.73rem}
  @media(max-width:480px){.account-photo-field{align-items:flex-start;flex-direction:column}.account-photo-copy{width:100%}}
</style>
@endpush

@push('scripts')
<script>
  (() => {
    const input = document.getElementById('avatar');
    const preview = document.getElementById('avatarPreview');
    const fallback = document.getElementById('avatarFallback');
    const removeAvatar = document.getElementById('removeAvatar');
    if (!input || !preview) return;

    if (removeAvatar) {
      const reflectRemoval = () => {
        if (removeAvatar.checked) {
          preview.classList.add('d-none');
          fallback?.classList.remove('d-none');
        } else if (preview.dataset.savedSrc) {
          preview.src = preview.dataset.savedSrc;
          preview.classList.remove('d-none');
          fallback?.classList.add('d-none');
        }
      };
      removeAvatar.addEventListener('change', reflectRemoval);
      reflectRemoval();
    }

    input.addEventListener('change', () => {
      const file = input.files && input.files[0];
      if (!file) return;
      preview.src = URL.createObjectURL(file);
      preview.classList.remove('d-none');
      fallback?.classList.add('d-none');
      if (removeAvatar) removeAvatar.checked = false;
    });
  })();
</script>
@endpush
@endsection
