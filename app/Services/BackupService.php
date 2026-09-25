<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class BackupService
{
    /**
     * How many backups to keep per tenant — create() prunes older ones past
     * this count. Previously nothing ever pruned backups at all.
     */
    private const RETENTION_COUNT = 14;

    private function basePath(): string
    {
        $tenantId = tenancy()->tenant?->id ?? 'unknown';

        return "backups/{$tenantId}";
    }

    /**
     * Builds a .zip containing data.json (the same full DB dump as before)
     * plus a files/ directory mirroring the tenant's public disk (product
     * images, digital product files, review images, CSV imports) —
     * previously a "backup" only ever captured DB rows, so restoring one
     * would bring back an order history pointing at product images and
     * digital downloads that no longer existed anywhere.
     */
    public function create(string $type = 'full'): array
    {
        $tenantId = tenancy()->tenant?->id ?? 'unknown';
        $date = now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$date}_{$type}.zip";
        $path = $this->basePath() . '/' . $filename;

        $data = $this->dumpDatabase($type);
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        $tmpZipPath = tempnam(sys_get_temp_dir(), 'backup_');
        $zip = new ZipArchive;
        $zip->open($tmpZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('data.json', $json);
        $this->addDirectoryToZip($zip, Storage::disk('public')->path(''), 'files');
        $zip->close();

        Storage::disk('local')->makeDirectory($this->basePath());
        Storage::disk('local')->put("private/{$path}", file_get_contents($tmpZipPath));
        @unlink($tmpZipPath);

        $size = Storage::disk('local')->size("private/{$path}");

        $this->pruneOldBackups();

        return [
            'filename' => $filename,
            'path' => $path,
            'size' => $size,
            'created_at' => now()->toIso8601String(),
            'type' => $type,
        ];
    }

    private function dumpDatabase(string $type): array
    {
        $tenantId = tenancy()->tenant?->id ?? 'unknown';
        // Schema::getTables() (driver-agnostic) instead of a raw "SHOW TABLES"
        // query — the latter is MySQL-only syntax, which meant this method
        // could never run against the SQLite connection the test suite uses,
        // leaving it with zero automated coverage.
        $tables = DB::getSchemaBuilder()->getTables();
        $data = ['metadata' => [
            'tenant_id' => $tenantId,
            'created_at' => now()->toIso8601String(),
            'type' => $type,
            'version' => '2.0',
        ]];

        foreach ($tables as $tableInfo) {
            $table = $tableInfo['name'];
            // Skip framework tables
            if (in_array($table, ['migrations', 'password_reset_tokens', 'failed_jobs', 'jobs', 'cache', 'sessions'])) {
                continue;
            }
            try {
                $data['tables'][$table] = DB::table($table)->get()->toArray();
            } catch (\Exception) {
                // skip
            }
        }

        return $data;
    }

    private function addDirectoryToZip(ZipArchive $zip, string $sourceDir, string $zipPrefix): void
    {
        if (!is_dir($sourceDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile()) {
                continue;
            }
            $relative = $zipPrefix . '/' . ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($sourceDir))), '/');
            $zip->addFile($file->getPathname(), $relative);
        }
    }

    /**
     * Keeps the most recent RETENTION_COUNT backups for this tenant and
     * deletes the rest — backups were previously never cleaned up at all,
     * growing storage usage without bound.
     */
    private function pruneOldBackups(): void
    {
        $backups = $this->list();
        if (count($backups) <= self::RETENTION_COUNT) {
            return;
        }

        foreach (array_slice($backups, self::RETENTION_COUNT) as $old) {
            try {
                $this->delete($old['filename']);
            } catch (\Exception $e) {
                Log::warning('Backup pruning: failed to delete ' . $old['filename'] . ': ' . $e->getMessage());
            }
        }
    }

    public function list(): array
    {
        $disk = Storage::disk('local');
        $dir = "private/{$this->basePath()}";

        if (!$disk->exists($dir)) {
            return [];
        }

        $files = $disk->files($dir);
        $result = [];

        foreach ($files as $file) {
            $name = basename($file);
            $result[] = [
                'filename' => $name,
                'size' => $disk->size($file),
                'size_human' => $this->formatBytes($disk->size($file)),
                'last_modified' => date('Y-m-d H:i:s', $disk->lastModified($file)),
            ];
        }

        // Sort newest first
        usort($result, fn ($a, $b) => strcmp($b['last_modified'], $a['last_modified']));

        return $result;
    }

    /**
     * Backup filenames are only ever generated by create() as
     * "backup_{date}_{type}.zip" (or, for backups made before files were
     * included, the legacy "backup_{date}_{type}.json.gz") — reject
     * anything else outright. This blocks path traversal
     * (../../other-tenant/...) on every public-facing method that takes a
     * filename from a route param.
     */
    private function sanitizeFilename(string $filename): string
    {
        $filename = basename($filename);

        if (!preg_match('/^backup_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}-[0-9]{2}-[0-9]{2}_[A-Za-z0-9]+\.(zip|json\.gz)$/', $filename)) {
            abort(404, 'Backup not found.');
        }

        return $filename;
    }

    /**
     * Restores both the DB (truncate + reinsert every table from the
     * backup) and, for .zip backups, the tenant's public files. FK checks
     * are disabled for the duration — TRUNCATE on a table another table
     * has a foreign key into would otherwise fail outright (this is the
     * "would likely crash on FK constraints" bug: the previous version ran
     * TRUNCATE with checks on, inside a transaction that TRUNCATE itself
     * implicitly commits around anyway since it's DDL).
     */
    public function restore(string $filename): void
    {
        $filename = $this->sanitizeFilename($filename);
        $path = "private/{$this->basePath()}/{$filename}";
        $disk = Storage::disk('local');

        if (!$disk->exists($path)) {
            throw new \RuntimeException("Backup file not found: {$filename}");
        }

        $raw = $disk->get($path);

        if (str_ends_with($filename, '.zip')) {
            $data = $this->restoreFromZip($raw);
        } else {
            $json = gzdecode($raw);
            $data = json_decode($json, true);
        }

        if (!isset($data['tables'])) {
            throw new \RuntimeException('Invalid backup format.');
        }

        // TRUNCATE is DDL on MySQL and implicitly commits/breaks any
        // surrounding transaction on its own — wrapping this in
        // DB::transaction() doesn't add real atomicity and actually throws
        // ("There is no active transaction") once MySQL silently closes the
        // transaction out from under Laravel's commit/rollback bookkeeping.
        $isSqlite = DB::getDriverName() === 'sqlite';
        DB::statement($isSqlite ? 'PRAGMA foreign_keys = OFF' : 'SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($data['tables'] as $table => $rows) {
                DB::table($table)->truncate();
                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table($table)->insert(array_map(fn ($r) => (array) $r, $chunk));
                }
            }
        } finally {
            DB::statement($isSqlite ? 'PRAGMA foreign_keys = ON' : 'SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Extracts data.json and restores files/ onto the tenant's public disk
     * from a .zip backup. Returns the decoded DB dump.
     */
    private function restoreFromZip(string $rawZip): array
    {
        $tmpZipPath = tempnam(sys_get_temp_dir(), 'restore_');
        file_put_contents($tmpZipPath, $rawZip);

        $zip = new ZipArchive;
        if ($zip->open($tmpZipPath) !== true) {
            @unlink($tmpZipPath);
            throw new \RuntimeException(__('messages.backup_archive_unreadable'));
        }

        $json = $zip->getFromName('data.json');
        $data = $json !== false ? json_decode($json, true) : null;

        $publicRoot = rtrim(Storage::disk('public')->path(''), '/');

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (!str_starts_with($entry, 'files/') || str_ends_with($entry, '/')) {
                continue;
            }

            $relative = substr($entry, strlen('files/'));
            // Same traversal guard as sanitizeFilename() — a zip entry name
            // is attacker-controllable if a backup file is ever tampered
            // with, so never trust it to build a filesystem path unchecked.
            if ($relative === '' || str_contains($relative, '..')) {
                continue;
            }

            $destination = $publicRoot . '/' . $relative;
            @mkdir(dirname($destination), 0755, true);
            $contents = $zip->getFromIndex($i);
            if ($contents !== false) {
                file_put_contents($destination, $contents);
            }
        }

        $zip->close();
        @unlink($tmpZipPath);

        return $data ?? [];
    }

    public function download(string $filename): StreamedResponse
    {
        $filename = $this->sanitizeFilename($filename);
        $path = "private/{$this->basePath()}/{$filename}";
        $disk = Storage::disk('local');

        if (!$disk->exists($path)) {
            abort(404, 'Backup not found.');
        }

        $contentType = str_ends_with($filename, '.zip') ? 'application/zip' : 'application/gzip';

        return response()->streamDownload(function () use ($disk, $path) {
            echo $disk->get($path);
        }, $filename, [
            'Content-Type' => $contentType,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function delete(string $filename): void
    {
        $filename = $this->sanitizeFilename($filename);
        $path = "private/{$this->basePath()}/{$filename}";
        Storage::disk('local')->delete($path);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
