<?php

namespace Database\Factories;

use App\Models\{ScholarshipApplication, ScholarshipProgram, User};
use Illuminate\Database\Eloquent\Factories\Factory;

class ScholarshipApplicationFactory extends Factory
{
    public function definition(): array
    {
        return ['application_no' => ScholarshipApplication::generateApplicationNo(), 'user_id' => User::factory()->state(['role' => 'student']),
            'scholarship_program_id' => ScholarshipProgram::factory(), 'academic_year' => now()->year.'-'.(now()->year+1),
            'status' => 'draft', 'enrollment_status' => 'enrolled', 'last_saved_step' => 1];
    }
}
