<?php

namespace App\Mail\Concerns;

use App\Models\Tenant\Setting;

/**
 * Sends the message in the language the shop runs in.
 *
 * Without this, a queued mail renders in whatever locale the worker happens
 * to be in, which is the application default and has nothing to do with the
 * shop the order was placed in. Worse, a message built while a manager has
 * the panel in English would reach a customer in English even though every
 * other word that customer has read was Polish.
 *
 * Mailable::locale() is the piece that survives the queue: the locale is
 * serialised with the job and restored around rendering.
 */
trait SendsInShopLanguage
{
    public function inShopLanguage(): static
    {
        return $this->locale(Setting::get('shop_language') ?: config('app.locale'));
    }
}
