<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentProfileRequest;
use App\Models\AuditLog;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\Auth;

class StudentProfileController extends Controller
{
    public function show()
    {
        $profile = Auth::user()->studentProfile;
        return view('profile.show', compact('profile'));
    }

    public function edit()
    {
        $profile = Auth::user()->studentProfile;
        return view('profile.edit', compact('profile'));
    }

    public function update(StudentProfileRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        $data['user_id'] = $user->id;
        $data['profile_completed'] = ! empty($data['course']) && ! empty($data['year_level']) && ! empty($data['student_id_no']);

        $profile = StudentProfile::updateOrCreate(['user_id' => $user->id], $data);
        AuditLog::record($user, 'profile_update', $profile, 'Student profile updated');

        return redirect()->route('profile.show')->with('success', 'Profile saved successfully.');
    }
}
