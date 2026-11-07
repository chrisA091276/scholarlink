<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

final class PasswordRules
{
    public const HELP = 'Use at least 8 characters, with uppercase and lowercase letters, a number, and a symbol.';

    public static function rules(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'max:72',
            Password::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'];
    }
}
