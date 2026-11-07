<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('creator')->latest()->paginate(15);
        return view('announcements.index', compact('announcements'));
    }

    public function create() { return view('announcements.create'); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'content' => 'required|string',
            'audience' => 'required|in:all,students,staff,admin',
        ]);
        $data['is_published'] = true;
        $data['published_at'] = now();
        $data['created_by'] = Auth::id();
        $announcement = Announcement::create($data);
        AuditLog::record(Auth::user(), 'announcement_create', $announcement, 'Announcement: ' . $data['title']);

        // Fan-out in-system notifications to the target audience
        $audience = $data['audience'];
        $role = $audience === 'students' ? 'student' : $audience;
        $recipients = \App\Models\User::where('status', 'active')
            ->when($audience !== 'all', fn ($q) => $q->where('role', $role))
            ->pluck('id');
        foreach ($recipients as $uid) {
            \App\Services\NotifyService::send($uid, 'Announcement: ' . $data['title'], \Illuminate\Support\Str::limit($data['content'], 200), route('announcements.view'));
        }

        return redirect()->route('announcements.index')->with('success', 'Announcement published to ' . $recipients->count() . ' user(s).');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return back()->with('success', 'Announcement deleted.');
    }
}
