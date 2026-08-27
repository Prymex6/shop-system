<?php

namespace Tests\Feature\Manager;

use App\Http\Middleware\Tenant\CheckSetupComplete;
use App\Http\Middleware\Tenant\CheckTenantLicense;
use App\Models\Tenant\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TenantTestCase;

/**
 * Security tests for the manager file upload endpoint (/manager/settings/upload).
 *
 * Verifies that:
 * - Only image MIME types are accepted (not PHP, SVG, PDF, etc.)
 * - Oversized files are rejected
 * - Missing files are rejected
 * - Unauthenticated users can't upload
 */
class SettingsUploadSecurityTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager',
            'email' => 'mgr@test.com',
            'password' => bcrypt('secret'),
            'role' => 'manager',
            'is_active' => true,
        ]);
    }

    /** Upload as manager with Accept: application/json so validation errors return 422 (not 302 redirect). */
    private function managerUpload(array $data): TestResponse
    {
        return $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('tenant.manager.settings.upload'), $data);
    }

    // ─── Valid uploads ───────────────────────────────────────────────────────

    public function test_valid_jpeg_upload_is_accepted(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo.jpg', 200, 200);

        $response = $this->managerUpload(['file' => $file, 'subfolder' => 'logos', 'field' => 'logo_url']);

        // Should succeed (200 with url) — NOT 422
        $this->assertNotEquals(422, $response->getStatusCode(), 'Valid JPEG should not return 422');
    }

    // ─── Invalid MIME types ──────────────────────────────────────────────────

    public function test_php_file_upload_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('shell.php', 50, 'application/x-php');

        $response = $this->managerUpload(['file' => $file, 'subfolder' => 'logos', 'field' => 'logo_url']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_svg_file_upload_is_rejected(): void
    {
        // SVG can contain embedded JS — must be blocked
        $file = UploadedFile::fake()->create('evil.svg', 10, 'image/svg+xml');

        $response = $this->managerUpload(['file' => $file, 'subfolder' => 'logos', 'field' => 'logo_url']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_pdf_file_upload_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->managerUpload(['file' => $file, 'subfolder' => 'logos', 'field' => 'logo_url']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_text_file_upload_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('config.txt', 5, 'text/plain');

        $response = $this->managerUpload(['file' => $file, 'subfolder' => 'logos', 'field' => 'logo_url']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    // ─── Size limits ─────────────────────────────────────────────────────────

    public function test_oversized_image_upload_is_rejected(): void
    {
        // Limit is 5120 KB (5 MB) — create a 6 MB file
        $file = UploadedFile::fake()->create('huge.jpg', 6144, 'image/jpeg');

        $response = $this->managerUpload(['file' => $file, 'subfolder' => 'logos', 'field' => 'logo_url']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    // ─── Missing file ─────────────────────────────────────────────────────────

    public function test_upload_without_file_is_rejected(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.settings.upload'), [
                'subfolder' => 'logos',
                'field' => 'logo_url',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    // ─── Access control ──────────────────────────────────────────────────────

    /**
     * Staff (fulfillment role) must not be able to upload settings files.
     * The CheckRole middleware blocks them — this test validates the 403 path.
     * Note: unauthenticated access is covered by E49 E2E tests (auth middleware
     *       is bypassed by withoutTenantMiddleware() in the PHP test environment).
     */
    public function test_staff_role_upload_is_rejected(): void
    {
        // Use 'waiter' — a non-manager role available in the SQLite test enum
        $staff = User::create([
            'name' => 'Pracownik',
            'email' => 'waiter@test.com',
            'password' => bcrypt('secret'),
            'role' => 'waiter',
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->image('logo.jpg', 100, 100);

        // Do NOT bypass CheckRole — only bypass domain/tenancy middleware
        $response = $this->actingAs($staff, 'tenant')
            ->withoutMiddleware([
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
                CheckSetupComplete::class,
                CheckTenantLicense::class,
                // EnsureTenantAuth and CheckRole remain active
            ])
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('tenant.manager.settings.upload'), [
                'file' => $file,
                'subfolder' => 'logos',
                'field' => 'logo_url',
            ]);

        $this->assertContains($response->getStatusCode(), [302, 403],
            'Staff role should not be allowed to upload settings files'
        );
    }
}
