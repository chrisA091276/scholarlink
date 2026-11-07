<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarship_programs', function (Blueprint $table): void {
            $table->json('application_template')->nullable()->after('eligibility_criteria');
        });

        Schema::table('scholarship_applications', function (Blueprint $table): void {
            $table->json('form_data')->nullable()->after('statement');
        });
    }

    public function down(): void
    {
        Schema::table('scholarship_applications', function (Blueprint $table): void {
            $table->dropColumn('form_data');
        });

        Schema::table('scholarship_programs', function (Blueprint $table): void {
            $table->dropColumn('application_template');
        });
    }
};
