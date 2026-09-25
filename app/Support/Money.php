<?php

namespace App\Support;

use App\Services\CurrencyService;

/**
 * What @money resolves to.
 *
 * It exists because a template is where a nullable database column meets a
 * formatter that takes a float. Several of the money columns here are
 * nullable — a purchase order can be saved before anything has been costed —
 * and the templates used to print those through number_format(), which reads
 * null as zero without a word. Doing the same thing on purpose, in one place,
 * is better than widening CurrencyService::formatAmount() until it accepts
 * anything.
 */
class Money
{
    public static function format(float|int|string|null $amount, string $currency = 'PLN'): string
    {
        return app(CurrencyService::class)->formatAmount((float) $amount, $currency);
    }
}
