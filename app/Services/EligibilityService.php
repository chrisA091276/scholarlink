<?php

namespace App\Services;

use App\Models\EligibilityResult;
use App\Models\EligibilityRule;
use App\Models\ScholarshipApplication;
use App\Models\StudentProfile;
use Carbon\Carbon;

class EligibilityService
{
    public function screen(ScholarshipApplication $application): array
    {
        $application->loadMissing(['program.eligibilityRules', 'program.requirements', 'documents', 'student.studentProfile']);
        $rules = $application->program->eligibilityRules->where('is_active', true)
            ->reject(fn ($rule) => \App\Support\RetiredAcademicFields::matches($rule->rule_key) || \App\Support\RetiredAcademicFields::matches($rule->rule_label));
        if ($rules->isEmpty()) return ['result' => 'manual_review', 'reasons' => ['No active eligibility rules configured — manual review required.']];

        $reasons = [];
        $hardFails = 0;
        $softFlags = 0;
        foreach ($rules as $rule) {
            // Verification is a staff action after submission. Do not reject a draft
            // because a required file is uploaded but has not yet been reviewed.
            if ($application->status === 'draft' && in_array($rule->rule_key, ['required_docs', 'documents.complete', 'documents.verified_count'], true)) {
                $softFlags++;
                $reasons[] = $rule->rule_label.': awaiting staff document verification.';
                continue;
            }
            $check = $this->checkRule($rule, $application, $application->student?->studentProfile);
            $reasons[] = ($check['pass'] ? '✓ ' : '✗ ') . $rule->rule_label . ' — ' . $check['detail'] . '.';
            if (! $check['pass']) {
                if ($this->isMandatory($rule)) $hardFails++; else $softFlags++;
            }
        }

        $required = $application->program->requirements->where('is_required', true)->pluck('id')->all();
        if ($required) {
            $submitted = $application->documents->pluck('scholarship_requirement_id')->filter()->all();
            $verified = $application->documents->where('verification_status', 'verified')->pluck('scholarship_requirement_id')->filter()->all();
            $missing = array_diff($required, $submitted);
            if ($missing) { $reasons[] = '✗ Missing ' . count($missing) . ' required document(s) — application cannot be fully eligible yet.'; $softFlags++; }
            elseif (array_diff($required, $verified)) { $reasons[] = '• Required documents are submitted but still awaiting verification — manual review recommended.'; $softFlags++; }
            else $reasons[] = '✓ All required documents submitted and verified.';
        }

        $result = $hardFails ? 'not_eligible' : ($softFlags ? 'manual_review' : 'eligible');
        $reasons[] = match ($result) {
            'not_eligible' => 'Overall: NOT ELIGIBLE — fails one or more mandatory criteria (final decision still requires authorized personnel).',
            'manual_review' => 'Overall: FOR MANUAL REVIEW — partially meets criteria or needs human verification.',
            default => 'Overall: ELIGIBLE — meets all configured criteria (final decision still requires authorized personnel).',
        };
        return ['result' => $result, 'reasons' => $reasons];
    }

    private function isMandatory(EligibilityRule $rule): bool
    {
        return $rule->failure_mode === 'mandatory' || $rule->rule_key === 'enrollment_status';
    }

