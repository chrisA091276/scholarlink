@extends('layouts.app')
@section('title','Announcements')
@section('page-title','Manage Announcements')
@section('content')
<div class="d-flex justify-content-end mb-2"><a href="{{ route('announcements.create') }}" class="btn btn-sm btn-gold">+ New</a></div>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Title</th><th>Audience</th><th>Date</th><th></th></tr></thead><tbody>
@forelse($announcements as $a)<tr><td>{{ $a->title }}</td><td><span class="badge bg-secondary">{{ $a->audience }}</span></td><td class="small">{{ $a->created_at->format('M d, Y') }}</td><td><form method="POST" action="{{ route('announcements.destroy',$a) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
@empty<tr><td colspan="4" class="text-muted">No announcements.</td></tr>@endforelse
</tbody></table>{{ $announcements->links() }}</div></div>
@endsection
