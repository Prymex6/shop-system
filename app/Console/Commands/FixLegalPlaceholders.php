<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Models\Tenant\Setting;
use Illuminate\Console\Command;

class FixLegalPlaceholders extends Command
{
    protected $signature = 'tenant:fix-legal-placeholders';

    protected $description = 'Replace a hard-coded shop name with {shop_name} in the terms and the privacy policy';

    public function handle(): int
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            foreach (['terms_content', 'privacy_content'] as $key) {
                $content = Setting::get($key, '');

                if (!$content) {
                    continue;
                }

                if (str_contains($content, '{shop_name}')) {
                    $this->line("  [{$tenant->id}] {$key} - already has the placeholder, skipping");

                    continue;
                }

                // Swap a literal "Nazwa handlowa: anything" for the placeholder
                $fixed = preg_replace(
                    '/Nazwa handlowa: [^<\n]+/',
                    'Nazwa handlowa: {shop_name}',
                    $content
                );

                if ($fixed !== $content) {
                    Setting::set($key, $fixed, 'text');
                    $this->info("  [{$tenant->id}] {$key} — naprawiono");
                } else {
                    $this->warn("  [{$tenant->id}] {$key} — nie znaleziono 'Nazwa handlowa:', pomijam");
                }
            }

            tenancy()->end();
        }

        $this->info('Gotowe.');

        return self::SUCCESS;
    }
}
