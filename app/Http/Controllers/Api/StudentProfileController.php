<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentProfileRequest;
use App\Http\Resources\StudentProfileResource;
use App\Models\AuditLog;
use App\Models\StudentProfile;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipAward;
use App\Models\AppNotification;
use Illuminate\Http\Request;

class StudentProfileController extends Controller
{
    public function show(Request $request)
    {
        $profile = $request->user()->studentProfile;

        return response()->json([
            'data' => $profile ? new StudentProfileResource($profile) : null,
            'profile_completed' => (bool) ($profile?->profile_completed),
        ]);
    }

    public function update(StudentProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        $data['user_id'] = $user->id;
        $data['profile_completed'] = ! empty($data['course'])
            && ! empty($data['year_level'])
            && ! empty($data['student_id_no']);

        $profile = StudentProfile::updateOrCreate(['user_id' => $user->id], $data);
        AuditLog::record($user, 'profile_update', $profile, 'Student profile updated via API');

        return response()->json([
            'message' => 'Profile saved successfully.',
            'data' => new StudentProfileResource($profile),
        ]);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'data' => [
                'profile_completed' => (bool) $user->studentProfile?->profile_completed,
                'applications' => ScholarshipApplication::with('program')->where('user_id', $user->id)->latest()->limit(5)->get(),
                'available_scholarships' => \App\Models\ScholarshipProgram::where('status', 'published')->orderBy('deadline')->limit(6)->get(),
                'awards' => ScholarshipAward::with('program')->where('user_id', $user->id)->latest()->get(),
                'unread_notifications' => AppNotification::where('user_id', $user->id)->where('is_read', false)->count(),
            ],
        ]);
    }
}
