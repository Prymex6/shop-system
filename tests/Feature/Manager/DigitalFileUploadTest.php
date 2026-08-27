<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Product;
use App\Models\Tenant\User;
use Illuminate\Http\UploadedFile;
use Tests\TenantTestCase;

/**
 * ProductController::uploadFile() validated only 'file|max:102400' — no
 * mimes/mimetypes rule at all, so Laravel's built-in extension-spoofing
 * guard (which only engages when one of those rules is present) never ran,
 * and a .php upload was accepted outright onto the private disk.
 */
class DigitalFileUploadTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    private function product(): Product
    {
        return Product::create([
            'name' => 'Ebook', 'slug' => 'ebook', 'price' => 20,
            'type' => 'digital', 'status' => 'active',
        ]);
    }

    public function test_php_file_upload_is_rejected(): void
    {
        $product = $this->product();
        $file = UploadedFile::fake()->createWithContent('shell.php', '<?php system($_GET["c"]); ?>');

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.products.files.upload', $product), ['file' => $file]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('digital_files', ['product_id' => $product->id]);
    }

    public function test_legitimate_pdf_upload_is_accepted(): void
    {
        $product = $this->product();
        $file = UploadedFile::fake()->create('ebook.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.products.files.upload', $product), ['file' => $file]);

        $response->assertOk();
        $this->assertDatabaseHas('digital_files', ['product_id' => $product->id]);
    }

    public function test_zip_upload_is_accepted(): void
    {
        $product = $this->product();
        $file = UploadedFile::fake()->create('assets.zip', 1000, 'application/zip');

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.products.files.upload', $product), ['file' => $file]);

        $response->assertOk();
    }
}
