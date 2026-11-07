<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarship_applications', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('scholarship_program_id')
                ->constrained('users')->nullOnDelete();
            $table->index('assigned_to', 'sch_apps_assigned_to_idx');
        });
    }

    public function down(): void
    {
        Schema::table('scholarship_applications', function (Blueprint $table) {
            $table->dropIndex('sch_apps_assigned_to_idx');
            $table->dropConstrainedForeignId('assigned_to');
        });
    }
};
