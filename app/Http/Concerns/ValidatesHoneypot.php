<?php

namespace App\Http\Concerns;

use Illuminate\Http\Request;

/**
 * Cheap bot deterrent for anonymous, no-login forms (contact, newsletter,
 * guest chat start): a hidden field real visitors never see or fill, but
 * that naive form-filling bots submit. On a hit we pretend success rather
 * than returning a validation error, so the bot doesn't learn to avoid the
 * field. Not a replacement for a real CAPTCHA against a targeted attacker —
 * throttle:... middleware already covers that case — this only cuts down
 * on unsophisticated mass-submission bots.
 */
trait ValidatesHoneypot
{
    protected function isHoneypotFilled(Request $request, string $field = 'website'): bool
    {
        return filled($request->input($field));
    }
}
