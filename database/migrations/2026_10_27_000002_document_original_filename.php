<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('application_documents', fn (Blueprint $table) => $table->string('original_filename')->nullable());
    }
    public function down(): void
    {
        Schema::table('application_documents', fn (Blueprint $table) => $table->dropColumn('original_filename'));
    }
};
