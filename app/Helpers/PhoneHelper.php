<?php

namespace App\Helpers;

class PhoneHelper
{
    /**
     * Normalize a phone number to bare 9 Polish digits (e.g. "123456789").
     * Strips spaces, dashes, parentheses, +48 / 0048 / leading 0.
     * Returns null when input is empty or shorter than 9 digits.
     */
    public static function normalize(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Keep only digits and the leading plus sign
        $clean = preg_replace('/[^\d+]/', '', $phone);

        // Strip known Polish prefixes
        if (str_starts_with($clean, '+48')) {
            $clean = substr($clean, 3);
        } elseif (str_starts_with($clean, '0048')) {
            $clean = substr($clean, 4);
        } elseif (str_starts_with($clean, '+')) {
            // Non-Polish international number — strip the + and return as-is
            $clean = substr($clean, 1);

            return $clean ?: null;
        }

        // Strip a leading 0 left from old-style Polish dialling (0 123 456 789)
        if (str_starts_with($clean, '0') && strlen($clean) === 10) {
            $clean = substr($clean, 1);
        }

        return strlen($clean) >= 7 ? $clean : null;
    }

    /**
     * Format stored digits for display: "123456789" → "123 456 789".
     * Non-9-digit values are returned as-is.
     */
    public static function format(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 9) {
            return substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6);
        }

        return $phone;
    }
}