    private function checkRule(EligibilityRule $rule, ScholarshipApplication $app, ?StudentProfile $profile): array
    {
        $key = strtolower(trim($rule->rule_key));
        $operator = strtolower(trim((string) ($rule->operator ?: '=')));
        $type = strtolower(trim((string) ($rule->rule_type ?: 'text')));
        if ($key === 'required_docs') { $key = 'documents.complete'; $type = 'boolean'; }
        [$exists, $actual] = $this->resolve($key, $app, $profile);
        if (in_array($operator, ['exists', 'present'], true)) return ['pass' => $exists && ! $this->missing($actual), 'detail' => $exists && ! $this->missing($actual) ? 'value is provided' : 'value is not provided'];
        if (in_array($operator, ['not_exists', 'absent'], true)) return ['pass' => ! $exists || $this->missing($actual), 'detail' => ! $exists || $this->missing($actual) ? 'value is absent' : 'value is provided'];
        if (! $exists || $this->missing($actual)) return ['pass' => false, 'detail' => 'value is not provided'];

        if (in_array($key, ['course', 'enrollment_status', 'year_level'], true) && in_array($operator, ['=', '==', 'in'], true)) {
            $values = $this->list($rule->rule_value);
            if ($key === 'course') {
                $pass = $values === [] || in_array('all', array_map('strtolower', $values), true) || collect($values)->contains(fn ($v) => str_contains(strtolower((string) $actual), strtolower($v)));
            } else {
                $pass = $values === [] || in_array('all', array_map('strtolower', $values), true) || in_array(strtolower((string) $actual), array_map('strtolower', $values), true);
            }
            return ['pass' => $pass, 'detail' => $pass ? "'{$actual}' is allowed" : "'{$actual}' is not allowed"];
        }

        $pass = $this->compare($actual, trim((string) $rule->rule_value), $operator, $type);
        return ['pass' => $pass !== null && $pass, 'detail' => $pass === null ? 'invalid rule value or operator' : ($pass ? "{$rule->operator} {$rule->rule_value} matched" : "actual '{$actual}' does not satisfy {$rule->operator} {$rule->rule_value}")];
    }

    private function resolve(string $key, ScholarshipApplication $app, ?StudentProfile $profile): array
    {
        if (str_starts_with($key, 'adfc_form.')) return $this->read($app->adfc_form ?? [], substr($key, 10));
        if ($app->form_version === AdfcApplicationForm::VERSION) {
            $field = str_starts_with($key, 'profile.') ? substr($key, 8) : $key;
            $field = ['student_id_no' => 'school_id_number', 'contact_number' => 'mobile_number'][$field] ?? $field;
            if (array_key_exists($field, $app->adfc_form ?? [])) return $this->read($app->adfc_form, $field);
        }
        $aliases = ['enrollment_status' => ['application', 'enrollment_status'], 'course' => ['profile', 'course'], 'year_level' => ['profile', 'year_level'], 'monthly_household_income' => ['application', 'monthly_household_income'], 'household_size' => ['application', 'household_size'], 'documents.complete' => ['documents', 'complete'], 'documents.required_count' => ['documents', 'required_count'], 'documents.submitted_count' => ['documents', 'submitted_count'], 'documents.verified_count' => ['documents', 'verified_count']];
        if (isset($aliases[$key])) {
            [$source, $field] = $aliases[$key];
            if ($source === 'documents') return [true, $this->documentMetric($app, $field)];
            $model = $source === 'profile' ? $profile : $app;
            [$exists, $value] = $this->read($model?->toArray() ?? [], $field);
            if ($exists && ! $this->missing($value)) return [$exists, $value];
            if ($source === 'application') return $this->read($profile?->toArray() ?? [], $field);
            return [$exists, $value];
        }
        if (str_starts_with($key, 'form_data.')) return $this->read($app->form_data ?? [], substr($key, 10));
        if (str_starts_with($key, 'application.')) return $this->read($app->toArray(), substr($key, 12));
        if (str_starts_with($key, 'profile.')) return $this->read($profile?->toArray() ?? [], substr($key, 8));
        foreach ([$app->toArray(), $profile?->toArray() ?? [], $app->form_data ?? []] as $source) { [$ok, $value] = $this->read($source, $key); if ($ok) return [$ok, $value]; }
        return [false, null];
    }

    private function documentMetric(ScholarshipApplication $app, string $metric): int|bool
    {
        $required = $app->program->requirements->where('is_required', true)->pluck('id')->all();
        $submitted = $app->documents->pluck('scholarship_requirement_id')->filter()->unique()->all();
        $verified = $app->documents->where('verification_status', 'verified')->pluck('scholarship_requirement_id')->filter()->unique()->all();
        return match ($metric) { 'required_count' => count($required), 'submitted_count' => count(array_intersect($required, $submitted)), 'verified_count' => count(array_intersect($required, $verified)), 'complete' => count(array_diff($required, $verified)) === 0, default => false };
    }

