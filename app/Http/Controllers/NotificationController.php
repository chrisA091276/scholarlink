<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AppNotification;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = AppNotification::where('user_id', Auth::id())->latest()->paginate(15);
        return view('notifications.index', compact('notifications'));
    }

    public function markRead(AppNotification $notification)
    {
        abort_unless($notification->user_id === Auth::id(), 403);
        $notification->update(['is_read' => true, 'read_at' => now()]);
        return back();
    }

    public function markAllRead()
    {
        AppNotification::where('user_id', Auth::id())->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        return back()->with('success', 'All notifications marked as read.');
    }

    public function announcements()
    {
        $user = Auth::user();
        $audiences = $user->isStudent() ? ['all', 'students'] : ($user->isStaff() ? ['all', 'staff'] : ['all', 'students', 'staff', 'admin']);
        $announcements = Announcement::where('is_published', true)
            ->whereIn('audience', $audiences)->latest()->paginate(10);
        return view('notifications.announcements', compact('announcements'));
    }
}
