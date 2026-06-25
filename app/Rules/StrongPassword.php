<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * StrongPassword Validation Rule
 *
 * Tutarlı şifre karmaşıklığı politikası:
 *  - Minimum 10 karakter
 *  - Maksimum 32 karakter
 *  - En az 1 büyük harf (A-Z)
 *  - En az 1 küçük harf (a-z)
 *  - En az 1 rakam (0-9)
 *  - En az 1 özel karakter (harf/rakam dışında)
 *
 * Bu kurallar auth/settings/profile şifre değiştirme rotalarında kullanılır.
 */
class StrongPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            ! is_string($value) ||
            ! preg_match(
                '/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[^A-Za-z0-9]).{10,32}$/',
                $value
            )
        ) {
            $fail('Şifreniz en az 10 karakter uzunluğunda olmalı ve en az 1 sayı, özel karakter ve büyük harf içermelidir.');
        }
    }
}