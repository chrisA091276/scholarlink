@extends('layouts.app')
@section('title', isset($user) ? 'Edit User' : 'New User')
@section('page-title', isset($user) ? 'Edit User' : 'New User')
@section('content')
<div class="card"><div class="card-body col-lg-8">
<form method="POST" action="{{ isset($user) ? route('users.update',$user) : route('users.store') }}">@csrf @if(isset($user))@method('PUT')@endif
<div class="mb-2"><label class="form-label small fw-semibold">Name *</label><input name="name" class="form-control" value="{{ old('name',$user->name ?? '') }}" required></div>
<div class="mb-2"><label class="form-label small fw-semibold">Email *</label><input type="email" name="email" class="form-control" value="{{ old('email',$user->email ?? '') }}" required></div>
<div class="row g-2">
<div class="col-md-6"><label class="form-label small fw-semibold">Role *</label><select name="role" class="form-select">@foreach(['student','staff','admin'] as $r)<option {{ old('role',$user->role ?? 'student')==$r?'selected':'' }}>{{ $r }}</option>@endforeach</select></div>
@if(isset($user))<div class="col-md-6"><label class="form-label small fw-semibold">Status *</label><select name="status" class="form-select"><option value="active" {{ $user->status=='active'?'selected':'' }}>active</option><option value="inactive" {{ $user->status=='inactive'?'selected':'' }}>inactive</option></select></div>@endif
</div>
<div class="row g-2 mt-1"><div class="col-md-6"><label class="form-label small fw-semibold">{{ isset($user)?'New password (leave blank to keep)':'Password *' }}</label><input type="password" name="password" class="form-control" {{ isset($user)?'':'required' }} minlength="8"></div>
<div class="col-md-6"><label class="form-label small fw-semibold">Confirm password</label><input type="password" name="password_confirmation" class="form-control"></div></div>
<p class="form-text">{{ \App\Support\PasswordRules::HELP }}</p>
<button class="btn btn-gold mt-3">Save user</button>
</form>
</div></div>
@endsection
