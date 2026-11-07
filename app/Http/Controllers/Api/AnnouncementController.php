<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function publicIndex()
    {
        $announcements = Announcement::where('is_published', true)
            ->where('audience', 'all')
            ->latest('published_at')
            ->latest()
            ->paginate(10);

        return AnnouncementResource::collection($announcements);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $audiences = $user->isStudent()
            ? ['all', 'students']
            : ($user->isStaff() ? ['all', 'staff'] : ['all', 'students', 'staff', 'admin']);

        $announcements = Announcement::where('is_published', true)
            ->whereIn('audience', $audiences)
            ->latest('published_at')
            ->latest()
            ->paginate(10);

        return AnnouncementResource::collection($announcements);
    }
}
