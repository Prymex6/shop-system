<?php

namespace App\Services;

use App\Models\Landlord\TenantModification;
use Illuminate\Support\Facades\Log;

class TenantModificationService
{
    /**
     * Apply all active modifications for a given tenant.
     * Generates patched files in storage/modifications/{tenantId}/
     */
    public function applyForTenant(string $tenantId): array
    {
        $results = [];

        $modifications = TenantModification::on('central')
            ->where('status', true)
            ->get()
            ->filter(fn ($mod) => $mod->appliesToTenant($tenantId));

        foreach ($modifications as $modification) {
            try {
                $applied = $this->applyModification($modification, $tenantId);
                $results[] = [
                    'modification' => $modification->name,
                    'code' => $modification->code,
                    'files' => $applied,
                    'status' => 'ok',
                ];
            } catch (\Throwable $e) {
                Log::error("Mod error [{$modification->code}] for tenant [{$tenantId}]: " . $e->getMessage());
                $results[] = [
                    'modification' => $modification->name,
                    'code' => $modification->code,
                    'files' => [],
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Apply a single modification for a tenant.
     * Returns list of files that were patched.
     */
    protected function applyModification(TenantModification $modification, string $tenantId): array
    {
        $patchedFiles = [];
        $basePath = realpath(base_path());

        foreach ($modification->getPatches() as $patch) {
            $relativeFile = $patch['file'] ?? '';

            // Prevent directory traversal
            if (
                str_contains($relativeFile, '..') ||
                str_starts_with($relativeFile, '/') ||
                str_starts_with($relativeFile, '\\')
            ) {
                throw new \RuntimeException("Invalid file path: {$relativeFile}");
            }

            $filePath = base_path($relativeFile);

            if (!file_exists($filePath)) {
                throw new \RuntimeException("File not found: {$relativeFile}");
            }

            // Verify resolved path stays within the application root
            $realFilePath = realpath($filePath);
            if ($realFilePath === false || !str_starts_with($realFilePath, $basePath)) {
                throw new \RuntimeException("File path traversal detected: {$relativeFile}");
            }

            $content = file_get_contents($filePath);

            foreach ($patch['operations'] ?? [] as $op) {
                $search = $op['search'] ?? '';
                $add = $op['add'] ?? '';
                $position = $op['position'] ?? 'replace';
                $regex = $op['regex'] ?? false;

                if (empty($search)) {
                    continue;
                }

                if ($regex) {
                    $content = match ($position) {
                        'replace' => preg_replace($search, $add, $content),
                        'before' => preg_replace($search, $add . '$0', $content),
                        'after' => preg_replace($search, '$0' . $add, $content),
                        default => $content,
                    };
                } else {
                    $content = match ($position) {
                        'replace' => str_replace($search, $add, $content),
                        'before' => str_replace($search, $add . $search, $content),
                        'after' => str_replace($search, $search . $add, $content),
                        default => $content,
                    };
                }
            }

            // Save to tenant-specific modification cache
            $outputPath = $this->getModifiedFilePath($tenantId, $patch['file']);
            $dir = dirname($outputPath);

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($outputPath, $content);
            $patchedFiles[] = $patch['file'];
        }

        return $patchedFiles;
    }

    /**
     * Remove all applied modifications for a tenant.
     */
    public function clearForTenant(string $tenantId): void
    {
        if (str_contains($tenantId, '/') || str_contains($tenantId, '\\') || str_contains($tenantId, '..')) {
            throw new \InvalidArgumentException("Invalid tenant ID: {$tenantId}");
        }

        $dir = storage_path("modifications/{$tenantId}");

        if (is_dir($dir)) {
            $this->deleteDirectory($dir);
        }
    }

    /**
     * Remove all modifications cache.
     */
    public function clearAll(): void
    {
        $dir = storage_path('modifications');

        if (is_dir($dir)) {
            $this->deleteDirectory($dir);
        }
    }

    /**
     * Get the path where a modified file is stored for a tenant.
     */
    public function getModifiedFilePath(string $tenantId, string $relativeFilePath): string
    {
        // Validate tenant ID contains no path separators
        if (str_contains($tenantId, '/') || str_contains($tenantId, '\\') || str_contains($tenantId, '..')) {
            throw new \InvalidArgumentException("Invalid tenant ID: {$tenantId}");
        }

        return storage_path("modifications/{$tenantId}/{$relativeFilePath}");
    }

    /**
     * Check if a modified version of a PHP class exists for the current tenant.
     */
    public function resolveClass(string $class): ?string
    {
        try {
            $tenantId = tenant('id');
        } catch (\Throwable) {
            return null;
        }

        if (!$tenantId) {
            return null;
        }

        // Convert App\Http\... to app/Http/...
        $relativePath = lcfirst(str_replace('\\', '/', $class)) . '.php';
        $modifiedPath = storage_path("modifications/{$tenantId}/{$relativePath}");

        return file_exists($modifiedPath) ? $modifiedPath : null;
    }

    /**
     * Get summary of applied modifications for a tenant.
     */
    public function getAppliedSummary(string $tenantId): array
    {
        $modDir = storage_path("modifications/{$tenantId}");

        if (!is_dir($modDir)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($modDir));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = str_replace($modDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        return $files;
    }

    private function deleteDirectory(string $dir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }
}
