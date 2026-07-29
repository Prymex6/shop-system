<?php

namespace App\Support;

class Csv
{
    /**
     * Neutralize spreadsheet formula injection: a cell whose value starts with
     * =, +, -, @, tab, or CR will execute as a formula when the exported CSV
     * is opened in Excel/Sheets — a classic vector for stealing data from
     * whoever on staff opens the export. Prefixing with a bare quote disables
     * formula evaluation while leaving the visible text unchanged.
     */
    public static function safe(mixed $value): string
    {
        $value = (string) $value;

        if (preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * safe() wrapped in double-quote CSV-field escaping.
     */
    public static function field(mixed $value): string
    {
        return '"' . str_replace('"', '""', self::safe($value)) . '"';
    }
}
