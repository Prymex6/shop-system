<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class TenantStorageService
{
    /**
     * Create the full folder structure for a new tenant and symlink media to public/.
     */
    public function createTenantFolders(string $tenantId): void
    {
        $dirs = [
            $this->mediaPath($tenantId, 'settings'),
            $this->mediaPath($tenantId, 'products'),
            $this->mediaPath($tenantId, 'gallery'),
            $this->backupsPath($tenantId),
            $this->logsPath($tenantId),
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        $this->ensurePublicSymlink($tenantId);
    }

    /**
     * Ensure public/tenants/{tenantId} → storage/tenants/{tenantId}/media exists.
     * Nothing in the tenant-creation flow currently calls createTenantFolders()
     * automatically, so every write path that hands out a public URL for a
     * tenant media file calls this defensively — otherwise the file saves fine
     * but its URL 404s because the symlink was never created for that tenant.
     */
    public function ensurePublicSymlink(string $tenantId): void
    {
        $link = public_path("tenants/{$tenantId}");
        $target = $this->mediaPath($tenantId);

        if (file_exists($link) || is_link($link)) {
            return;
        }

        $linkParent = dirname($link);
        if (!is_dir($linkParent)) {
            mkdir($linkParent, 0755, true);
        }
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }

        // On Windows symlinks require elevated privileges; fall back to junction (still works in PHP)
        if (PHP_OS_FAMILY === 'Windows') {
            // Use junction (directory hard link) – works without admin on NTFS
            exec('mklink /J "' . str_replace('/', '\\', $link) . '" "' . str_replace('/', '\\', $target) . '"');
        } else {
            symlink($target, $link);
        }
    }

    // ── Path helpers ──────────────────────────────────────────────────────────

    public function mediaPath(string $tenantId, ?string $subfolder = null): string
    {
        $base = base_path("storage/tenants/{$tenantId}/media");

        return $subfolder ? "{$base}/{$subfolder}" : $base;
    }

    public function backupsPath(string $tenantId, ?string $yearMonth = null): string
    {
        $base = base_path("storage/tenants/{$tenantId}/backups");

        return $yearMonth ? "{$base}/{$yearMonth}" : $base;
    }

    public function logsPath(string $tenantId): string
    {
        return base_path("storage/tenants/{$tenantId}/logs");
    }

    /**
     * Resolve the public URL for a tenant media file.
     */
    public function publicUrl(string $tenantId, string $subfolder, string $filename): string
    {
        return "/tenants/{$tenantId}/{$subfolder}/{$filename}";
    }

    /**
     * Get the absolute path where an uploaded file should be saved.
     */
    public function uploadDestination(string $tenantId, string $subfolder, string $filename): string
    {
        $dir = $this->mediaPath($tenantId, $subfolder);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->ensurePublicSymlink($tenantId);

        return "{$dir}/{$filename}";
    }
}
