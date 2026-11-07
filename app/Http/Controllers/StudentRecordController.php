<?php

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;

class StudentRecordController extends Controller
{
    public function index(Request $request)
    {
        $query = StudentProfile::with(['user.applications' => function ($q) {
            if (auth()->user()->isStaff()) $q->where('assigned_to', auth()->id());
            $q->with('program');
        }])->latest();
        if (auth()->user()->isStaff()) $query->whereHas('user.applications', fn ($q) => $q->where('assigned_to', auth()->id()));
        if ($s = $request->get('search')) {
            $query->where(fn ($q) => $q->where('student_id_no', 'like', "%{$s}%")
                ->orWhere('first_name', 'like', "%{$s}%")
                ->orWhere('last_name', 'like', "%{$s}%")
                ->orWhereHas('user', fn ($qq) => $qq->where('email', 'like', "%{$s}%")));
        }
        if ($c = $request->get('course')) $query->where('course', 'like', "%{$c}%");
        if ($y = $request->get('year_level')) $query->where('year_level', $y);
        $students = $query->paginate(15)->withQueryString();

        return view('students.index', compact('students'));
    }

    public function show(User $student)
    {
        abort_unless($student->isStudent(), 404);
        if (auth()->user()->isStaff()) abort_unless($student->applications()->where('assigned_to', auth()->id())->exists(), 403);
        $student->load(['studentProfile', 'applications' => function ($q) {
            if (auth()->user()->isStaff()) $q->where('assigned_to', auth()->id());
            $q->with(['program', 'documents']);
        }, 'awards' => function ($q) {
            if (auth()->user()->isStaff()) $q->whereHas('application', fn ($a) => $a->where('assigned_to', auth()->id()));
            $q->with('program');
        }]);
        return view('students.show', compact('student'));
    }
}
