@extends('layouts.app')
@section('title','New Announcement')
@section('page-title','New Announcement')
@section('content')
<div class="card"><div class="card-body col-lg-8">
<form method="POST" action="{{ route('announcements.store') }}">@csrf
<div class="mb-2"><label class="form-label small fw-semibold">Title *</label><input name="title" class="form-control" required></div>
<div class="mb-2"><label class="form-label small fw-semibold">Content *</label><textarea name="content" class="form-control" rows="4" required></textarea></div>
<div class="mb-2"><label class="form-label small fw-semibold">Audience *</label><select name="audience" class="form-select"><option value="all">Everyone</option><option value="students">Students</option><option value="staff">Staff</option><option value="admin">Admins</option></select></div>
<button class="btn btn-gold">Publish</button>
</form>
</div></div>
@endsection
