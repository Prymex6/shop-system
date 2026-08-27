<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Product;
use App\Models\Tenant\ProductImage;
use App\Models\Tenant\User;
use App\Services\ProductImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TenantTestCase;

/**
 * Product images were stored as the raw upload — no resizing, no thumbnail
 * — so a gallery button rendering at 64x64px downloaded the exact same
 * multi-megabyte original as the full hero image. ProductImageProcessor
 * generates a size-capped hero (1600px) plus a small thumbnail (320px) on
 * upload instead.
 */
class ProductImagePipelineTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'manager@test.com',
            'password' => bcrypt('secret'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_processor_caps_hero_dimensions_and_generates_a_smaller_thumbnail(): void
    {
        // Deliberately larger than the 1600px hero cap.
        $file = UploadedFile::fake()->image('produkt.jpg', 2400, 1800);

        $result = (new ProductImageProcessor)->process($file, 'public', 'products');

        $this->assertNotEquals($result['path'], $result['thumbnail_path']);
        Storage::disk('public')->assertExists($result['path']);
        Storage::disk('public')->assertExists($result['thumbnail_path']);

        // 2400x1800 scaled to fit within 1600 -> 1600x1200
        $this->assertEquals(1600, $result['width']);
        $this->assertEquals(1200, $result['height']);

        [$thumbW, $thumbH] = getimagesize(Storage::disk('public')->path($result['thumbnail_path']));
        $this->assertLessThanOrEqual(320, $thumbW);
        $this->assertLessThanOrEqual(320, $thumbH);
    }

    public function test_processor_does_not_upscale_a_small_image(): void
    {
        $file = UploadedFile::fake()->image('maly.jpg', 400, 300);

        $result = (new ProductImageProcessor)->process($file, 'public', 'products');

        $this->assertEquals(400, $result['width']);
        $this->assertEquals(300, $result['height']);
    }

    public function test_uploading_gallery_image_stores_thumbnail_and_dimensions(): void
    {
        $product = Product::create([
            'name' => 'Produkt', 'slug' => 'produkt-' . uniqid(),
            'sku' => 'P-' . uniqid(), 'price' => 50, 'is_active' => true,
        ]);
        $file = UploadedFile::fake()->image('galeria.jpg', 2000, 2000);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.products.images.upload', $product), ['image' => $file]);

        $response->assertOk();
        $image = ProductImage::where('product_id', $product->id)->firstOrFail();
        $this->assertNotNull($image->thumbnail_path);
        $this->assertNotEquals($image->path, $image->thumbnail_path);
        $this->assertEquals(1600, $image->width);
        Storage::disk('public')->assertExists($image->path);
        Storage::disk('public')->assertExists($image->thumbnail_path);
    }

    public function test_deleting_gallery_image_removes_both_files(): void
    {
        $product = Product::create([
            'name' => 'Produkt', 'slug' => 'produkt-' . uniqid(),
            'sku' => 'P-' . uniqid(), 'price' => 50, 'is_active' => true,
        ]);
        $file = UploadedFile::fake()->image('galeria.jpg', 1000, 1000);
        $result = (new ProductImageProcessor)->process($file, 'public', 'products');
        $image = $product->images()->create([
            'path' => $result['path'], 'thumbnail_path' => $result['thumbnail_path'],
            'width' => $result['width'], 'height' => $result['height'], 'sort_order' => 0,
        ]);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.products.images.destroy', [$product, $image]))
            ->assertOk();

        Storage::disk('public')->assertMissing($result['path']);
        Storage::disk('public')->assertMissing($result['thumbnail_path']);
    }

    public function test_uploading_primary_product_image_sets_dimensions(): void
    {
        $product = Product::create([
            'name' => 'Produkt', 'slug' => 'produkt-' . uniqid(),
            'sku' => 'P-' . uniqid(), 'price' => 50, 'is_active' => true,
        ]);
        $file = UploadedFile::fake()->image('glowne.jpg', 1800, 900);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->put(route('tenant.manager.products.update', $product), [
                'name' => $product->name, 'type' => 'physical', 'price' => 50, 'image' => $file,
            ])->assertRedirect();

        $product->refresh();
        $this->assertEquals(1600, $product->image_width);
        $this->assertEquals(800, $product->image_height);
    }
}
