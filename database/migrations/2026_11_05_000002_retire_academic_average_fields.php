<?php

use App\Support\RetiredAcademicFields;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Retire configuration. Historical numeric values remain private and unused.
        DB::transaction(function () {
            $rules = DB::table('eligibility_rules')->get(['id', 'rule_key', 'rule_label']);
            $ids = $rules->filter(fn ($rule) => RetiredAcademicFields::matches($rule->rule_key) || RetiredAcademicFields::matches($rule->rule_label))->pluck('id');
            DB::table('eligibility_rules')->whereIn('id', $ids)->delete();
            foreach (DB::table('settings')->pluck('key') as $key) {
                if (RetiredAcademicFields::matches($key)) DB::table('settings')->where('key', $key)->delete();
            }
            foreach (DB::table('scholarship_programs')->get(['id', 'application_template', 'renewal_template']) as $program) {
                $updates = [];
                foreach (['application_template', 'renewal_template'] as $column) {
                    $fields = json_decode($program->{$column} ?? '[]', true) ?: [];
                    $filtered = array_values(array_filter($fields, fn ($field) => ! RetiredAcademicFields::matches($field['key'] ?? '') && ! RetiredAcademicFields::matches($field['label'] ?? '')));
                    if ($filtered !== $fields) $updates[$column] = json_encode($filtered);
                }
                if ($updates) DB::table('scholarship_programs')->where('id', $program->id)->update($updates);
            }
            DB::table('scholarship_programs')->where('eligibility_criteria', 'GPA of 2.0 or better; enrolled; no failing grades.')
                ->update(['eligibility_criteria' => 'Enrolled; no failing grades.']);
        });
    }

    public function down(): void
    {
        // Retired criteria are not automatically restored; a database backup retains prior configuration.
    }
};
