@extends('layouts.guest')
@section('title','Reset password')
@section('content')
<h5 class="fw-bold">Reset password</h5>
<p class="small">{{ \App\Support\PasswordRules::HELP }}</p>
<form method="POST" action="{{ route('password.update') }}">@csrf
<input type="hidden" name="token" value="{{ $token }}">
<div class="mb-2"><input type="email" name="email" class="form-control" value="{{ $email ?? old('email') }}" placeholder="Email" required></div>
<div class="mb-2"><input type="password" name="password" class="form-control" placeholder="New password (min 8)" required></div>
<div class="mb-3"><input type="password" name="password_confirmation" class="form-control" placeholder="Confirm password" required></div>
<button class="btn btn-gold w-100">Reset password</button>
</form>
@endsection
