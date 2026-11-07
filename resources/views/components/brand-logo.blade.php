{{-- ADFC logo with graceful fallback.
     Official logo: public/images/adfc-logo.jpg (png also accepted)
     Campus photo: public/images/adfc-campus.jpg --}}
@props(['size' => 44, 'float' => false])
@php $logo = file_exists(public_path('images/adfc-logo.jpg')) ? 'images/adfc-logo.jpg' : 'images/adfc-logo.png'; @endphp
<span class="d-inline-flex align-items-center justify-content-center bg-white rounded-3 {{ $float ? 'logo-float' : '' }}"
      style="width:{{ $size }}px;height:{{ $size }}px;position:relative;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.25)">
  <i class="bi bi-mortarboard-fill" style="color:#0a1931;font-size:{{ (int) ($size * 0.55) }}px"></i>
  <img src="{{ asset($logo) }}" alt="ADFC logo"
       style="position:absolute;inset:0;width:100%;height:100%;object-fit:contain;padding:3px;background:#fff"
       onerror="this.remove()">
</span>
