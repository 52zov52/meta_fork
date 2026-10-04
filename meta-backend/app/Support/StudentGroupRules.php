<?php

namespace App\Support;

/**
 * Правила для поля «группа»: без латиницы, чтобы совпадали маски академий и отчёты.
 */
final class StudentGroupRules
{
    public static function rules(): array
    {
        return [
            'nullable',
            'string',
            'max:10',
            'regex:/^[\p{Cyrillic}0-9\s().,\-]+$/u',
        ];
    }

    public static function invalidMessage(): string
    {
        return 'Группа: только кириллица, цифры и символы ( ) . , - пробел; латиница не допускается.';
    }

    public static function isValid(?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (mb_strlen($value) > 10) {
            return false;
        }

        return (bool) preg_match('/^[\p{Cyrillic}0-9\s().,\-]+$/u', $value);
    }
}
