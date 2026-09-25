<?php

namespace App\Tenancy\Bootstrappers;

use App\Models\Tenant\Setting;
use Illuminate\Support\Facades\Config;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class TenantMailBootstrapper implements TenancyBootstrapper
{
    protected array $originalConfig = [];

    public function bootstrap(Tenant $tenant): void
    {
        try {
            $email = Setting::get('shop_email');
            $name = Setting::get('shop_name');
        } catch (\Throwable $e) {
            // tenant_settings does not exist yet, which happens during the first migration
            return;
        }

        $this->originalConfig = [
            'mail.from.address' => Config::get('mail.from.address'),
            'mail.from.name' => Config::get('mail.from.name'),
        ];

        // Auto-derive from address from tenant domain if not set manually
        if (!$email) {
            $domain = $tenant->domains()->first()?->domain ?? null;
            if ($domain) {
                // Strip port and any subdomain-only parts to get root domain
                $host = preg_replace('/:\d+$/', '', $domain); // remove port
                $parts = explode('.', $host);
                // Use last 2 parts as root domain (e.g. pizza.pl from test.pizza.pl)
                $rootDomain = count($parts) >= 2
                    ? implode('.', array_slice($parts, -2))
                    : $host;
                $email = 'noreply@' . $rootDomain;
            }
        }

        Config::set([
            'mail.from.address' => $email ?: Config::get('mail.from.address'),
            'mail.from.name' => $name ?: Config::get('mail.from.name'),
        ]);
    }

    public function revert(): void
    {
        if (empty($this->originalConfig)) {
            return;
        }

        Config::set($this->originalConfig);
        $this->originalConfig = [];
    }
}
