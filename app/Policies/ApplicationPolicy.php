<?php

namespace App\Policies;

use App\Models\ScholarshipApplication;
use App\Models\User;

class ApplicationPolicy
{
    public function view(User $user, ScholarshipApplication $application): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isStaff()) return (int) $application->assigned_to === (int) $user->id;
        return $application->user_id === $user->id;
    }

    public function update(User $user, ScholarshipApplication $application): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isStaff()) return (int) $application->assigned_to === (int) $user->id;
        return $application->user_id === $user->id
            && in_array($application->status, ['draft', 'incomplete_requirements'], true);
    }

    public function decide(User $user): bool
    {
        return $user->isAdmin();
    }

    public function evaluate(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }
}
