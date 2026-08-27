<?php

namespace Tests\Feature;

use App\Models\Tenant\Product;
use App\Services\BackupService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TenantTestCase;
use ZipArchive;

/**
 * BackupService::create() previously only ever dumped DB rows as JSON —
 * uploaded files (product images, digital product files, review images)
 * were never included, so restoring a "backup" would bring back an order
 * history pointing at files that no longer existed anywhere. restore()
 * itself had zero test/route/command coverage at all (100% dead code) and
 * would have crashed on FK constraints (TRUNCATE with checks on) if it
 * were ever actually invoked.
 */
class BackupServiceTest extends TenantTestCase
{
    public function test_backup_includes_public_files_not_just_database_rows(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('products/photo.jpg', 'fake-image-bytes');

        $result = app(BackupService::class)->create('full');

        $this->assertStringEndsWith('.zip', $result['filename']);

        $zipPath = tempnam(sys_get_temp_dir(), 'test_backup_');
        file_put_contents($zipPath, Storage::disk('local')->get('private/' . $result['path']));

        $zip = new ZipArchive;
        $zip->open($zipPath);

        $this->assertNotFalse($zip->locateName('data.json'));
        $this->assertNotFalse($zip->locateName('files/products/photo.jpg'));
        $this->assertEquals('fake-image-bytes', $zip->getFromName('files/products/photo.jpg'));

        $zip->close();
        @unlink($zipPath);
    }

    public function test_backup_and_restore_round_trips_database_rows(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Original', 'slug' => 'original', 'price' => 10,
            'type' => 'physical', 'status' => 'active',
        ]);

        $backup = app(BackupService::class)->create('full');

        // Simulate data loss / a bad change after the backup was taken.
        $product->update(['name' => 'Corrupted']);
        Product::create([
            'name' => 'Should not survive restore', 'slug' => 'extra',
            'price' => 1, 'type' => 'physical', 'status' => 'active',
        ]);

        app(BackupService::class)->restore($backup['filename']);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Original']);
        $this->assertDatabaseMissing('products', ['slug' => 'extra']);
    }

    public function test_backup_and_restore_round_trips_public_files(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('products/keep.jpg', 'original-bytes');

        $backup = app(BackupService::class)->create('full');

        // Simulate the file being lost/corrupted after the backup.
        Storage::disk('public')->delete('products/keep.jpg');
        $this->assertFalse(Storage::disk('public')->exists('products/keep.jpg'));

        app(BackupService::class)->restore($backup['filename']);

        $this->assertTrue(Storage::disk('public')->exists('products/keep.jpg'));
        $this->assertEquals('original-bytes', Storage::disk('public')->get('products/keep.jpg'));
    }

    public function test_old_backups_beyond_retention_count_are_pruned(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        // Filenames are second-precision — travel the clock forward each
        // iteration instead of sleeping, so 16 backups get 16 distinct names.
        $start = now();
        for ($i = 0; $i < 16; $i++) {
            $this->travelTo($start->copy()->addSeconds($i));
            app(BackupService::class)->create('full');
        }
        $this->travelBack();

        $remaining = app(BackupService::class)->list();

        $this->assertLessThanOrEqual(14, count($remaining));
    }

    public function test_sanitize_rejects_path_traversal_in_filename(): void
    {
        Storage::fake('local');

        $this->expectException(NotFoundHttpException::class);
        app(BackupService::class)->restore('../../../etc/passwd');
    }

    public function test_sanitize_accepts_new_zip_and_legacy_gz_formats(): void
    {
        Storage::fake('local');

        $reflection = new \ReflectionMethod(BackupService::class, 'sanitizeFilename');
        $reflection->setAccessible(true);
        $service = app(BackupService::class);

        $this->assertSame(
            'backup_2026-08-11_12-00-00_full.zip',
            $reflection->invoke($service, 'backup_2026-08-11_12-00-00_full.zip')
        );
        $this->assertSame(
            'backup_2026-08-11_12-00-00_full.json.gz',
            $reflection->invoke($service, 'backup_2026-08-11_12-00-00_full.json.gz')
        );
    }
}
