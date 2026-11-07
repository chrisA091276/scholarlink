<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eligibility_rules', function (Blueprint $table): void {
            $table->string('failure_mode', 20)->default('manual_review')->after('rule_value');
        });
    }

    public function down(): void
    {
        Schema::table('eligibility_rules', function (Blueprint $table): void {
            $table->dropColumn('failure_mode');
        });
    }
};
