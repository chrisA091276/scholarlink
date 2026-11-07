@extends('layouts.guest')
@section('title','Forgot password')
@section('content')
<h5 class="fw-bold">Forgot password?</h5>
<p class="small text-muted">Enter your email and we'll send a reset link.</p>
<form method="POST" action="{{ route('password.email') }}">@csrf
<div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Email address" required></div>
<button class="btn btn-gold w-100">Send reset link</button>
</form>
<p class="small text-center mt-3 mb-0"><a href="{{ route('login') }}">Back to login</a></p>
@endsection
