<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarship_applications', function (Blueprint $table) {
            $table->string('form_version', 40)->nullable();
            $table->json('adfc_form')->nullable();
            $table->json('form_review')->nullable();
        });
        Schema::table('application_documents', function (Blueprint $table) {
            $table->string('document_category', 40)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('application_documents', fn (Blueprint $table) => $table->dropColumn('document_category'));
        Schema::table('scholarship_applications', fn (Blueprint $table) => $table->dropColumn(['form_version', 'adfc_form', 'form_review']));
    }
};
