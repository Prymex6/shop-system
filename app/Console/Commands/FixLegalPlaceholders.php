<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Models\Tenant\Setting;
use Illuminate\Console\Command;

class FixLegalPlaceholders extends Command
{
    protected $signature = 'tenant:fix-legal-placeholders';

    protected $description = 'Zamień hardkodowaną nazwę sklepu na {shop_name} w regulaminie i polityce prywatności';

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
                    $this->line("  [{$tenant->id}] {$key} — już ma placeholder, pomijam");

                    continue;
                }

                // Zamień "Nazwa handlowa: cokolwiek" na placeholder
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
