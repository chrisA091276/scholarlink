<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegistrationService
{
    public function register(array $data): User
    {
        try {
            $user = DB::transaction(function () use ($data) {
            $name = trim($data['name'] ?? implode(' ', array_filter([
                $data['first_name'] ?? null, $data['middle_name'] ?? null,
                $data['last_name'] ?? null, $data['suffix'] ?? null,
            ])));
            $user = User::create([
                'name' => $name, 'email' => $data['email'], 'password' => $data['password'],
                'role' => 'student', 'status' => 'active',
            ]);
            $user->studentProfile()->create([
                'student_id_no' => $data['student_id_no'] ?? null,
                'first_name' => $data['first_name'] ?? $name,
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'] ?? '',
                'suffix' => $data['suffix'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
            ]);
            AuditLog::record($user, 'register', $user, 'Student accepted the privacy notice and terms (2026-10-26).');
            return $user;
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $exception) {
            // Validation may race with another request; constraints remain authoritative.
            if (User::where('email', $data['email'])->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['email' => 'This email address is already registered.']);
            }
            if (! empty($data['student_id_no']) && \App\Models\StudentProfile::where('student_id_no', $data['student_id_no'])->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['student_id_no' => 'This student number is already registered.']);
            }
            throw $exception;
        }
        if (config('scholarlink.verify_email')) $user->sendEmailVerificationNotification();
        return $user;
    }
}
