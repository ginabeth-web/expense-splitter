<?php

namespace App\Support;

class Money
{
    /**
     * Parse a user-typed amount like "1,234.5" into cents. Returns null for
     * anything that isn't a positive amount with at most two decimals and at
     * most 12 whole digits. Commas are only accepted as thousands separators.
     */
    public static function toCents(string $input): ?int
    {
        $value = trim($input);

        if (! preg_match('/^(\d{1,3}(?:,\d{3})+|\d+)(?:\.(\d{1,2}))?$/', $value, $matches)) {
            return null;
        }

        $whole = str_replace(',', '', $matches[1]);
        if (strlen(ltrim($whole, '0')) > 12) {
            return null;
        }

        $cents = (int) $whole * 100 + (int) str_pad($matches[2] ?? '0', 2, '0');

        return $cents > 0 ? $cents : null;
    }

    public static function format(int $cents, string $currency): string
    {
        $sign = $cents < 0 ? '-' : '';

        return $currency.' '.$sign.number_format(abs($cents) / 100, 2);
    }

    /**
     * Cents as a plain form value, e.g. 123456 → "1234.56".
     */
    public static function toInput(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
