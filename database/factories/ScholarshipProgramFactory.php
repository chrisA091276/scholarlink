<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScholarshipProgramFactory extends Factory
{
    public function definition(): array
    {
        return ['code' => 'DEMO-'.fake()->unique()->numerify('######'), 'name' => '[FICTIONAL] Test Scholarship',
            'category' => 'Demonstration', 'status' => 'published', 'opening_date' => today()->subDay(),
            'deadline' => today()->addMonth(), 'created_by' => User::factory()->state(['role' => 'admin'])];
    }
}
