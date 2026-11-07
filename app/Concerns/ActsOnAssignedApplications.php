<?php

namespace App\Concerns;

use App\Models\ScholarshipApplication;
use Illuminate\Support\Facades\Auth;

trait ActsOnAssignedApplications
{
    /**
     * Staff may only act on applications assigned to them.
     * Admins are unrestricted. Everyone else gets 403.
     */
    protected function ensureCanActOn(ScholarshipApplication $application): void
    {
        $user = request()->user() ?? Auth::user();
        if ($user && $user->isAdmin()) return;
        abort_unless(
            $user && $user->isStaff() && (int) $application->assigned_to === (int) $user->id,
            403, 'This application is not assigned to you. Ask an administrator to assign it first.'
        );
    }
}
