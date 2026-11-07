<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->string('department', 150)->nullable()->after('course');
            $table->string('academic_year', 20)->nullable()->after('enrollment_status');
            $table->string('semester', 30)->nullable()->after('academic_year');
            $table->string('emergency_contact_name', 150)->nullable()->after('address');
            $table->string('emergency_contact_number', 30)->nullable()->after('emergency_contact_name');
            $table->string('parent_guardian_name', 200)->nullable()->after('semester');
            $table->string('parent_guardian_occupation', 150)->nullable()->after('parent_guardian_name');
            $table->unsignedSmallInteger('household_size')->nullable()->after('parent_guardian_occupation');
            $table->decimal('monthly_household_income', 12, 2)->nullable()->after('household_size');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'department', 'academic_year', 'semester', 'emergency_contact_name',
                'emergency_contact_number', 'parent_guardian_name', 'parent_guardian_occupation',
                'household_size', 'monthly_household_income',
            ]);
        });
    }
};
