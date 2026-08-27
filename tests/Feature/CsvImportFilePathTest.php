<?php

namespace Tests\Feature;

use App\Jobs\ImportCustomersCsv;
use App\Models\Tenant\User;
use App\Services\CustomerImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TenantTestCase;

/**
 * All three CSV import jobs (customers/orders/products) computed the
 * absolute file path as storage_path('app/' . $storedFilePath) — wrong in
 * Laravel 11+, where the 'local' disk's root is storage_path('app/private'),
 * not storage_path('app/') directly. The controllers also omitted an
 * explicit disk on store(), landing the file on the app's default disk
 * (FILESYSTEM_DISK=public) instead of 'local' as the code's own comments
 * assumed. Combined, every import silently did nothing — the job logged
 * "file not found" and returned, with no error ever surfaced to the
 * manager, who just sees "import queued" and nothing else. Covers the
 * customer import end-to-end; orders/products share the identical fix.
 */
class CsvImportFilePathTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_uploaded_csv_lands_on_local_disk_not_public(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        // QUEUE_CONNECTION=sync in tests runs the import job immediately
        // inline, which — now correctly — finds and deletes the file as
        // part of successfully processing it. Fake the queue here so this
        // test can check the upload location in isolation; job execution
        // itself is covered by the next test.
        Queue::fake();

        // CustomerImportController reads tenancy()->tenant->id directly (no
        // null-safe operator) — fine in production (tenancy is always
        // initialized for tenant routes) but needs a fake binding here
        // since TenantTestCase doesn't initialize real tenancy.
        $fakeTenant = new \stdClass;
        $fakeTenant->id = 'test-tenant-id';
        tenancy()->tenant = $fakeTenant;

        $csv = UploadedFile::fake()->createWithContent('customers.csv', "name,email\nJan,jan@test.com\n");

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.customers.import'), ['file' => $csv]);

        $response->assertRedirect();
        Storage::disk('public')->assertMissing('imports/customers');
        $this->assertNotEmpty(Storage::disk('local')->allFiles('imports/customers'));
    }

    public function test_import_job_actually_finds_and_processes_the_stored_file(): void
    {
        Storage::fake('local');

        $path = Storage::disk('local')->putFileAs(
            'imports/customers',
            UploadedFile::fake()->createWithContent('customers.csv', "name,email,phone\nJan Testowy,jantest@example.com,123456789\n"),
            'customers.csv'
        );

        // Job resolves the real path via Storage::disk('local')->path() —
        // that must point at a file that genuinely exists on the fake disk.
        $fullPath = Storage::disk('local')->path($path);
        $this->assertFileExists($fullPath, 'Sanity check: the fake disk actually wrote the file where the job will look for it');

        (new ImportCustomersCsv($path, 'test-tenant', null))->handle(app(CustomerImportService::class));

        $this->assertDatabaseHas('customers', ['email' => 'jantest@example.com']);
        Storage::disk('local')->assertMissing($path);
    }
}
