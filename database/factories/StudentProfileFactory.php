<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentProfileFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory()->state(['role' => 'student']), 'student_id_no' => 'TEST-'.fake()->unique()->numerify('######'),
            'first_name' => 'Fictional', 'last_name' => 'Student', 'contact_number' => '+639171234567',
            'date_of_birth' => '2004-01-01', 'address' => 'Fictional test address',
            'course' => 'Demonstration course', 'year_level' => '2nd', 'enrollment_status' => 'enrolled', 'profile_completed' => true];
    }
}
