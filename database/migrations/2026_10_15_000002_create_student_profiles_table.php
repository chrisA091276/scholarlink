<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('student_id_no', 50)->unique();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->date('date_of_birth')->nullable();
            $table->string('contact_number', 30)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('course', 150)->nullable();
            $table->string('year_level', 30)->nullable();
            $table->string('enrollment_status', 30)->default('enrolled');
            $table->decimal('gpa', 4, 2)->nullable();
            $table->boolean('profile_completed')->default(false);
            $table->timestamps();
            $table->index(['course', 'year_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
