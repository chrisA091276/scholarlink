<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarship_applications', function (Blueprint $table): void {
            $table->string('guardian_name', 200)->nullable()->after('statement');
            $table->string('guardian_occupation', 150)->nullable()->after('guardian_name');
            $table->unsignedSmallInteger('household_size')->nullable()->after('guardian_occupation');
            $table->decimal('monthly_household_income', 12, 2)->nullable()->after('household_size');
        });
    }

    public function down(): void
    {
        Schema::table('scholarship_applications', function (Blueprint $table): void {
            $table->dropColumn(['guardian_name', 'guardian_occupation', 'household_size', 'monthly_household_income']);
        });
    }
};
