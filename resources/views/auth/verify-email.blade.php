@extends('layouts.app')
@section('title', 'Verify your email')
@section('page-title', 'Verify your email')
@section('content')
<div class="card border-0 shadow-sm p-4">
<h1 class="h4">Confirm your email address</h1>
<p>Open the verification link from your email while signed in to this account. You can save drafts while waiting. Email verification is required before submission when enabled by the school.</p>
@if(auth()->user()->hasVerifiedEmail())
<p class="text-success">Your current email address is verified.</p>
@else
<form method="POST" action="{{ route('verification.send') }}">@csrf<button class="btn btn-primary">Request verification email</button></form>
@endif
</div>
@endsection
