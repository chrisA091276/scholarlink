@extends('layouts.app')
@section('title','Scholarship Directory')
@section('page-title','Scholarship Directory')
@section('content')
@php
  $photos = [
    'merit' => asset('images/dashboard/scholarship-merit.jpg'),
    'need-based' => asset('images/dashboard/scholarship-need-based.jpg'),
    'athletic' => asset('images/dashboard/scholarship-athletic.jpg'),
    'default' => asset('images/dashboard/scholarship-default.jpg'),
  ];
@endphp
<div class="student-page">
  <div class="student-page-head"><div><span class="student-eyebrow">FIND YOUR OPPORTUNITY</span><h1>Scholarship directory</h1><p>Explore ADFC scholarship programs and find support that matches your academic goals, financial needs, and talents.</p></div><a href="{{ route('my-applications.index') }}" class="btn btn-navy"><i class="bi bi-file-earmark-text me-2"></i>My applications</a></div>
  <div class="student-surface mb-4"><form method="GET" class="student-toolbar"><div class="flex-grow-1 position-relative"><i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i><input name="search" class="form-control student-filter ps-5" placeholder="Search by scholarship name or category" value="{{ request('search') }}"></div><select name="category" class="student-filter"><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c }}" {{ request('category')===$c?'selected':'' }}>{{ $c }}</option>@endforeach</select><button class="btn btn-navy"><i class="bi bi-sliders me-1"></i>Filter</button></form></div>
  <div class="d-flex justify-content-between align-items-center mb-3"><span class="student-note">{{ $programs->total() }} published {{ $programs->total() === 1 ? 'opportunity' : 'opportunities' }} available</span><span class="student-note">Browse · Compare · Apply</span></div>
  @if($programs->count())<div class="student-card-grid">
    @foreach($programs as $p)
      @php($photo = $photos[strtolower($p->category)] ?? $photos['default'])
      <article class="student-opportunity"><div class="student-opportunity-photo"><img src="{{ $photo }}" alt="{{ $p->category }} scholarship" loading="lazy" referrerpolicy="no-referrer"><span>{{ $p->category }}</span></div><div class="student-opportunity-body"><h3>{{ $p->name }}</h3><p>{{ \Illuminate\Support\Str::limit($p->description ?: $p->benefits, 118) }}</p><div class="student-opportunity-meta"><span><i class="bi bi-calendar3 me-1"></i>{{ $p->deadline?->format('M d, Y') ?? 'No deadline' }}</span><span>{{ $p->slots ? $p->slots.' slots' : 'Open slots' }}</span></div><a href="{{ route('scholarships.view',$p) }}" class="btn btn-sm btn-outline-primary mt-3">View details <i class="bi bi-arrow-up-right ms-1"></i></a></div></article>
    @endforeach
  </div>@else<div class="student-surface"><div class="student-empty"><i class="bi bi-search"></i><h3>No scholarships found</h3><p>Try a different search or category. Published opportunities will appear here when available.</p><a href="{{ route('scholarships.public') }}" class="btn btn-outline-primary">Clear filters</a></div></div>@endif
  <div class="mt-4">{{ $programs->links() }}</div>
</div>
@endsection
