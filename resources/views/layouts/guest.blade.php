<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'ScholarLink')</title>
<link href="{{ asset('vendor/bootstrap/5.3.3/css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css') }}" rel="stylesheet">
<link href="{{ asset('css/adfc.css') }}" rel="stylesheet">
@php
$_campus = collect(['images/adfc-campus.jpg','images/adfc-campus.png','images/adfc-campus.jpeg'])->first(fn($p) => file_exists(public_path($p)), 'images/adfc-campus.jpg');
@endphp
<style>
:root{--navy:#0a1931;--navy2:#12295e;--gold:#c9a227}
body{min-height:100vh;position:relative;background:linear-gradient(135deg,#0a1931,#12295e)}
body::before{content:'';position:fixed;inset:0;background:url("{{ asset($_campus) }}") center/cover;opacity:.28;animation:heroZoom 30s ease-in-out infinite alternate}
body::after{content:'';position:fixed;inset:0;background:linear-gradient(135deg,rgba(10,25,49,.72),rgba(18,41,94,.62))}
.auth-card{border:1px solid rgba(255,255,255,.22);border-radius:1rem;color:#fff;
  background:rgba(255,255,255,.035);
  -webkit-backdrop-filter:blur(8px) saturate(130%);backdrop-filter:blur(8px) saturate(130%);
  box-shadow:0 18px 50px rgba(0,0,0,.22),inset 0 1px 0 rgba(255,255,255,.16);
  position:relative;z-index:1}
.auth-card .text-muted{color:rgba(255,255,255,.68)!important}
.auth-card .form-label,.auth-card .form-check-label{color:rgba(255,255,255,.88)}
.auth-card .form-control,.auth-card .input-group-text{background:rgba(255,255,255,.035);
  border-color:rgba(255,255,255,.28);color:#fff}
.auth-card .form-control::placeholder{color:rgba(255,255,255,.45)}
.auth-card .form-control:focus{background:rgba(255,255,255,.08);border-color:var(--gold2);color:#fff;
  box-shadow:0 0 0 .2rem rgba(201,162,39,.25)}
.auth-card .input-group-text{color:rgba(255,255,255,.8)}
.auth-card .form-check-input{background-color:rgba(255,255,255,.12);border-color:rgba(255,255,255,.4)}
.auth-card .form-check-input:checked{background-color:var(--gold);border-color:var(--gold)}
.auth-card .btn-outline-secondary{color:rgba(255,255,255,.85);border-color:rgba(255,255,255,.28)}
.auth-card .btn-outline-secondary:hover{background:rgba(255,255,255,.18);border-color:rgba(255,255,255,.45);color:#fff}
.auth-card a{color:var(--gold2)}
.auth-card a:hover{color:#fff}
.auth-card .role-tab{background:transparent;border-color:rgba(255,255,255,.25);color:rgba(255,255,255,.8)}
.auth-card .role-tab:hover{border-color:var(--gold2);color:#fff}
.auth-card .role-tab.active{background:rgba(201,162,39,.2);border-color:var(--gold2);color:#fff;
  box-shadow:0 6px 18px rgba(0,0,0,.3)}
.auth-card .role-tab.active i{color:var(--gold)}
.auth-card .demo-box{color:rgba(255,255,255,.85);background:transparent}
.auth-card .demo-box code{color:var(--gold2)}
.btn-gold{background:var(--gold);border-color:var(--gold);color:#0a1931;font-weight:600}
.btn-gold:hover{background:#e6c84a;color:#0a1931}
.auth-wrap{position:relative;z-index:1}
</style>
<link href="{{ asset('css/academic.css') }}" rel="stylesheet">
<link href="{{ asset('css/responsive.css') }}?v={{ filemtime(public_path('css/responsive.css')) }}" rel="stylesheet">
</head>
<body class="auth-page d-flex align-items-center py-5">
<div class="container auth-wrap"><div class="row justify-content-center"><div class="col-md-6 col-lg-5">
<div class="text-center text-white mb-4 page-enter">
  <x-brand-logo :size="84" :float="true" />
  <h4 class="fw-bold mt-3 mb-0">Asian Development Foundation College</h4>
  <div class="small" style="color:var(--gold)">1984 &middot; Tacloban City</div>
  <small class="opacity-75">ScholarLink: Student Scholarship Management System</small>
</div>
<div class="card auth-card page-enter"><div class="card-body p-4">
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
  @yield('content')
</div></div>
<div class="text-center mt-3"><a href="{{ route('landing') }}" class="text-white-50 text-decoration-none small">&larr; Back to home</a></div>
</div></div></div>
<script src="{{ asset('vendor/bootstrap/5.3.3/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/adfc.js') }}"></script>
<x-field-errors />
</body>
</html>
