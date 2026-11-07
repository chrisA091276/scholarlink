<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::table('renewal_applications')->select('scholarship_award_id', 'academic_year')->groupBy('scholarship_award_id', 'academic_year')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Duplicate renewal cycles exist. Review them before applying this migration; no records were removed.');
        }
        Schema::table('renewal_applications', fn (Blueprint $table) => $table->unique(['scholarship_award_id', 'academic_year'], 'renewals_award_cycle_unique'));
    }
    public function down(): void
    {
        Schema::table('renewal_applications', fn (Blueprint $table) => $table->dropUnique('renewals_award_cycle_unique'));
    }
};
