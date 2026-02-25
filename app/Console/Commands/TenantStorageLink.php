<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Services\TenantStorageService;
use Illuminate\Console\Command;

class TenantStorageLink extends Command
{
    protected $signature = 'tenant:storage-link';

    protected $description = 'Recreate public/tenants/{id} symlinks/junctions for all tenants';

    public function handle(TenantStorageService $storage): int
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->info('No tenants found.');

            return 0;
        }

        foreach ($tenants as $tenant) {
            $link = public_path("tenants/{$tenant->id}");
            $target = $storage->mediaPath($tenant->id);

            if (!is_dir($target)) {
                mkdir($target, 0755, true);
            }

            if (file_exists($link) || is_link($link)) {
                $this->line("  <comment>skip</comment>  {$tenant->id} (already exists)");

                continue;
            }

            $linkParent = dirname($link);
            if (!is_dir($linkParent)) {
                mkdir($linkParent, 0755, true);
            }

            if (PHP_OS_FAMILY === 'Windows') {
                exec('mklink /J "' . str_replace('/', '\\', $link) . '" "' . str_replace('/', '\\', $target) . '"', $out, $code);
                $ok = $code === 0;
            } else {
                $ok = symlink($target, $link);
            }

            if ($ok) {
                $this->line("  <info>linked</info> {$tenant->id}");
            } else {
                $this->line("  <error>failed</error> {$tenant->id}");
            }
        }

        $this->info('Done.');

        return 0;
    }
}
