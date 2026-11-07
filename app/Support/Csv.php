<?php

namespace App\Support;

class Csv
{
    /** Prevent spreadsheet formulas in exported, user-controlled cells. */
    public static function write($stream, array $cells): void
    {
        fputcsv($stream, array_map(function ($value) {
            $value = (string) ($value ?? '');
            return preg_match('/^[\s]*[=+@\-]/u', $value) ? "'".$value : $value;
        }, $cells), ',', '"', '');
    }
}