    private function read(array $source, string $path): array
    {
        $value = $source;
        foreach (explode('.', $path) as $segment) { if (! is_array($value) || ! array_key_exists($segment, $value)) return [false, null]; $value = $value[$segment]; }
        return [true, $value];
    }

    private function missing(mixed $value): bool { return $value === null || (is_string($value) && trim($value) === ''); }

    private function compare(mixed $actual, string $expected, string $operator, string $type): ?bool
    {
        $operator = match ($operator) { '==' => '=', '<>' => '!=', 'gte' => '>=', 'lte' => '<=', default => $operator };
        if (in_array($operator, ['is_true', 'is_false'], true)) {
            $actualBoolean = $this->cast($actual, 'boolean');
            if ($actualBoolean === null) return null;
            return $operator === 'is_true' ? $actualBoolean : ! $actualBoolean;
        }
        if ($operator === 'between') { $v = $this->list($expected); if (count($v) !== 2) return null; $a = $this->cast($actual, $type); $l = $this->cast($v[0], $type); $u = $this->cast($v[1], $type); return $a !== null && $l !== null && $u !== null && $a >= $l && $a <= $u; }
        if (in_array($operator, ['in', 'not_in'], true)) {
            $allowed = collect($this->list($expected))->map(fn ($value) => $this->cast($value, $type));
            $actualValues = is_array($actual) ? $actual : [$actual];
            $found = collect($actualValues)->map(fn ($value) => $this->cast($value, $type))->contains(fn ($value) => $value !== null && $allowed->contains($value));
            return $operator === 'in' ? $found : ! $found;
        }
        if (in_array($operator, ['contains', 'not_contains', 'starts_with', 'ends_with'], true)) {
            $e = strtolower($expected);
            if (is_array($actual)) $found = collect($actual)->contains(fn ($value) => strcasecmp((string) $value, $expected) === 0);
            else {
                $a = strtolower((string) $actual);
                $found = match ($operator) { 'contains', 'not_contains' => str_contains($a, $e), 'starts_with' => str_starts_with($a, $e), 'ends_with' => str_ends_with($a, $e) };
            }
            return $operator === 'not_contains' ? ! $found : $found;
        }
        $a = $this->cast($actual, $type); $e = $this->cast($expected, $type); if ($a === null || $e === null) return null;
        return match ($operator) { '=' => $a === $e, '!=' => $a !== $e, '>' => $a > $e, '>=' => $a >= $e, '<' => $a < $e, '<=' => $a <= $e, default => null };
    }

    private function cast(mixed $value, string $type): mixed
    {
        if ($type === 'number') return is_numeric($value) ? (float) $value : null;
        if ($type === 'boolean') return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'complete', 'verified'], true) ? true : (in_array(strtolower((string) $value), ['0', 'false', 'no', 'incomplete', 'pending'], true) ? false : null);
        if ($type === 'date') { try { return Carbon::parse((string) $value)->startOfDay()->timestamp; } catch (\Throwable) { return null; } }
        return strtolower(trim((string) $value));
    }

    private function list(string $value): array { $json = json_decode($value, true); $items = is_array($json) ? $json : explode(',', $value); return array_values(array_filter(array_map(fn ($v) => trim((string) $v), $items), fn ($v) => $v !== '')); }

    public function runAndStore(ScholarshipApplication $application, ?int $checkedBy = null): EligibilityResult
    {
        $out = $this->screen($application);
        return EligibilityResult::create(['scholarship_application_id' => $application->id, 'checked_by' => $checkedBy, 'result' => $out['result'], 'reasons' => implode("\n", $out['reasons']), 'checked_at' => now()]);
    }
}
