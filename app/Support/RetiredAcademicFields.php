<?php

namespace App\Support;

class RetiredAcademicFields
{
    public static function withoutAnswers(array $answers): array
    {
        return array_filter($answers, fn ($key) => ! self::matches((string) $key), ARRAY_FILTER_USE_KEY);
    }

    public static function matches(string $value): bool
    {
        $normalized = str_replace(['_', '.', '-'], ' ', strtolower($value));

        return (bool) preg_match('/\b(gpa|gwa)\b|\b(?:general\s+)?weighted\s+average\b|\bgrade\s+point\s+average\b|\bacademic\s+average\b/', $normalized);
    }
}
