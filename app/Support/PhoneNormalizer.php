<?php

namespace App\Support;

final class PhoneNormalizer
{
    public static function ghana(?string $phone): ?string
    {
        if (! filled($phone)) {
            return null;
        }

        $value = preg_replace('/[^0-9+]/', '', $phone);
        if ($value === null || $value === '') {
            return null;
        }

        if (str_starts_with($value, '+233') && strlen($value) === 13) {
            return $value;
        }

        if (str_starts_with($value, '233') && strlen($value) === 12) {
            return '+'.$value;
        }

        if (str_starts_with($value, '0') && strlen($value) === 10) {
            return '+233'.substr($value, 1);
        }

        return $value;
    }
}
