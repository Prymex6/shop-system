<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects URLs that resolve to a private, loopback, or link-local address —
 * blocks staff (or an attacker with a compromised staff account) from
 * pointing an outbound tenant webhook at internal infrastructure (SSRF),
 * e.g. http://127.0.0.1, http://169.254.169.254 (cloud metadata), http://10.x.x.x.
 */
class PublicHttpUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_URL)) {
            $fail('Pole :attribute musi być prawidłowym adresem URL.');

            return;
        }

        $parts = parse_url($value);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';

        if (!in_array($scheme, ['http', 'https'], true)) {
            $fail('Adres URL musi używać protokołu http lub https.');

            return;
        }

        if (self::isBlockedHost($host)) {
            $fail('Ten adres URL wskazuje na niedozwolony (wewnętrzny) adres sieciowy.');
        }
    }

    public static function isBlockedHost(string $host): bool
    {
        if ($host === '' || strtolower($host) === 'localhost') {
            return true;
        }

        // Resolve hostnames to IPs so a public-looking domain can't be used to
        // rebind to a private address at request time (DNS rebinding).
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);

        if (empty($ips)) {
            // Couldn't resolve at all — safest to reject rather than let it through.
            return true;
        }

        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return true;
            }
        }

        return false;
    }
}
